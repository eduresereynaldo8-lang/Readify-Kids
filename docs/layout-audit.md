# Readify Kids layout consistency audit

All 10 Admin views, all 18 Teacher views, and all 8 normal Student views now extend their role layout. The three role layouts inherit the existing layouts.dashboard shell. The fullscreen Student battle arena is the only standalone role view and is unchanged.

## Cause and cleanup

- Normal pages already extended layouts.admin, layouts.teacher, or layouts.student; those layouts still contained the old independent shells. The three dashboards bypassed them and directly extended layouts.dashboard.
- The old Admin layout owned the black/red sidebar, red active links, red role badge and red logout styling. Those definitions and --admin-primary, --admin-dark, --sidebar-bg were removed with the old shell.
- All roles now share the same logo, navy sidebar, blue active navigation, topbar, profile chip, content spacing, and mobile menu script. Active links cover child routes, with separate Read Aloud and Battle creation entries.
- Bootstrap CSS and its bundle load once in the shared shell. Style and script stacks are supported. Existing content styles were extracted into public/css/readify-pages.css and scoped by role and normal-page content.
- Admin primary actions, avatar accents, and active pagination use blue/purple. Delete, inactive, failed, low-score, required-field and other legitimate red states remain. Existing report chart colors remain.
- Read Aloud show previously contained a complete standalone HTML document. It now extends layouts.student; its original scene is scoped to a content panel and its unchanged recording/upload script is in the scripts stack.
- Normal child views had no independent sidebar/navigation/mobile-menu blocks to remove. Old duplicate systems were in the three role layouts. No role views referenced layouts.admin-old, admin.layout, teacher.layout, or student.layout.
- Tables without wrappers received horizontal scroll regions. Teacher activity filter controls now wrap on small screens.
- Corrected an unclosed action-row div and escaped username interpolation in Admin tables. The Teacher activity empty-state link now uses the existing teacher.activities.create.readaloud route. No route names or controller actions were changed.

## Files changed in this layout task

- docs/layout-audit.md
- public/css/readify-pages.css
- resources/views/admin/activities.blade.php
- resources/views/admin/dashboard.blade.php
- resources/views/admin/evaluations.blade.php
- resources/views/admin/logs.blade.php
- resources/views/admin/students.blade.php
- resources/views/admin/teachers.blade.php
- resources/views/admin/teachers_create.blade.php
- resources/views/admin/teachers_edit.blade.php
- resources/views/layouts/admin.blade.php
- resources/views/layouts/dashboard.blade.php
- resources/views/layouts/student.blade.php
- resources/views/layouts/teacher.blade.php
- resources/views/student/dashboard.blade.php
- resources/views/student/readaloud/show.blade.php
- resources/views/teacher/activities/index.blade.php
- resources/views/teacher/activities/show.blade.php
- resources/views/teacher/dashboard.blade.php
- resources/views/teacher/leaderboard.blade.php
- resources/views/teacher/logs.blade.php
- resources/views/teacher/students/index.blade.php
- resources/views/teacher/students/show.blade.php

The earlier dashboard-controller and DashboardMetrics changes visible in git status belong to the preceding dashboard redesign. This layout task does not modify controllers, routes, authentication, schema, or .env.

## Every role view inspected

The last column records whether the view had its own DOCTYPE/html/head/body before cleanup. None of the normal child views contains duplicate navigation or legacy shell CSS after cleanup.

