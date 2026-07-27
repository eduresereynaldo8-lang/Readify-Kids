# Auto Level-Up System Implementation

## Steps

- [x] Plan approved by user
- [x] Create TODO.md
- [x] **Step 1**: Add `checkAndUpdateLevel()` method to `app/Models/Student.php`
- [x] **Step 2**: Update `app/Http/Controllers/ActivityController.php` — call level check after points increment
- [x] **Step 3**: Update `app/Http/Controllers/EvaluationController.php` — call level check after points increment
- [x] **Step 4**: Update `app/Http/Controllers/GameController.php` — call level check after points increment (both `submitRound` and `overrideScore`)

## Result

All 4 files have been updated. The auto level-up system now works as follows:

- `Student::checkAndUpdateLevel()` loop checks if `total_points >= current_level * 500` and increments level until points are below the next threshold
- Called automatically after every points award across all 3 controllers (4 locations total)

