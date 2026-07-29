# TODO - Fix Activity Teacher Scoping

## Problem
Students can see activities created by **any** teacher instead of only their own teacher's activities.

## Plan
Add `->where('teacher_id', $student->teacher_id)` filter to all student-facing activity queries.

## Steps

### 1. `ActivityController.php`
- [x] `studentIndex()` - Add teacher_id filter
- [x] `studentShow()` - Add teacher_id filter + scope
- [x] `submit()` - Add teacher_id filter when fetching activity

### 2. `VoiceRecordingController.php`
- [x] `index()` - Add teacher_id filter
- [x] `show()` - Add teacher_id filter
- [x] `upload()` - Add teacher_id filter when fetching activity

### 3. `GameController.php`
- [x] `index()` - Add teacher_id filter
- [x] `start()` - Add teacher_id filter when fetching activity

### 4. `DashboardController.php`
- [x] `studentDashboard()` - Add teacher_id filter

### 5. `Activity Model`
- [x] `$fillable` - Added `battle_mode` to fix battle activities not saving the flag (was being silently discarded by mass-assignment protection)

### 6. Testing
- [ ] Verify Teacher 1's students only see Teacher 1's activities
- [ ] Verify Teacher 2's students only see Teacher 2's activities
- [ ] Verify new teacher's students see empty state
- [ ] Verify battle activities now appear in the student battle arena page