| View | Before | After | Full HTML before |
| --- | --- | --- | --- |
| admin/activities.blade.php | layouts.admin | layouts.admin | No |
| admin/dashboard.blade.php | layouts.dashboard | layouts.admin | No |
| admin/evaluations.blade.php | layouts.admin | layouts.admin | No |
| admin/logs.blade.php | layouts.admin | layouts.admin | No |
| admin/reports.blade.php | layouts.admin | layouts.admin | No |
| admin/students.blade.php | layouts.admin | layouts.admin | No |
| admin/students_view.blade.php | layouts.admin | layouts.admin | No |
| admin/teachers.blade.php | layouts.admin | layouts.admin | No |
| admin/teachers_create.blade.php | layouts.admin | layouts.admin | No |
| admin/teachers_edit.blade.php | layouts.admin | layouts.admin | No |
| teacher/dashboard.blade.php | layouts.dashboard | layouts.teacher | No |
| teacher/leaderboard.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/logs.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/progress.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/create-battle.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/create-readaloud.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/create.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/edit-battle.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/edit-readaloud.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/edit.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/index.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/activities/show.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/evaluations/index.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/evaluations/show.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/students/create.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/students/edit.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/students/index.blade.php | layouts.teacher | layouts.teacher | No |
| teacher/students/show.blade.php | layouts.teacher | layouts.teacher | No |
| student/dashboard.blade.php | layouts.dashboard | layouts.student | No |
| student/leaderboard.blade.php | layouts.student | layouts.student | No |
| student/progress.blade.php | layouts.student | layouts.student | No |
| student/activities/index.blade.php | layouts.student | layouts.student | No |
| student/activities/show.blade.php | layouts.student | layouts.student | No |
| student/game/battle.blade.php | Standalone | Standalone | Yes |
| student/game/index.blade.php | layouts.student | layouts.student | No |
| student/readaloud/index.blade.php | layouts.student | layouts.student | No |
| student/readaloud/show.blade.php | Standalone | layouts.student | Yes |

## Verification

- /admin/teachers was migrated and visually checked against /admin/dashboard first: matching navy sidebar, logo, blue active state, topbar, profile, spacing, and background; Teacher Management controls remain intact.
- 33 normal routes rendered successfully using existing local data. Rendered form action/method combinations and navigation links matched registered routes. Each rendered page has one HTML document, one sidebar, one active navigation item, and one Bootstrap bundle.
- Browser checks covered those 33 pages at 1536, 768, 390, and 320 pixels. The initial Teacher Activities filter overflow was corrected; focused rechecks of six affected/interactive pages passed with no overflow or JavaScript exceptions. The full browser run reported no console errors.
- Checked mobile menu opening and Escape closing, activity filtering, battle word add/remove controls, evaluation-star input updates, recording function/CSRF hooks, and Bootstrap alert dismissal. Prior shared-menu checks also cover overlay closing and desktop/mobile resize.
- All original child-page form opening tags/actions, CSRF directives, method overrides, validation directives, element IDs, and inline page scripts match the pre-cleanup snapshot. Existing scripts and recording/upload logic are unchanged.
- Final scan found no standalone document or legacy Admin shell variables in normal role views. Battle Mode remained byte-for-byte unchanged.
- git diff --check passed. php artisan optimize:clear completed successfully, including view cache clearing.

## Existing issues requiring separate review

1. teacher.students.show cannot currently render through its controller: StudentController::show eager-loads badges.badge, but Student::badges returns Badge models without a badge relationship. This existing backend error occurs before Blade rendering. Its view uses layouts.teacher, but the route could not be visually verified. Repair requires a separate controller change outside the requested Blade-only scope.
2. teacher/activities/create.blade.php and teacher/activities/edit.blade.php are unused legacy templates. They already extend layouts.teacher but reference missing teacher.activities.store/update routes. No current route/controller renders them; the routed Read Aloud and Battle create/edit pages passed. Decide separately whether to retire those templates or restore their intended backend.
3. No actual form submissions, account changes, deletion actions, microphone capture, or recording uploads were performed during layout testing. Recording code is preserved and its hooks were verified; real device/network recording remains a manual integration check.

## Commands and assets

No extra assets, migrations, dependency installs, or build commands are required. optimize:clear has already run locally. After deploying these view/assets changes elsewhere, run php artisan optimize:clear there and refresh the browser.
