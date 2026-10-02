# Profile management implementation

Implemented Admin, Teacher, and Student self-service profiles, Admin teacher details, and student profile pictures. No Git commands or migrations were run. Current database access during inspection was limited to reading column metadata.

## Data model confirmed

- `users`: username, email, password, role, status, created_at, updated_at.
- `teachers`: user_id, firstname, lastname, school_name, timestamps.
- `students`: user_id, teacher_id, student_number, firstname, lastname, lrn_no, birthday, age, gender, section, current_level, total_points, timestamps.
- No existing image column was found in the live users, teachers, or students tables. Existing relationships and foreign keys are reused.
- Admin has no separate name fields. Age continues using the existing birthday-based accessor.

## Files created

- `app/Http/Controllers/ProfileController.php`: shared profile display, strict self-service role checks, account validation, password endpoint.
- `app/Http/Controllers/AdminProfileController.php`: own account updates.
- `app/Http/Controllers/TeacherProfileController.php`: transactional own user/teacher updates.
- `app/Http/Controllers/StudentProfileController.php`: photo upload/removal; inherited display/password actions.
- `app/Services/UpdateUserPassword.php`: shared password validation, verification, hashing, session rotation.
- `app/Services/StudentProfilePhoto.php`: photo persistence, replacement, removal, safe cleanup.
- `resources/views/profile/show.blade.php`: combined profile/settings page using each role's existing layout.
- `resources/views/components/student-avatar.blade.php`: reusable photo/initials avatar.
- `resources/views/admin/teachers/show.blade.php`: teacher detail, class summary, paginated assigned students.
- `public/css/profile.css`
- `public/js/profile.js`: password visibility controls and failed-image fallback.
- `database/migrations/2026_09_26_120000_add_profile_picture_to_students_table.php`
- `tests/Feature/ProfileManagementTest.php`
- `docs/profile-management.md`: this report.

## Files updated

- `routes/web.php`: profile and teacher-detail routes within existing authenticated role groups.
- `app/Models/Student.php`: profile_picture fillable field, managed-path ownership check, host-aware photo URL accessor.
- `app/Http/Controllers/AdminController.php`: showTeacher action with real aggregates; added the previously missing LogActivity import so existing Edit Teacher saves can log successfully.
- `resources/views/layouts/dashboard.blade.php`: account menu links, student sidebar/account photo, shared profile assets.
- `resources/views/admin/teachers.blade.php`: View / Edit / Delete action order and accessible titles.
- `resources/views/teacher/students/show.blade.php`: student photo in existing profile header.

The individual admin/teacher/student layout entry points already share layouts.dashboard, so no duplicate top bars or role-specific layout changes were needed.

## Routes added

| Method | Path | Route name |
| --- | --- | --- |
| GET | /admin/profile | admin.profile.show |
| PUT | /admin/profile | admin.profile.update |
| PUT | /admin/profile/password | admin.profile.password |
| GET | /teacher/profile | teacher.profile.show |
| PUT | /teacher/profile | teacher.profile.update |
| PUT | /teacher/profile/password | teacher.profile.password |
| GET | /student/profile | student.profile.show |
| PUT | /student/profile/password | student.profile.password |
| POST | /student/profile/photo | student.profile.photo |
| DELETE | /student/profile/photo | student.profile.photo.destroy |
| GET | /admin/teachers/{teacher} | admin.teachers.show |

Profile display and editing share one page. Student school information has no self-service update endpoint.

## Account menus and teacher details

All three account dropdowns show the role account label, My Profile, and POST Log out. Admin's dropdown System reports link was replaced; the sidebar Reports link remains.

Admin teacher details show name, username, email, school, actual users.status, join date, student count, activity count, published count, and evaluation count. Assigned students are scoped to the selected teacher and paginated at 10 per page. Reading status uses the existing completed-activity aggregate. Existing Edit and Delete links remain; delete behavior is unchanged.

## Editable fields

| Role | Can change |
| --- | --- |
| Admin | Own username, email, password |
| Teacher | Own first name, last name, school, username, email, password |
| Student | Own profile picture and password |

