<?php

namespace App\Services;

use App\Helpers\LogActivity;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentImportService
{
    public function preview(array $rows): array
    {
        $counts = array_count_values(array_map(fn ($row) => $row['data']['lrn_no'], $rows));
        $existing = Student::whereIn('lrn_no', array_keys($counts))->pluck('lrn_no')->all();
        $reserved = [];
        foreach ($rows as &$row) {
            $data = $row['data'];
            $data['gender'] = ucfirst(strtolower($data['gender']));
            if (preg_match('/^level\s+([1-5])$/i', $data['starting_level'], $match)) {
                $data['starting_level'] = $match[1];
            }
            $validator = Validator::make($data, $this->rules(), $this->messages());
            $errors = array_merge($row['errors'], $validator->errors()->all());
            if ($data['lrn_no'] !== '' && ($counts[$data['lrn_no']] > 1 || in_array($data['lrn_no'], $existing, true))) {
                $errors[] = 'Duplicate LRN: this LRN already exists or appears more than once in this file.';
            }
            $row['data'] = $data;
            $row['errors'] = array_values(array_unique($errors));
            $row['age'] = $validator->errors()->has('birthday') ? null : (int) Carbon::parse($data['birthday'])->age;
            $row['username'] = null;
            $row['status'] = 'Invalid';
            if (!$row['errors']) {
                $row['username'] = $this->username($data, $reserved);
                $reserved[] = $row['username'];
                $row['status'] = $row['username'] === $this->baseUsername($data) ? 'Ready' : 'Username Conflict Resolved';
            }
        }
        unset($row);

        return $rows;
    }

    public function import(array $rows, Teacher $teacher): array
    {
        $result = ['total' => count($rows), 'imported' => [], 'skipped' => []];
        foreach ($rows as $row) {
            if ($row['errors']) {
                $result['skipped'][] = $row;
                continue;
            }
            // Repeat validation after preview to detect changes in the database.
            // A unique constraint remains the final arbiter if another import races us.
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $credential = DB::transaction(function () use ($row, $teacher) {
                        $data = $row['data'];
                        $rules = $this->rules();
                        $rules['lrn_no'][] = Rule::unique('students', 'lrn_no');
                        Validator::make($data, $rules, $this->messages())->validate();
                        $username = $this->username($data);
                        $password = $this->temporaryPassword();
                        $user = User::create([
                            'username' => $username,
                            'password' => Hash::make($password),
                            'role' => 'student',
                            'status' => 'active',
                        ]);
                        $student = Student::create([
                            'user_id' => $user->id,
                            'teacher_id' => $teacher->id,
                            'firstname' => $data['first_name'],
                            'lastname' => $data['last_name'],
                            'lrn_no' => $data['lrn_no'],
                            'birthday' => $data['birthday'],
                            'age' => (int) Carbon::parse($data['birthday'])->age,
                            'gender' => $data['gender'],
                            'section' => $data['section'],
                            'current_level' => (int) $data['starting_level'],
                            'total_points' => 0,
                        ]);
                        LogActivity::log('ADD_STUDENT', 'Students',
                            'Imported student: '.$student->firstname.' '.$student->lastname);

                        return [
                            'row' => $row['row'],
                            'name' => $student->firstname.' '.$student->lastname,
                            'lrn_no' => $student->lrn_no,
                            'username' => $username,
                            'password' => $password,
                            'status' => 'Imported',
                        ];
                    });
                    $result['imported'][] = $credential;
                    break;
                } catch (ValidationException $error) {
                    $row['errors'] = array_merge(...array_values($error->errors()));
                    $result['skipped'][] = $row;
                    break;
                } catch (QueryException $error) {
                    if (Student::where('lrn_no', $row['data']['lrn_no'])->exists()) {
                        $row['errors'] = ['Duplicate LRN: another account now uses this LRN.'];
                    } elseif (in_array((string) $error->getCode(), ['23000', '23505'], true) && $attempt < 2) {
                        // A concurrent username allocation can be retried after rollback.
                        continue;
                    } else {
                        report($error);
                        $row['errors'] = ['Could not create this student. No account was saved for this row. Please retry this row.'];
                    }
                    $result['skipped'][] = $row;
                    break;
                } catch (\Throwable $error) {
                    report($error);
                    $row['errors'] = ['Could not create this student. No account was saved for this row. Please retry this row.'];
                    $result['skipped'][] = $row;
                    break;
                }
            }
        }

        return $result;
    }

    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'lrn_no' => ['required', 'string', 'regex:/^[0-9]{12}$/'],
            'birthday' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'section' => ['required', 'string', 'max:50'],
            'starting_level' => ['required', 'integer', Rule::in(range(1, 5))],
        ];
    }

    private function messages(): array
    {
        return [
            '*.required' => 'Missing Required Field: :attribute is required.',
            'lrn_no.regex' => 'Invalid LRN: enter exactly 12 digits.',
            'lrn_no.unique' => 'Duplicate LRN: this LRN already exists.',
            'birthday.date_format' => 'Invalid Birthday: use a real date in YYYY-MM-DD format.',
            'birthday.before_or_equal' => 'Invalid Birthday: a birthday cannot be in the future.',
            'gender.in' => 'Invalid Gender: use Male or Female.',
            'starting_level.integer' => 'Invalid Level: use a starting level from 1 to 5.',
            'starting_level.in' => 'Invalid Level: use a starting level from 1 to 5.',
        ];
    }

    private function baseUsername(array $data): string
    {
        $first = preg_replace('/[^a-z0-9]+/', '.', strtolower(Str::ascii($data['first_name'])));
        $last = preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii($data['last_name'])));
        $base = trim(preg_replace('/\.+/', '.', trim($first, '.').'.'.$last), '.');

        return rtrim(substr($base !== '' ? $base : 'student', 0, 85), '.');
    }

    private function username(array $data, array $reserved = []): string
    {
        $base = $this->baseUsername($data);
        $candidate = $base;
        $suffix = '.'.substr($data['lrn_no'], -4);
        $number = 1;
        while (in_array($candidate, $reserved, true) || User::where('username', $candidate)->exists()) {
            $tail = $suffix.($number === 1 ? '' : '.'.$number);
            $candidate = substr($base, 0, 100 - strlen($tail)).$tail;
            $number++;
        }

        return $candidate;
    }

    private function temporaryPassword(): string
    {
        $groups = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%*?'];
        $characters = [];
        foreach ($groups as $group) {
            $characters[] = $group[random_int(0, strlen($group) - 1)];
        }
        $alphabet = implode('', $groups);
        while (count($characters) < 12) {
            $characters[] = $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }
}
