<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Disposable schema only. Never execute migrations or access the project database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach ([
            'users' => ['username', 'email', 'password', 'role', 'status'],
            'teachers' => ['user_id', 'firstname', 'lastname', 'school_name'],
            'students' => ['user_id', 'teacher_id', 'firstname', 'lastname', 'lrn_no', 'birthday',
                'age', 'gender', 'section', 'current_level', 'total_points', 'profile_picture'],
            'activities' => ['teacher_id', 'activity_name', 'activity_type', 'is_published'],
            'activity_results' => ['student_id', 'activity_id', 'status', 'score', 'completed_at'],
            'evaluations' => ['teacher_id', 'recording_id'],
            'activity_logs' => ['user_id', 'role', 'action', 'module', 'description', 'ip_address', 'user_agent'],
        ] as $table => $columns) {
            Schema::create($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->id();
                foreach ($columns as $column) {
                    $blueprint->string($column)->nullable();
                }
                $blueprint->timestamps();
            });
        }
        Storage::fake('public');
    }

    private function account(string $role): User
    {
        $number = User::count() + 1;
        $user = User::create(['username' => $role.$number, 'email' => $role.$number.'@example.test',
            'password' => Hash::make('Original123!'), 'role' => $role, 'status' => 'active']);
        if ($role === 'teacher') {
            Teacher::create(['user_id' => $user->id, 'firstname' => 'Teacher', 'lastname' => 'Example',
                'school_name' => 'Reading School']);
        }
        if ($role === 'student') {
            $teacher = $this->account('teacher')->teacher;
            Student::create(['user_id' => $user->id, 'teacher_id' => $teacher->id,
                'firstname' => 'Amda', 'lastname' => 'Ryzen', 'lrn_no' => '012345678901',
                'birthday' => '2018-05-15', 'age' => 8, 'gender' => 'Male', 'section' => 'Section A',
                'current_level' => 1, 'total_points' => 0]);
        }

        return $user;
    }

    public static function roles(): array
    {
        return [['admin'], ['teacher'], ['student']];
    }

    #[DataProvider('roles')]
    public function test_each_role_sees_its_profile_and_dropdown(string $role): void
    {
        $user = $this->account($role);
        $response = $this->actingAs($user)->get(route($role.'.profile.show'))
            ->assertOk()->assertSee('My Profile')->assertSee($user->username)
            ->assertSee(route($role.'.profile.password'), false)
            ->assertDontSee($user->password, false);
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        if ($role === 'admin') {
            $response->assertDontSee('System reports')->assertSee(route('admin.reports'), false);
        }
        if ($role === 'student') {
            $response->assertSee('012345678901')->assertSee('Section A')->assertSee('Level 1')
                ->assertSee('May 15, 2018')->assertSee('AR')->assertDontSee('data-profile-photo', false);
        }
    }

    public function test_admin_updates_only_own_allowed_fields(): void
    {
        $admin = $this->account('admin');
        $other = $this->account('admin');
        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'id' => $other->id, 'user_id' => $other->id, 'username' => 'renamed_admin',
            'email' => 'renamed@example.test', 'role' => 'student', 'status' => 'inactive',
            'password' => 'UnverifiedPassword', 'firstname' => 'Invented',
        ])->assertRedirect(route('admin.profile.show'))->assertSessionHasNoErrors();
        $admin->refresh();
        $this->assertSame('renamed_admin', $admin->username);
        $this->assertSame('renamed@example.test', $admin->email);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('Original123!', $admin->password));
        $this->assertSame($other->username, $other->fresh()->username);
    }

    public function test_teacher_updates_own_information_and_ignores_submitted_ownership(): void
    {
        $user = $this->account('teacher');
        $other = $this->account('teacher');
        $this->actingAs($user)->put(route('teacher.profile.update'), [
            'id' => $other->teacher->id, 'teacher_id' => $other->teacher->id, 'user_id' => $other->id,
            'firstname' => 'Updated', 'lastname' => 'Name', 'school_name' => 'New School',
            'username' => 'updated_teacher', 'email' => 'new@example.test', 'role' => 'admin',
        ])->assertRedirect(route('teacher.profile.show'))->assertSessionHasNoErrors();
        $this->assertSame('Updated', $user->teacher->fresh()->firstname);
        $this->assertSame('New School', $user->teacher->fresh()->school_name);
        $this->assertSame('updated_teacher', $user->fresh()->username);
        $this->assertSame('teacher', $user->fresh()->role);
        $this->assertSame('Teacher', $other->teacher->fresh()->firstname);
    }

    #[DataProvider('editableRoles')]
    public function test_username_and_email_uniqueness_and_own_values(string $role): void
    {
        $user = $this->account($role);
        $other = $this->account($role);
        $payload = ['username' => $user->username, 'email' => $user->email,
            'firstname' => 'Teacher', 'lastname' => 'Example', 'school_name' => 'School'];
        $this->actingAs($user)->put(route($role.'.profile.update'), $payload)->assertSessionHasNoErrors();
        $this->put(route($role.'.profile.update'), array_merge($payload, [
            'username' => $other->username, 'email' => $other->email,
        ]))->assertSessionHasErrors(['username', 'email'], null, 'profile');
        $this->assertSame($user->username, $user->fresh()->username);
    }

    public static function editableRoles(): array
    {
        return [['admin'], ['teacher']];
    }

    #[DataProvider('roles')]
    public function test_password_change_requires_current_password_and_keeps_session(string $role): void
    {
        $user = $this->account($role);
        $other = $this->account($role);
        $this->actingAs($user)->get(route($role.'.profile.show'))->assertOk();
        $sessionId = session()->getId();
        $token = session()->token();
        $this->put(route($role.'.profile.password'), [
            'current_password' => 'Original123!', 'password' => 'Changed123!',
            'password_confirmation' => 'Changed123!', 'user_id' => $other->id,
        ])->assertRedirect(route($role.'.profile.show'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Changed123!', $user->fresh()->password));
        $this->assertTrue(Hash::check('Original123!', $other->fresh()->password));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($token, session()->token());
        $this->get(route($role.'.profile.show'))->assertOk();
    }

    #[DataProvider('roles')]
    public function test_wrong_current_password_changes_nothing_and_is_not_flashed(string $role): void
    {
        $user = $this->account($role);
        $hash = $user->password;
        $response = $this->actingAs($user)->from(route($role.'.profile.show'))
            ->put(route($role.'.profile.password'), [
                'current_password' => 'Wrong123!', 'password' => 'Changed123!',
                'password_confirmation' => 'Changed123!',
            ])->assertRedirect(route($role.'.profile.show'))
            ->assertSessionHasErrors(['current_password' => 'The current password is incorrect.'], null, 'password');
        $this->assertSame($hash, $user->fresh()->password);
        foreach (['current_password', 'password', 'password_confirmation'] as $field) {
            $response->assertSessionMissing('_old_input.'.$field);
        }
        $this->get(route($role.'.profile.show'))->assertSee('The current password is incorrect.')
            ->assertDontSee('value="Wrong123!"', false)->assertDontSee('value="Changed123!"', false);
    }

    #[DataProvider('invalidPasswords')]
    public function test_password_policy_and_confirmation(string $role, string $password, string $confirmation): void
    {
        $user = $this->account($role);
        $hash = $user->password;
        $this->actingAs($user)->put(route($role.'.profile.password'), [
            'current_password' => 'Original123!', 'password' => $password, 'password_confirmation' => $confirmation,
        ])->assertSessionHasErrors('password', null, 'password');
        $this->assertSame($hash, $user->fresh()->password);
    }

    public static function invalidPasswords(): array
    {
        $cases = [];
        foreach (['admin', 'teacher', 'student'] as $role) {
            $cases[] = [$role, 'short', 'short'];
            $cases[] = [$role, 'LongPassword', 'DifferentPassword'];
        }

        return $cases;
    }

    public function test_password_attempts_are_throttled(): void
    {
        $user = $this->account('student');
        $this->actingAs($user);
        for ($i = 0; $i < 6; $i++) {
            $this->putJson(route('student.profile.password'), [
                'current_password' => 'wrong', 'password' => 'Changed123!', 'password_confirmation' => 'Changed123!',
            ])->assertUnprocessable();
        }
        $this->putJson(route('student.profile.password'), [])->assertStatus(429);
    }

    public function test_student_school_and_login_records_have_no_update_endpoint(): void
    {
        $user = $this->account('student');
        $before = $user->student->getAttributes();
        $this->actingAs($user)->put('/student/profile', [
            'lrn_no' => '999999999999', 'section' => 'Hacked', 'current_level' => 99,
            'firstname' => 'Changed', 'teacher_id' => 999, 'username' => 'changed', 'total_points' => 999,
        ])->assertStatus(405);
        $this->assertSame($before, $user->student->fresh()->getAttributes());
        $this->assertSame($user->username, $user->fresh()->username);
    }

    #[DataProvider('imageFormats')]
    public function test_student_uploads_supported_images_to_own_folder(string $extension): void
    {
        $user = $this->account('student');
        $other = $this->account('student');
        $this->actingAs($user)->post(route('student.profile.photo'), [
            'profile_picture' => UploadedFile::fake()->image('portrait.'.$extension, 20, 20),
            'student_id' => $other->student->id, 'user_id' => $other->id, 'section' => 'Hacked',
        ])->assertRedirect(route('student.profile.show'))->assertSessionHasNoErrors();
        $student = $user->student->fresh();
        $this->assertTrue($student->ownsProfilePicture($student->profile_picture));
        Storage::disk('public')->assertExists($student->profile_picture);
        $this->assertNull($other->student->fresh()->profile_picture);
        $this->assertSame('Section A', $student->section);
        $this->get(route('student.profile.show'))->assertOk()->assertSee(asset('storage/'.$student->profile_picture), false);
    }

    public static function imageFormats(): array
    {
        return [['jpg'], ['jpeg'], ['png'], ['webp']];
    }

    #[DataProvider('badImages')]
    public function test_invalid_uploads_preserve_the_current_photo(string $kind): void
    {
        $user = $this->account('student');
        $old = $this->existingPhoto($user->student);
        $validImage = UploadedFile::fake()->image('valid.jpg');
        $file = match ($kind) {
            'large' => UploadedFile::fake()->image('large.png')->size(2049),
            'text' => UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "not an image";'),
            'gif' => UploadedFile::fake()->image('animation.gif'),
            'svg' => UploadedFile::fake()->createWithContent('photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            'php' => UploadedFile::fake()->createWithContent('image.php', file_get_contents($validImage->getPathname())),
        };
        $this->actingAs($user)->post(route('student.profile.photo'), ['profile_picture' => $file])
            ->assertSessionHasErrors('profile_picture', null, 'photo');
        $this->assertSame($old, $user->student->fresh()->profile_picture);
        Storage::disk('public')->assertExists($old);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public static function badImages(): array
    {
        return [['large'], ['text'], ['gif'], ['svg'], ['php']];
    }

    private function existingPhoto(Student $student): string
    {
        $path = 'profile_pictures/students/'.$student->id.'/'.str_repeat('a', 40).'.jpg';
        Storage::disk('public')->put($path, 'fixture');
        $student->update(['profile_picture' => $path]);

        return $path;
    }

    public function test_photo_replacement_removal_and_default_avatar(): void
    {
        $user = $this->account('student');
        $old = $this->existingPhoto($user->student);
        $this->actingAs($user)->post(route('student.profile.photo'), [
            'profile_picture' => UploadedFile::fake()->image('new.png'),
        ])->assertSessionHasNoErrors();
        $new = $user->student->fresh()->profile_picture;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
        $this->delete(route('student.profile.photo.destroy'))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($new);
        $this->assertNull($user->student->fresh()->profile_picture);
        $this->get(route('student.profile.show'))->assertSee('AR')->assertDontSee('data-profile-photo', false);
        $this->delete(route('student.profile.photo.destroy'))->assertSessionHasNoErrors();
    }

    public function test_missing_photo_file_falls_back_without_a_broken_image(): void
    {
        $user = $this->account('student');
        $user->student->update(['profile_picture' => 'profile_pictures/students/'.$user->student->id.'/'.str_repeat('a', 40).'.jpg']);
        $this->actingAs($user)->get(route('student.profile.show'))->assertOk()
            ->assertSee('AR')->assertDontSee('data-profile-photo', false);
    }

    #[DataProvider('unsafePaths')]
    public function test_removal_cannot_delete_unmanaged_or_another_students_files(string $kind): void
    {
        $user = $this->account('student');
        $other = $this->account('student');
        $path = match ($kind) {
            'other' => $this->existingPhoto($other->student),
            'default' => 'avatars/default.png',
            'outside' => 'documents/keep.txt',
            'traversal' => 'profile_pictures/students/'.$user->student->id.'/../../../documents/keep.txt',
        };
        $target = $kind === 'traversal' ? 'documents/keep.txt' : $path;
        Storage::disk('public')->put($target, 'keep');
        $user->student->update(['profile_picture' => $path]);
        $this->actingAs($user)->delete(route('student.profile.photo.destroy'), ['student_id' => $other->student->id])
            ->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($target);
        $this->assertNull($user->student->fresh()->profile_picture);
    }

    public static function unsafePaths(): array
    {
        return [['other'], ['default'], ['outside'], ['traversal']];
    }

    public function test_failed_database_save_retains_old_photo_and_removes_new_upload(): void
    {
        $user = $this->account('student');
        $old = $this->existingPhoto($user->student);
        Event::listen('eloquent.updating: '.Student::class, function () {
            throw new \RuntimeException('Simulated database failure.');
        });
        $this->actingAs($user)->post(route('student.profile.photo'), [
            'profile_picture' => UploadedFile::fake()->image('new.jpg'),
        ])->assertServerError();
        $this->assertSame($old, $user->student->fresh()->profile_picture);
        Storage::disk('public')->assertExists($old);
        $this->assertSame([$old], Storage::disk('public')->allFiles());
    }

    public function test_teacher_sees_only_assigned_students_photos(): void
    {
        $studentUser = $this->account('student');
        $student = $studentUser->student;
        $path = $this->existingPhoto($student);
        $teacherUser = $student->teacher->user;
        $this->actingAs($teacherUser)->get(route('teacher.students.show', $student))
            ->assertOk()->assertSee(asset('storage/'.$path), false);
        $other = $this->account('teacher');
        $this->actingAs($other)->get(route('teacher.students.show', $student))->assertNotFound();
        $this->post(route('student.profile.photo'), ['profile_picture' => UploadedFile::fake()->image('x.jpg')])->assertForbidden();
        $this->delete(route('student.profile.photo.destroy'))->assertForbidden();
        $this->assertSame($path, $student->fresh()->profile_picture);
    }

    public function test_admin_teacher_detail_has_real_counts_and_scoped_paginated_students(): void
    {
        $admin = $this->account('admin');
        $student = $this->account('student')->student;
        $teacher = $student->teacher;
        $otherTeacher = $this->account('teacher')->teacher;
        Activity::create(['teacher_id' => $teacher->id, 'activity_name' => 'Published', 'is_published' => true]);
        Activity::create(['teacher_id' => $teacher->id, 'activity_name' => 'Draft', 'is_published' => false]);
        Activity::create(['teacher_id' => $otherTeacher->id, 'activity_name' => 'Other', 'is_published' => true]);
        DB::table('evaluations')->insert(['teacher_id' => $teacher->id]);
        $this->actingAs($admin)->get(route('admin.teachers.show', $teacher))
            ->assertOk()->assertSee('Account Status')->assertSee('Active')->assertSee('012345678901')
            ->assertSee('No Data')->assertViewHas('teacher', fn ($row) =>
                $row->students_count === 1 && $row->activities_count === 2
                && $row->published_activities_count === 1 && $row->evaluations_count === 1)
            ->assertViewHas('assignedStudents', fn ($rows) => $rows->total() === 1 && $rows->perPage() === 10);
        $this->get(route('admin.teachers'))->assertOk()
            ->assertSee(route('admin.teachers.show', $teacher), false)
            ->assertSee(route('admin.teachers.edit', $teacher), false)
            ->assertSee(route('admin.teachers.delete', $teacher), false);
        $this->get(route('admin.teachers.create'))->assertOk();
        $this->get(route('admin.teachers.edit', $teacher))->assertOk();
    }

    #[DataProvider('roles')]
    public function test_role_boundaries_for_self_service_and_admin_detail(string $role): void
    {
        $user = $this->account($role);
        $teacher = $this->account('teacher')->teacher;
        $this->actingAs($user);
        foreach (array_diff(['admin', 'teacher', 'student'], [$role]) as $otherRole) {
            $this->get(route($otherRole.'.profile.show'))->assertForbidden();
            $this->put(route($otherRole.'.profile.password'), [])->assertForbidden();
            if ($otherRole !== 'student') {
                $this->put(route($otherRole.'.profile.update'), [])->assertForbidden();
            }
        }
        if ($role !== 'admin') {
            $this->get(route('admin.teachers.show', $teacher))->assertForbidden();
        }
    }

    public function test_existing_admin_teacher_edit_action_still_updates_and_logs(): void
    {
        $admin = $this->account('admin');
        $teacher = $this->account('teacher')->teacher;
        $this->actingAs($admin)->put(route('admin.teachers.update', $teacher), [
            'firstname' => 'Edited', 'lastname' => 'Teacher', 'school_name' => 'Updated School',
            'username' => 'edited_teacher', 'email' => 'edited@example.test',
        ])->assertRedirect(route('admin.teachers'))->assertSessionHasNoErrors();
        $this->assertSame('Edited', $teacher->fresh()->firstname);
        $this->assertSame('edited_teacher', $teacher->user->fresh()->username);
        $this->assertTrue(Hash::check('Original123!', $teacher->user->fresh()->password));
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'EDIT_TEACHER')->count());
    }

    public function test_existing_admin_teacher_delete_action_still_deletes_only_target_account(): void
    {
        $admin = $this->account('admin');
        $teacher = $this->account('teacher')->teacher;
        $other = $this->account('teacher');
        $this->actingAs($admin)->delete(route('admin.teachers.delete', $teacher))
            ->assertRedirect()->assertSessionHas('success', 'Teacher deleted successfully.');
        $this->assertNull(User::find($teacher->user_id));
        $this->assertNotNull($other->fresh());
        $this->assertNotNull($admin->fresh());
    }

    public function test_guest_profile_routes_require_login(): void
    {
        foreach (['admin', 'teacher', 'student'] as $role) {
            $this->get(route($role.'.profile.show'))->assertRedirect(route('login'));
            $this->put(route($role.'.profile.password'), [])->assertRedirect(route('login'));
        }
        $this->post(route('student.profile.photo'), [])->assertRedirect(route('login'));
        $this->delete(route('student.profile.photo.destroy'))->assertRedirect(route('login'));
    }
}
