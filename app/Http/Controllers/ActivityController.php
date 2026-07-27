<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\ActivityWordBank;
use App\Models\ReadingMaterial;

class ActivityController extends Controller
{
    // ──────────────────────────────────────────────
    // LIST, SHOW, DELETE (shared by both types)
    // ──────────────────────────────────────────────

    public function index()
    {
        $teacher    = auth()->user()->teacher;
        $activities = Activity::where('teacher_id', $teacher->id)->latest()->get();

        $total       = $activities->count();
        $published   = $activities->where('is_published', true)->count();
        $drafts      = $activities->where('is_published', false)->count();
        $completions = $activities->sum(fn($a) => $a->results->count());

        return view('teacher.activities.index', compact(
            'activities', 'total', 'published', 'drafts', 'completions'
        ));
    }

    public function show($id)
    {
        $teacher  = auth()->user()->teacher;
        $activity = Activity::where('teacher_id', $teacher->id)
                    ->with(['wordBank', 'results.student', 'readingMaterial'])
                    ->findOrFail($id);

        // Route to the correct show view based on type
        if ($activity->activity_type === 'Read Aloud') {
            return view('teacher.activities.show', compact('activity'));
        }
        return view('teacher.activities.show', compact('activity'));
    }

    public function destroy($id)
    {
        $teacher  = auth()->user()->teacher;
        $activity = Activity::where('teacher_id', $teacher->id)->findOrFail($id);
        $activity->delete();
        return redirect()->route('teacher.activities.index')
               ->with('success', 'Activity deleted successfully!');
    }

    // ──────────────────────────────────────────────
    // READ ALOUD — Create
    // ──────────────────────────────────────────────

    public function createReadAloud()
    {
        return view('teacher.activities.create-readaloud');
    }

    public function storeReadAloud(Request $request)
    {
        $request->validate([
            'activity_name'    => 'required|string|max:255',
            'description'      => 'nullable|string|max:500',
            'level'            => 'required|integer|min:1|max:5',
            'difficulty_level' => 'required|string',
            'duration_minutes' => 'required|integer|min:1',
            'points_reward'    => 'required|integer|min:1',
            'passage'          => 'required|string',
        ]);

        $activity = Activity::create([
            'teacher_id'          => auth()->user()->teacher->id,
            'activity_name'       => $request->activity_name,
            'description'         => $request->description,
            'activity_type'       => 'Read Aloud',
            'level'               => $request->level,
            'difficulty_level'    => $request->difficulty_level,
            'duration_minutes'    => $request->duration_minutes,
            'points_reward'       => $request->points_reward,
            'is_published'        => $request->has('is_published') ? $request->boolean('is_published') : !$request->has('save_draft'),
            'allow_reattempt'     => $request->has('allow_reattempt'),
            'battle_mode'         => false,
        ]);

        // Save passage to reading_materials and link to activity
        $material = ReadingMaterial::create([
            'teacher_id'       => auth()->user()->teacher->id,
            'title'            => $request->activity_name,
            'content'          => $request->passage,
            'difficulty_level' => $request->difficulty_level,
            'level'            => $request->level,
        ]);
        $activity->update(['reading_material_id' => $material->id]);

        return redirect()->route('teacher.activities.index')
               ->with('success', 'Read Aloud activity ' . ($request->has('is_published') ? 'published' : 'saved as draft') . ' successfully!');
    }

    // ──────────────────────────────────────────────
    // READ ALOUD — Edit / Update
    // ──────────────────────────────────────────────

    public function editReadAloud($id)
    {
        $teacher  = auth()->user()->teacher;
        $activity = Activity::where('teacher_id', $teacher->id)
                    ->with('readingMaterial')
                    ->where('activity_type', 'Read Aloud')
                    ->findOrFail($id);
        return view('teacher.activities.edit-readaloud', compact('activity'));
    }

    public function updateReadAloud(Request $request, $id)
    {
        $teacher  = auth()->user()->teacher;
        $activity = Activity::where('teacher_id', $teacher->id)
                    ->where('activity_type', 'Read Aloud')
                    ->findOrFail($id);

        $request->validate([
            'activity_name'    => 'required|string|max:255',
            'description'      => 'nullable|string|max:500',
            'level'            => 'required|integer|min:1|max:5',
            'difficulty_level' => 'required|string',
            'duration_minutes' => 'required|integer|min:1',
            'points_reward'    => 'required|integer|min:1',
            'passage'          => 'nullable|string',
        ]);

        $activity->update([
            'activity_name'      => $request->activity_name,
            'description'        => $request->description,
            'level'              => $request->level,
            'difficulty_level'   => $request->difficulty_level,
            'duration_minutes'   => $request->duration_minutes,
            'points_reward'      => $request->points_reward,
            'is_published'       => $request->has('is_published'),
            'allow_reattempt'    => $request->has('allow_reattempt'),
        ]);

        // Update reading material
        if ($request->filled('passage')) {
            if ($activity->readingMaterial) {
                $activity->readingMaterial->update([
                    'content'          => $request->passage,
                    'difficulty_level' => $request->difficulty_level,
                    'level'            => $request->level,
                ]);
            } else {
                $material = ReadingMaterial::create([
                    'teacher_id'       => auth()->user()->teacher->id,
                    'title'            => $request->activity_name,
                    'content'          => $request->passage,
                    'difficulty_level' => $request->difficulty_level,
                    'level'            => $request->level,
                ]);
                $activity->update(['reading_material_id' => $material->id]);
            }
        }

        return redirect()->route('teacher.activities.index')
               ->with('success', 'Read Aloud activity updated successfully!');
    }

