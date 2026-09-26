<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use ZipArchive;

class StudentImportFile
{
    public const HEADERS = ['first_name', 'last_name', 'lrn_no', 'birthday', 'gender', 'section', 'starting_level'];
    public const MAX_ROWS = 100;

    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'xlsx'], true) || !$file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
            $this->invalid('Choose an XLSX or CSV file no larger than 5 MB.');
        }

        try {
            $rows = $extension === 'xlsx' ? $this->xlsx($file) : $this->csv($file);
        } catch (ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            // Parser details may include uploaded data or paths; do not expose them.
            $this->invalid('This file could not be read. Save a new XLSX or UTF-8 CSV using the import template.');
        }

        if (!$rows) {
            $this->invalid('The import file is empty.');
        }
        $headerRow = array_shift($rows);
        $headers = array_map(fn ($value) => trim((string) $value), $headerRow['values']);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0] ?? '');
        $missing = array_diff(self::HEADERS, $headers);
        if ($missing || count($headers) !== count(self::HEADERS) || array_diff($headers, self::HEADERS)) {
            $message = 'The import file does not match the Readify Kids student template.';
            if ($missing) {
                $message .= ' Missing columns: '.implode(', ', $missing).'.';
            }
            $this->invalid($message.' Use only the seven template columns.');
        }

        $records = [];
        foreach ($rows as $row) {
            $values = $row['values'];
            if (!array_filter($values, fn ($value) => $value !== null && trim((string) $value) !== '')) {
                continue;
            }
            $errors = $row['errors'] ?? [];
            if (count($values) !== count($headers)) {
                $errors[] = 'The row must contain exactly seven columns.';
            }
            $values = array_slice(array_pad($values, count($headers), ''), 0, count($headers));
            $data = array_combine($headers, array_map(fn ($value) => trim((string) ($value ?? '')), $values));
            foreach ($data as $column => $value) {
                if (mb_strlen($value) > 500) {
                    $errors[] = $column.' is too long.';
                    $data[$column] = mb_substr($value, 0, 500);
                }
                if (preg_match('/^[=+@]/u', $value)) {
                    $errors[] = 'Use plain values, not formulas.';
                }
            }
            $records[] = ['row' => $row['row'], 'data' => $data, 'errors' => array_values(array_unique($errors))];
            if (count($records) > self::MAX_ROWS) {
                $this->invalid('Import up to '.self::MAX_ROWS.' students at a time. Split larger classes into separate files.');
            }
        }
        if (!$records) {
            $this->invalid('The file contains headers but no students. Add student rows before previewing.');
        }

        return $records;
    }

    private function csv(UploadedFile $file): array
    {
        if (!in_array($file->getMimeType(), ['text/plain', 'text/csv', 'text/x-csv', 'application/csv', 'application/vnd.ms-excel'], true)) {
            $this->invalid('Choose a plain UTF-8 CSV file or an XLSX workbook.');
        }
        $content = file_get_contents($file->getRealPath());
        if (!mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            $this->invalid('Save the CSV as UTF-8, with comma-separated columns.');
        }
        $handle = fopen($file->getRealPath(), 'rb');
        $rows = [];
        try {
            $line = 0;
            while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $line++;
                if (!array_filter($values, fn ($value) => $value !== null && trim($value) !== '')) {
                    continue;
                }
                $rows[] = ['row' => $line, 'values' => $values];
                if (count($rows) > self::MAX_ROWS + 1) {
                    $this->invalid('Import up to '.self::MAX_ROWS.' students at a time.');
                }
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function xlsx(UploadedFile $file): array
    {
        $this->inspectArchive($file->getRealPath());
        $reader = new Xlsx;
        if (!$reader->canRead($file->getRealPath())) {
            $this->invalid('This file is not a valid XLSX workbook.');
        }
        $names = $reader->listWorksheetNames($file->getRealPath());
        if (!$names) {
            $this->invalid('The workbook has no worksheets.');
        }
        $reader->setLoadSheetsOnly($names[0]);
        $reader->setReadEmptyCells(false);
        $reader->setIncludeCharts(false);
        // Keep date styles, but never evaluate formulas or load a different file format.
        $book = $reader->load($file->getRealPath());
        try {
            $sheet = $book->getSheet(0);
            $byRow = [];
            foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
                $cell = $sheet->getCell($coordinate);
                $value = $cell->getValue();
                if ($value instanceof RichText) {
                    $value = $value->getPlainText();
                }
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }
                $row = $cell->getRow();
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
                if ($column > 7) {
                    $this->invalid('The import file does not match the Readify Kids student template. Use only the seven template columns.');
                }
                $byRow[$row] ??= ['row' => $row, 'values' => array_fill(0, 7, ''), 'errors' => []];
                if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                    $byRow[$row]['errors'][] = 'Use plain values, not formulas.';
                } elseif (is_numeric($value) && Date::isDateTime($cell)) {
                    $value = Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
                } elseif (is_float($value) && floor($value) === $value) {
                    $value = sprintf('%.0f', $value);
                }
                $byRow[$row]['values'][$column - 1] = (string) $value;
                if (count($byRow) > self::MAX_ROWS + 1) {
                    $this->invalid('Import up to '.self::MAX_ROWS.' students at a time.');
                }
            }
            ksort($byRow);

            return array_values($byRow);
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function inspectArchive(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->invalid('This file is not a valid XLSX workbook.');
        }
        try {
            $total = 0;
            if ($zip->numFiles > 512) {
                $this->invalid('This workbook is too complex. Copy the student values into a fresh template.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $total += $entry['size'];
                if ($total > 20 * 1024 * 1024 || $entry['size'] > 5 * 1024 * 1024) {
                    $this->invalid('This workbook expands beyond the import limit. Copy the student values into a fresh template.');
                }
                $name = strtolower($entry['name']);
                if (str_contains($name, 'vbaproject') || str_contains($name, 'externallinks/')
                    || str_contains($name, 'embeddings/') || str_contains($name, 'connections.xml')) {
                    $this->invalid('Upload a workbook containing student values only, without macros, embedded files, or external connections.');
                }
                if (str_ends_with($name, '.xml') || str_ends_with($name, '.rels')) {
                    $xml = $zip->getFromIndex($i);
                    if (!mb_check_encoding($xml, 'UTF-8') || preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $xml)
                        || preg_match('/TargetMode\s*=\s*["\']External["\']/i', $xml)) {
                        $this->invalid('Upload a workbook containing plain student values without external links.');
                    }
                    if (str_starts_with($name, 'xl/worksheets/') && preg_match_all('/<(?:\w+:)?c(?:\s|>)/', $xml) > 20000) {
                        $this->invalid('This workbook is too complex. Copy the student values into a fresh template.');
                    }
                }
            }
        } finally {
            $zip->close();
        }
    }

    public function template()
    {
        $instructions = [
            ['Column', 'Instructions'],
            ['first_name', 'Required. Student first name.'],
            ['last_name', 'Required. Student last name.'],
            ['lrn_no', 'Required. Exactly 12 digits; unique. Keep this column as Text to preserve leading zeros.'],
            ['birthday', 'Required. YYYY-MM-DD; cannot be a future date.'],
            ['gender', 'Required. Male or Female. Letter case is normalized.'],
            ['section', 'Required. Class section, up to 50 characters.'],
            ['starting_level', 'Required. 1, 2, 3, 4, or 5 (the existing Readify Kids levels).'],
            ['File limits', 'XLSX or comma-separated UTF-8 CSV; up to 5 MB and '.self::MAX_ROWS.' student rows.'],
            ['How to use', 'Fill the first sheet, preview, then confirm. Blank rows are ignored.'],
            ['Accounts', 'Usernames and random temporary passwords are generated after confirmation.'],
            ['Do not add', 'Do not include username, password, age, or teacher_id columns.'],
            ['Sample only', 'Juan | Dela Cruz | 123456789012 | 2018-05-15 | Male | Grade 2-A | 1'],
        ];

        return $this->download([
            'Student Import Template' => [self::HEADERS],
            'Instructions' => $instructions,
        ], 'Readify_Kids_Student_Import_Template.xlsx');
    }

    public function download(array $sheets, string $filename)
    {
        // Explicit string cells preserve LRNs and prevent spreadsheet formula injection.
        return response()->streamDownload(function () use ($sheets) {
            $book = new Spreadsheet;
            $book->removeSheetByIndex(0);
            foreach ($sheets as $title => $rows) {
                $sheet = $book->createSheet()->setTitle($title);
                foreach ($rows as $rowIndex => $values) {
                    foreach (array_values($values) as $columnIndex => $value) {
                        $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 1], (string) ($value ?? ''), DataType::TYPE_STRING);
                    }
                }
                $lastColumn = $sheet->getHighestDataColumn();
                $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1:'.$lastColumn.'1')->getFill()->setFillType('solid')->getStartColor()->setRGB('173E70');
                $sheet->freezePane('A2');
                if ($title === 'Student Import Template') {
                    $sheet->getStyle('A2:G'.(self::MAX_ROWS + 1))->getNumberFormat()->setFormatCode('@');
                }
                foreach (range('A', $lastColumn) as $column) {
                    $sheet->getColumnDimension($column)->setWidth($title === 'Instructions' && $column === 'B' ? 105 : 24);
                    $sheet->getStyle($column.':'.$column)->getNumberFormat()->setFormatCode('@');
                }
            }
            $book->setActiveSheetIndex(0);
            try {
                $writer = new XlsxWriter($book);
                $writer->setPreCalculateFormulas(false);
                $writer->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
            'Pragma' => 'no-cache', 'Expires' => '0', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