Username/email changes enforce uniqueness against users with only the authenticated user's ID excluded. Student names, username, LRN, birthday, age, gender, section, current level, teacher assignment, points, and reading records stay read-only. Requests cannot change roles, ownership, IDs, or statistics.

## Photos

- Laravel public disk: `storage/app/public/profile_pictures/students/{student_id}/{generated_hash}.{extension}`.
- Only the relative path is saved in students.profile_picture.
- Server validation checks uploaded file, image MIME type, allowed extension, valid image dimensions, and maximum 2048 KB. JPG/JPEG, PNG, and WebP are accepted.
- Original filenames are never used for storage.
- Updates lock the student's row; the old image remains until the replacement database update commits.
- Failed database saves remove the new upload and retain the old image.
- Cleanup deletes only generated image filenames inside that student's own managed directory. Other student files, external paths, traversal paths, and defaults are excluded.
- Missing files and browser image-load failures fall back to initials.
- Photos appear on Student My Profile, the student account/sidebar avatar, and Teacher View Student.
- Teacher student visibility retains the existing teacher_id scope. Teachers cannot upload or remove student photos.
- The existing public/storage junction is already correctly linked. URLs use Laravel's asset helper for the current request host instead of a saved localhost URL.

## Passwords and authorization

Every password change verifies the current hash using Hash::check, requires a confirmed new password of at least 8 characters, and stores Hash::make output. Wrong current passwords produce exactly "The current password is incorrect." Password fields are never repopulated or flashed.

Updates resolve the authenticated account; request IDs cannot select a different account. Password verification/save occurs under a row lock. Successful changes preserve authentication and regenerate the session ID and CSRF token. Password endpoints are throttled to six requests per minute.

Existing auth, role middleware, PreventBackHistory, CSRF, and POST logout remain. Because existing role middleware allows admins onto teacher routes, self-service controllers additionally enforce the exact role. Admin teacher details remain admin-only.

## Migration and manual commands

Created, but did not run:

`database/migrations/2026_09_26_120000_add_profile_picture_to_students_table.php`

It adds only a nullable string students.profile_picture. Back up your database, then manually run:

```sh
php artisan migrate
```

The photo upload/removal feature requires this migration. No storage-link command is needed on this machine: public/storage already points to storage/app/public. On another installation without that link, use the normal `php artisan storage:link` setup. Public CSS/JS assets do not require an npm build.

## Verification

- `php artisan test --compact --filter=ProfileManagementTest`: **45 passed, 350 assertions**.
- Final `php artisan test --compact`: **247 passed, 2 failed, 3121 assertions**.
- `node --test tests/JavaScript/read-aloud.test.mjs`: **14 passed**.
- PHP syntax checks for new controllers/services, changed model/controller/routes, test file, and migration passed.
- `node --check public/js/profile.js` passed.

Profile tests cover all roles, menu rendering, editable-field allowlists, unique usernames/emails, forged ownership, password errors/confirmation/throttling/session rotation, supported and rejected images, replacement/removal, missing files, deletion boundaries, database failure cleanup, teacher student access, real teacher summary counts, route precedence, and existing Admin teacher Edit/Delete actions.

SessionSecurityTest, ManualEvaluationWorkflowTest, BattleOralReadingTest, and the other regression suites passed except the two failures below. Tests used explicitly isolated in-memory SQLite schemas and fake storage. No migration commands were executed.

## Remaining limitations and existing inconsistencies

- Two existing StudentManagementTest assertions fail at lines 126 and 209: the unchanged teacher/students/form.blade.php does not render current_level, so the tests cannot find value="7" or name="current_level". This unrelated form and its tests were left unchanged.
- Existing Admin teacher list/status filtering uses email_verified_at, which is absent from the live users table. The new teacher detail page accurately uses users.status; existing list/toggle behavior was not redesigned.
- Automated tests rendered the Blade pages and verified responses. Interactive browser appearance and controls were not manually tested.
- Student photos become usable against the current database after you run the prepared migration.