    // ──────────────────────────────────────────────
    // BATTLE MODE — Create
    // ──────────────────────────────────────────────

    public function createBattle()
    {
        return view('teacher.activities.create-battle');
    }

    public function storeBattle(Request $request)
    {
        $request->validate([
            'activity_name'    => 'required|string|max:255',
            'description'      => 'nullable|string|max:500',
            'level'            => 'required|integer|min:1|max:5',
            'difficulty_level' => 'required|string',
            'duration_minutes' => 'required|integer|min:1',
            'points_reward'    => 'required|integer|min:1',
            'battle_words'     => 'nullable|array',
            'battle_words.*'   => 'nullable|string|max:1000',
        ]);

        // Determine published status:
        // - If "Publish Activity" button was clicked (no is_published field) OR checkbox was checked → published
        // - If "Save as Draft" button was clicked (is_published=0) → draft
        // - If checkbox is checked → published regardless of button
        $isPublished = $request->has('is_published') 
            ? $request->boolean('is_published')
            : !$request->has('save_draft');

        $activity = Activity::create([
            'teacher_id'          => auth()->user()->teacher->id,
            'activity_name'       => $request->activity_name,
            'description'         => $request->description,
            'activity_type'       => 'Word Game',
            'level'               => $request->level,
            'difficulty_level'    => $request->difficulty_level,
            'duration_minutes'    => $request->duration_minutes,
            'points_reward'       => $request->points_reward,
            'is_published'        => $isPublished,
            'allow_reattempt'     => $request->has('allow_reattempt'),
            'battle_mode'         => true,
        ]);

        // Save battle words/paragraphs
        if ($request->filled('battle_words')) {
            foreach (array_filter($request->battle_words) as $index => $word) {
                $type = strlen($word) <= 20
                    ? 'word'
                    : (str_word_count($word) <= 4 ? 'phrase' : 'paragraph');

                ActivityWordBank::create([
                    'activity_id' => $activity->id,
                    'word'        => trim($word),
                    'order'       => $index,
                    'type'        => $type,
                ]);
            }
        }

        return redirect()->route('teacher.activities.index')
               ->with('success', 'Battle activity ' . ($request->has('is_published') ? 'published' : 'saved as draft') . ' successfully!');
    }

    // ──────────────────────────────────────────────
    // BATTLE MODE — Edit / Update
    // ──────────────────────────────────────────────

    public function editBattle($id)
    {
        $teacher  = auth()->user()->teacher;
        $activity = Activity::where('teacher_id', $teacher->id)
                    ->with('wordBank')
                    ->where('battle_mode', true)
                    ->findOrFail($id);
        return view('teacher.activities.edit-battle', compact('activity'));
    }

    public function updateBattle(Request $request, $id)
    {
        $teacher  = auth()->user()->teacher;
        $activity = Activity::where('teacher_id', $teacher->id)
                    ->where('battle_mode', true)
                    ->findOrFail($id);

        $request->validate([
            'activity_name'    => 'required|string|max:255',
            'description'      => 'nullable|string|max:500',
            'level'            => 'required|integer|min:1|max:5',
            'difficulty_level' => 'required|string',
            'duration_minutes' => 'required|integer|min:1',
            'points_reward'    => 'required|integer|min:1',
            'battle_words'     => 'nullable|array',
            'battle_words.*'   => 'nullable|string|max:1000',
        ]);

        $activity->update([
            'activity_name'      => $request->activity_name,
            'description'        => $request->description,
            'level'              => $request->level,
            'difficulty_level'   => $request->difficulty_level,
            'duration_minutes'   => $request->duration_minutes,
            'points_reward'      => $request->points_reward,
            'is_published'       => $request->has('is_published'),
            'allow_reattempt'    => $request->has('allow_reattempt'),
        ]);

        // Update word bank
        if ($request->filled('battle_words')) {
            $activity->wordBank()->delete();
            foreach (array_filter($request->battle_words) as $index => $word) {
                $type = strlen($word) <= 20
                    ? 'word'
                    : (str_word_count($word) <= 4 ? 'phrase' : 'paragraph');

                ActivityWordBank::create([
                    'activity_id' => $activity->id,
                    'word'        => trim($word),
                    'order'       => $index,
                    'type'        => $type,
                ]);
            }
        }

        return redirect()->route('teacher.activities.index')
               ->with('success', 'Battle activity updated successfully!');
    }

    // ──────────────────────────────────────────────
    // STUDENT SIDE (unchanged)
    // ──────────────────────────────────────────────

    public function studentIndex()
{
    $student = auth()->user()->student;

    $activities = Activity::where('is_published', true)
                  ->where('level', '<=', $student->current_level)
                  ->with(['results' => fn($q) => $q->where('student_id', $student->id)])
                  ->get();

    $grouped = $activities->groupBy('activity_type');

    return view('student.activities.index', compact('activities', 'grouped'));
}

    public function studentShow($id)
    {
        $activity = Activity::where('is_published', true)
                    ->with('wordBank')
                    ->findOrFail($id);
        return view('student.activities.show', compact('activity'));
    }

    public function submit(Request $request, $id)
    {
        $student  = auth()->user()->student;
        $activity = Activity::findOrFail($id);

        \App\Models\ActivityResult::create([
            'student_id'   => $student->id,
            'activity_id'  => $activity->id,
            'score'        => $request->score ?? 0,
            'mistakes'     => $request->mistakes ?? 0,
            'time_spent'   => $request->time_spent ?? 0,
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        $student->increment('total_points', $activity->points_reward);
        $student->checkAndUpdateLevel();

        return redirect()->route('student.activities.index')
               ->with('success', 'Activity submitted!');
    }
}
