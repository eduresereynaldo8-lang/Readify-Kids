<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Services\StudentImportFile;
use App\Services\StudentImportService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class StudentImportController extends Controller
{
    private const SESSION_KEY = 'student_import';
    private const LIFETIME_MINUTES = 30;

    public function index(Request $request)
    {
        $teacher = $this->teacher($request);
        $batch = $this->load($request, $teacher);

        return view('teacher.students.import', [
            'batch' => $batch,
            'rows' => $batch && $batch['phase'] === 'preview' ? $batch['rows'] : null,
            'maxRows' => StudentImportFile::MAX_ROWS,
        ]);
    }

    public function template(Request $request, StudentImportFile $files)
    {
        $this->teacher($request);

        return $files->template();
    }

    public function preview(Request $request, StudentImportFile $files, StudentImportService $imports)
    {
        $teacher = $this->teacher($request);
        $request->validate(['file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv']]);
        $rows = $imports->preview($files->read($request->file('file')));
        // A new preview replaces credentials from the preceding import.
        $this->save($request, [
            'id' => (string) Str::uuid(),
            'teacher_id' => $teacher->id,
            'user_id' => $request->user()->id,
            'phase' => 'preview',
            'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES)->timestamp,
            'rows' => $rows,
        ]);

        return redirect()->route('teacher.students.import');
    }

    public function confirm(Request $request, StudentImportService $imports)
    {
        $teacher = $this->teacher($request);
        $batch = $this->requireBatch($request, $teacher);
        if ($batch['phase'] === 'preview') {
            $result = $imports->import($batch['rows'], $teacher);
            unset($batch['rows']);
            $batch['phase'] = 'result';
            $batch['result'] = $result;
            $batch['expires_at'] = now()->addMinutes(self::LIFETIME_MINUTES)->timestamp;
            $this->save($request, $batch);
        }

        // Repeated confirmation returns the same credentials without creating users.
        return redirect()->route('teacher.students.import.result', ['batch' => $batch['id']]);
    }

    public function result(Request $request)
    {
        $batch = $this->requireBatch($request, $this->teacher($request));
        abort_unless($batch['phase'] === 'result', 409);

        return view('teacher.students.import-result', ['batch' => $batch, 'result' => $batch['result']]);
    }

    public function credentials(Request $request, StudentImportFile $files)
    {
        $batch = $this->requireBatch($request, $this->teacher($request));
        abort_unless($batch['phase'] === 'result', 409);
        $rows = [['Student Name', 'LRN No.', 'Username', 'Temporary Password']];
        foreach ($batch['result']['imported'] as $credential) {
            $rows[] = [$credential['name'], $credential['lrn_no'], $credential['username'], $credential['password']];
        }

        return $files->download(['Student Credentials' => $rows],
            'ReadifyKids_Student_Login_Credentials_'.now()->format('Y-m-d').'.xlsx');
    }

    public function errors(Request $request, StudentImportFile $files)
    {
        $batch = $this->requireBatch($request, $this->teacher($request));
        $invalid = $batch['phase'] === 'preview'
            ? array_filter($batch['rows'], fn ($row) => count($row['errors']) > 0)
            : $batch['result']['skipped'];
        $rows = [array_merge(['row'], StudentImportFile::HEADERS, ['error'])];
        foreach ($invalid as $row) {
            $rows[] = array_merge([$row['row']], array_map(fn ($key) => $row['data'][$key], StudentImportFile::HEADERS),
                [implode(' ', $row['errors'])]);
        }

        return $files->download(['Skipped Students' => $rows], 'ReadifyKids_Skipped_Students_'.now()->format('Y-m-d').'.xlsx');
    }

    public function finish(Request $request)
    {
        $this->requireBatch($request, $this->teacher($request));
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('teacher.students.index');
    }

    private function teacher(Request $request): Teacher
    {
        abort_unless($request->user()->role === 'teacher', 403);
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        return $teacher;
    }

    private function requireBatch(Request $request, Teacher $teacher): array
    {
        $request->validate(['batch' => ['required', 'uuid']]);
        $batch = $this->load($request, $teacher);
        abort_unless($batch && hash_equals($batch['id'], $request->input('batch')), 410,
            'This import has expired or was replaced. Start a new import from Student Management.');

        return $batch;
    }

    private function load(Request $request, Teacher $teacher): ?array
    {
        $encrypted = $request->session()->get(self::SESSION_KEY);
        if (!$encrypted) {
            return null;
        }
        try {
            $batch = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException | \JsonException $error) {
            $request->session()->forget(self::SESSION_KEY);
            abort(410, 'This import is no longer available. Please start a new import.');
        }
        abort_unless($batch['teacher_id'] === $teacher->id && $batch['user_id'] === $request->user()->id, 403);
        if ($batch['expires_at'] <= now()->timestamp) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $batch;
    }

    private function save(Request $request, array $batch): void
    {
        // SESSION_DRIVER may be database and SESSION_ENCRYPT may be false.
        // Encrypt this payload independently: no plaintext passwords in that table.
        $request->session()->put(self::SESSION_KEY, Crypt::encryptString(json_encode($batch, JSON_THROW_ON_ERROR)));
    }
}
