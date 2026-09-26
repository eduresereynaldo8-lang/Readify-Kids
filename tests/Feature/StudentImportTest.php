<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentImportFile;
use App\Services\StudentProgress;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    private User $teacherUser;
    private Teacher $teacher;
    private User $otherTeacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        // Never run migrations or access the application's database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Carbon::setTestNow('2026-09-25 12:00:00');
        DB::connection()->getSchemaBuilder()->create('users', function (Blueprint $t) {
            $t->id(); $t->string('username')->unique(); $t->string('password');
            $t->string('role'); $t->string('status'); $t->timestamps();
        });
        DB::connection()->getSchemaBuilder()->create('teachers', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('firstname'); $t->string('lastname'); $t->timestamps();
        });
        DB::connection()->getSchemaBuilder()->create('students', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->unique(); $t->integer('teacher_id');
            $t->string('firstname'); $t->string('lastname'); $t->string('lrn_no')->unique();
            $t->date('birthday'); $t->integer('age'); $t->string('gender'); $t->string('section');
            $t->integer('current_level'); $t->integer('total_points'); $t->string('student_number')->nullable();
            $t->timestamps();
        });
        DB::connection()->getSchemaBuilder()->create('activity_logs', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('role'); $t->string('action'); $t->string('module');
            $t->text('description'); $t->string('ip_address')->nullable(); $t->text('user_agent')->nullable(); $t->timestamps();
        });
        DB::connection()->getSchemaBuilder()->create('activity_results', function (Blueprint $t) {
            $t->id(); $t->integer('student_id'); $t->integer('activity_id');
            $t->decimal('score', 5, 2)->nullable(); $t->string('status'); $t->timestamps();
        });
        $this->teacherUser = $this->user('teacher_one', 'teacher');
        $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'firstname' => 'First', 'lastname' => 'Teacher']);
        $this->otherTeacherUser = $this->user('teacher_two', 'teacher');
        Teacher::create(['user_id' => $this->otherTeacherUser->id, 'firstname' => 'Other', 'lastname' => 'Teacher']);
        $this->actingAs($this->teacherUser);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $username, string $role = 'student'): User
    {
        return User::create(['username' => $username, 'password' => Hash::make('secret123'), 'role' => $role, 'status' => 'active']);
    }

    private function row(array $changes = []): array
    {
        return array_replace([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'lrn_no' => '012345679012',
            'birthday' => '2018-05-15', 'gender' => 'Male', 'section' => 'Grade 2-A', 'starting_level' => '1',
        ], $changes);
    }

    private function csv(array $rows, ?array $headers = null): UploadedFile
    {
        $handle = fopen('php://memory', 'r+');
        fputcsv($handle, $headers ?? StudentImportFile::HEADERS, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row), ',', '"', '');
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return UploadedFile::fake()->createWithContent('students.csv', $content);
    }

    private function xlsx(array $rows, bool $excelDate = false, bool $formula = false): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        foreach (array_merge([StudentImportFile::HEADERS], array_map('array_values', $rows)) as $r => $values) {
            foreach ($values as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }
        if ($excelDate) {
            $sheet->setCellValue('D2', Date::PHPToExcel(new \DateTime('2018-05-15')));
            $sheet->getStyle('D2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        }
        if ($formula) {
            $sheet->setCellValue('A2', '=1+1');
        }
        // Blank formatted rows must not become students or validation errors.
        $sheet->getStyle('A5000:G5000')->getFont()->setBold(true);
        ob_start();
        (new Xlsx($book))->setPreCalculateFormulas(false)->save('php://output');
        $content = ob_get_clean();
        $book->disconnectWorksheets();

        return UploadedFile::fake()->createWithContent('students.xlsx', $content);
    }

    private function batch(): array
    {
        return json_decode(Crypt::decryptString(session('student_import')), true);
    }

    private function preview(array $rows): array
    {
        $this->post(route('teacher.students.import.preview'), ['file' => $this->csv($rows)])
            ->assertRedirect(route('teacher.students.import'));

        return $this->batch();
    }

    private function confirm(array $extra = []): array
    {
        $id = $this->batch()['id'];
        $this->post(route('teacher.students.import.confirm'), array_merge(['batch' => $id], $extra))
            ->assertRedirect(route('teacher.students.import.result', ['batch' => $id]));

        return $this->batch()['result'];
    }

    private function workbook(string $bytes): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'readify-import-test-');
        try {
            file_put_contents($path, $bytes);

            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    public function test_teacher_can_open_import_and_management_has_button_without_dynamic_route_capture(): void
    {
        $this->get(route('teacher.students.import'))->assertOk()->assertSee('Import Students')
            ->assertSee('Download Import Template')->assertSee('Preview Students');
        $this->get(route('teacher.students.index'))->assertOk()->assertSee(route('teacher.students.import'), false);
    }

    public function test_template_has_only_required_headers_and_separate_instructions_without_sample_students(): void
    {
        $response = $this->get(route('teacher.students.import.template'))->assertOk();
        $response->assertDownload('Readify_Kids_Student_Import_Template.xlsx');
        $book = $this->workbook($response->streamedContent());
        try {
            $this->assertSame(['Student Import Template', 'Instructions'], $book->getSheetNames());
            $this->assertSame(StudentImportFile::HEADERS, $book->getSheet(0)->rangeToArray('A1:G1', null, false)[0]);
            $this->assertSame([], array_filter($book->getSheet(0)->rangeToArray('A2:G101', null, false),
                fn ($row) => count(array_filter($row, fn ($value) => $value !== null && $value !== '')) > 0));
            $this->assertSame('@', $book->getSheet(0)->getStyle('C2')->getNumberFormat()->getFormatCode());
            $this->assertStringContainsString('1, 2, 3, 4, or 5', $book->getSheet(1)->getCell('B8')->getValue());
            $this->assertSame(0, Student::count());
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_csv_preview_normalizes_gender_calculates_age_and_saves_nothing(): void
    {
        $batch = $this->preview([$this->row(['gender' => 'FEMALE'])]);
        $row = $batch['rows'][0];
        $this->assertSame('Ready', $row['status']);
        $this->assertSame('juan.delacruz', $row['username']);
        $this->assertSame('Female', $row['data']['gender']);
        $this->assertSame('012345679012', $row['data']['lrn_no']);
        $this->assertSame(8, $row['age']);
        $this->assertSame(0, Student::count());
        $this->assertSame(2, User::count());
        $this->get(route('teacher.students.import'))->assertOk()->assertSee('juan.delacruz')->assertSee('Valid Records:');
    }

    public function test_xlsx_preview_preserves_text_lrn_reads_excel_dates_and_ignores_blank_rows(): void
    {
        $this->post(route('teacher.students.import.preview'), ['file' => $this->xlsx([$this->row()], true)])->assertRedirect();
        $rows = $this->batch()['rows'];
        $this->assertCount(1, $rows);
        $this->assertSame([], $rows[0]['errors']);
        $this->assertSame('012345679012', $rows[0]['data']['lrn_no']);
        $this->assertSame('2018-05-15', $rows[0]['data']['birthday']);
    }

    public function test_confirm_creates_owned_user_and_student_hashes_password_and_starts_with_no_data(): void
    {
        $this->preview([$this->row()]);
        $result = $this->confirm(['teacher_id' => $this->otherTeacherUser->teacher->id, 'rows' => [['lrn_no' => '999999999999']]]);
        $this->assertCount(1, $result['imported']);
        $credential = $result['imported'][0];
        $student = Student::sole();
        $this->assertSame($this->teacher->id, $student->teacher_id);
        $this->assertSame('012345679012', $student->lrn_no);
        $this->assertSame(8, $student->age);
        $this->assertSame(1, $student->current_level);
        $this->assertSame(0, $student->total_points);
        $this->assertSame('student', $student->user->role);
        $this->assertSame('active', $student->user->status);
        $this->assertTrue(Hash::check($credential['password'], $student->user->password));
        $this->assertGreaterThanOrEqual(10, strlen($credential['password']));
        foreach (['/[A-Z]/', '/[a-z]/', '/[0-9]/', '/[^a-zA-Z0-9]/'] as $pattern) {
            $this->assertMatchesRegularExpression($pattern, $credential['password']);
        }
        $this->assertStringNotContainsString($credential['password'], session('student_import'));
        $this->assertStringNotContainsString($credential['password'], DB::table('activity_logs')->pluck('description')->implode(' '));
        $loaded = StudentProgress::withActivityProgress(Student::query())->sole();
        $this->assertSame('No Data', $loaded->reading_status['label']);
        $this->assertSame(0, DB::table('activity_results')->count());
        $this->get(route('teacher.students.import.result', ['batch' => $this->batch()['id']]))
            ->assertOk()->assertSee($credential['username'])->assertSee($credential['password'], false)
            ->assertDontSee($student->user->password, false);
        auth()->logout();
        $this->post(route('login.post'), ['username' => $credential['username'], 'password' => $credential['password']])
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_username_conflicts_use_lrn_then_numeric_suffix_and_reserve_names_within_file(): void
    {
        $this->user('juan.delacruz');
        $this->user('juan.delacruz.9012');
        $batch = $this->preview([
            $this->row(),
            $this->row(['lrn_no' => '112345679012']),
        ]);
        $this->assertSame(['juan.delacruz.9012.2', 'juan.delacruz.9012.3'], array_column($batch['rows'], 'username'));
        $this->assertSame('Username Conflict Resolved', $batch['rows'][0]['status']);
        $result = $this->confirm();
        $this->assertSame(['juan.delacruz.9012.2', 'juan.delacruz.9012.3'], array_column($result['imported'], 'username'));
        $this->assertNotSame($result['imported'][0]['password'], $result['imported'][1]['password']);
    }

    public function test_username_claimed_after_preview_is_resolved_at_confirmation(): void
    {
        $this->preview([$this->row()]);
        $this->user('juan.delacruz');
        $result = $this->confirm();
        $this->assertSame('juan.delacruz.9012', $result['imported'][0]['username']);
    }

    public static function invalidRows(): array
    {
        return [
            ['lrn_no', '1234', 'Invalid LRN'], ['lrn_no', '12345678901a', 'Invalid LRN'],
            ['birthday', '2026-02-30', 'Invalid Birthday'], ['birthday', '2027-01-01', 'Invalid Birthday'],
            ['gender', 'Other', 'Invalid Gender'], ['first_name', '', 'Missing Required Field'],
            ['section', '', 'Missing Required Field'], ['starting_level', 'Beginner', 'Invalid Level'],
            ['starting_level', '6', 'Invalid Level'],
        ];
    }

    #[DataProvider('invalidRows')]
    public function test_invalid_rows_are_reported_and_never_imported(string $field, string $value, string $message): void
    {
        $batch = $this->preview([$this->row([$field => $value])]);
        $this->assertStringContainsString($message, implode(' ', $batch['rows'][0]['errors']));
        $result = $this->confirm();
        $this->assertCount(0, $result['imported']);
        $this->assertCount(1, $result['skipped']);
        $this->assertSame(2, User::count());
        $this->assertSame(0, Student::count());
    }

    public function test_all_duplicate_lrns_in_file_are_invalid_and_valid_rows_still_import(): void
    {
        $batch = $this->preview([$this->row(), $this->row(), $this->row(['lrn_no' => '123456789099'])]);
        foreach ([0, 1] as $i) {
            $this->assertStringContainsString('Duplicate LRN', implode(' ', $batch['rows'][$i]['errors']));
        }
        $result = $this->confirm();
        $this->assertSame(3, $result['total']);
        $this->assertCount(1, $result['imported']);
        $this->assertCount(2, $result['skipped']);
    }

    public function test_existing_lrn_is_rejected_and_not_overwritten(): void
    {
        $this->preview([$this->row()]); $this->confirm();
        $studentId = Student::sole()->id;
        $batch = $this->preview([$this->row(['first_name' => 'Changed'])]);
        $this->assertStringContainsString('Duplicate LRN', implode(' ', $batch['rows'][0]['errors']));
        $this->confirm();
        $this->assertSame($studentId, Student::sole()->id);
        $this->assertSame('Juan', Student::sole()->firstname);
    }

    public function test_lrn_claimed_between_preview_and_confirm_is_skipped(): void
    {
        $this->preview([$this->row()]);
        $user = $this->user('existing_student');
        Student::create(['user_id' => $user->id, 'teacher_id' => $this->otherTeacherUser->teacher->id,
            'firstname' => 'Existing', 'lastname' => 'Reader', 'lrn_no' => $this->row()['lrn_no'],
            'birthday' => '2018-05-15', 'age' => 8, 'gender' => 'Male', 'section' => 'A', 'current_level' => 1, 'total_points' => 0]);
        $result = $this->confirm();
        $this->assertCount(0, $result['imported']);
        $this->assertCount(1, $result['skipped']);
        $this->assertSame(3, User::count());
    }

    public function test_empty_csv_rows_are_ignored_and_missing_or_extra_headers_are_rejected(): void
    {
        $batch = $this->preview([array_fill(0, 7, ''), $this->row(), array_fill(0, 7, '')]);
        $this->assertCount(1, $batch['rows']);
        $response = $this->postJson(route('teacher.students.import.preview'), [
            'file' => $this->csv([$this->row()], ['first_name', 'last_name']),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertStringContainsString('Missing columns: lrn_no', $response->json('errors.file.0'));
        $this->postJson(route('teacher.students.import.preview'), [
            'file' => $this->csv([$this->row()], array_merge(StudentImportFile::HEADERS, ['teacher_id'])),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_student_creation_failure_rolls_back_user_and_other_valid_rows_can_succeed(): void
    {
        $this->preview([$this->row(), $this->row(['first_name' => 'Maria', 'lrn_no' => '123456789013'])]);
        Student::creating(function (Student $student) {
            if ($student->firstname === 'Juan') {
                throw new \RuntimeException('Simulated student failure');
            }
        });
        try {
            $result = $this->confirm();
        } finally {
            Student::flushEventListeners();
        }
        $this->assertCount(1, $result['imported']);
        $this->assertCount(1, $result['skipped']);
        $this->assertSame(3, User::count());
        $this->assertSame('Maria', Student::sole()->firstname);
        $this->assertFalse(User::where('username', 'juan.delacruz')->exists());
    }

    public function test_confirmation_is_repeatable_without_duplicate_accounts_or_replacing_passwords(): void
    {
        $this->preview([$this->row()]);
        $first = $this->confirm();
        $this->assertSame($first, $this->confirm());
        $this->assertSame(1, Student::count());
        $this->assertSame(3, User::count());
    }

    public function test_credentials_download_only_contains_current_import_and_preserves_lrn_as_text(): void
    {
        $this->preview([$this->row()]);
        $first = $this->confirm();
        $id = $this->batch()['id'];
        $response = $this->get(route('teacher.students.import.credentials', ['batch' => $id]))->assertOk();
        $response->assertDownload('ReadifyKids_Student_Login_Credentials_2026-09-25.xlsx');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $book = $this->workbook($response->streamedContent());
        try {
            $sheet = $book->getSheet(0);
            $this->assertSame('012345679012', $sheet->getCell('B2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
            $this->assertSame($first['imported'][0]['password'], $sheet->getCell('D2')->getValue());
            $this->assertSame(2, $sheet->getHighestDataRow());
        } finally {
            $book->disconnectWorksheets();
        }
        $this->preview([$this->row(['first_name' => 'Maria', 'lrn_no' => '123456789013'])]);
        $this->confirm();
        $this->get(route('teacher.students.import.credentials', ['batch' => $id]))->assertStatus(410);
        $this->assertCount(1, $this->batch()['result']['imported']);
        $this->assertStringNotContainsString($first['imported'][0]['password'], json_encode($this->batch()['result']));
    }

    public function test_other_teacher_cannot_confirm_or_download_another_teachers_import(): void
    {
        $this->preview([$this->row()]); $this->confirm();
        $id = $this->batch()['id'];
        $this->actingAs($this->otherTeacherUser);
        $this->post(route('teacher.students.import.confirm'), ['batch' => $id])->assertForbidden();
        $this->get(route('teacher.students.import.result', ['batch' => $id]))->assertForbidden();
        $this->get(route('teacher.students.import.credentials', ['batch' => $id]))->assertForbidden();
        $this->assertSame(1, Student::count());
    }

    public function test_expiry_and_finish_remove_access_to_credentials(): void
    {
        $this->preview([$this->row()]); $this->confirm();
        $id = $this->batch()['id'];
        $this->post(route('teacher.students.import.finish'), ['batch' => $id])
            ->assertRedirect(route('teacher.students.index'));
        $this->assertNull(session('student_import'));
        $this->get(route('teacher.students.import.credentials', ['batch' => $id]))->assertStatus(410);

        $this->preview([$this->row(['lrn_no' => '123456789013'])]); $this->confirm();
        $id = $this->batch()['id'];
        Carbon::setTestNow(now()->addMinutes(31));
        $this->get(route('teacher.students.import.credentials', ['batch' => $id]))->assertStatus(410);
        $this->assertNull(session('student_import'));
    }

    public function test_student_and_guest_cannot_open_import_routes(): void
    {
        $this->actingAs($this->user('learner'))->get(route('teacher.students.import'))->assertForbidden();
        $this->post(route('teacher.students.import.preview'), ['file' => $this->csv([$this->row()])])->assertForbidden();
        auth()->logout();
        $this->get(route('teacher.students.import'))->assertRedirect(route('login'));
    }

    public function test_wrong_extensions_oversized_files_and_disguised_html_are_rejected(): void
    {
        foreach (['php', 'exe', 'js', 'html', 'pdf', 'zip'] as $extension) {
            $this->postJson(route('teacher.students.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('students.'.$extension, 'not a spreadsheet'),
            ])->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->postJson(route('teacher.students.import.preview'), [
            'file' => UploadedFile::fake()->create('students.csv', 5121, 'text/csv'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson(route('teacher.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', '<html><body>bad file</body></html>'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson(route('teacher.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('students.xlsx', 'not a zip workbook'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_formula_cells_are_not_evaluated_and_downloaded_errors_are_literal_strings(): void
    {
        $batch = $this->preview([$this->row(['first_name' => '=1+1'])]);
        $this->assertContains('Use plain values, not formulas.', $batch['rows'][0]['errors']);
        $response = $this->get(route('teacher.students.import.errors', ['batch' => $batch['id']]))->assertOk();
        $book = $this->workbook($response->streamedContent());
        try {
            $this->assertSame('=1+1', $book->getSheet(0)->getCell('B2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $book->getSheet(0)->getCell('B2')->getDataType());
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_more_than_the_row_limit_is_rejected_before_any_accounts_are_created(): void
    {
        $this->postJson(route('teacher.students.import.preview'), [
            'file' => $this->csv(array_fill(0, StudentImportFile::MAX_ROWS + 1, $this->row())),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame(0, Student::count());
        $this->assertSame(2, User::count());
    }
    public function test_xlsx_formulas_external_relationships_and_expansion_limits_are_rejected(): void
    {
        $this->post(route('teacher.students.import.preview'), [
            'file' => $this->xlsx([$this->row()], false, true),
        ])->assertRedirect();
        $this->assertContains('Use plain values, not formulas.', $this->batch()['rows'][0]['errors']);

        foreach ([
            'xl/worksheets/_rels/sheet1.xml.rels' => '<Relationships><Relationship TargetMode="External" Target="https://example.test/private"/></Relationships>',
            'xl/oversized.xml' => str_repeat('x', 6 * 1024 * 1024),
        ] as $entry => $contents) {
            // Laravel fake uploads keep a file handle open; Windows cannot replace
            // that file when ZipArchive closes, so mutate a separate closed fixture.
            $path = tempnam(sys_get_temp_dir(), 'readify-xlsx-test-');
            try {
                $original = $this->xlsx([$this->row()]);
                file_put_contents($path, file_get_contents($original->getRealPath()));
                $zip = new \ZipArchive;
                $zip->open($path);
                $zip->addFromString($entry, $contents);
                $zip->close();
                $file = UploadedFile::fake()->createWithContent('students.xlsx', file_get_contents($path));
            } finally {
                unlink($path);
            }
            $this->postJson(route('teacher.students.import.preview'), ['file' => $file])
                ->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->assertSame(0, Student::count());
    }

    public function test_expired_preview_and_wrong_batch_cannot_create_accounts(): void
    {
        $id = $this->preview([$this->row()])['id'];
        $this->post(route('teacher.students.import.confirm'), ['batch' => (string) Str::uuid()])->assertStatus(410);
        Carbon::setTestNow(now()->addMinutes(31));
        $this->post(route('teacher.students.import.confirm'), ['batch' => $id])->assertStatus(410);
        $this->assertSame(0, Student::count());
        $this->assertSame(2, User::count());
    }

    public function test_logout_invalidates_credentials_access(): void
    {
        $this->preview([$this->row()]); $this->confirm();
        $id = $this->batch()['id'];
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertNull(session('student_import'));
        $this->get(route('teacher.students.import.credentials', ['batch' => $id]))->assertRedirect(route('login'));
    }


}
