<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class LeaderboardController extends Controller
{
    // Teacher leaderboard
    public function index()
    {
        $teacher  = auth()->user()->teacher;
        $students = Student::where('teacher_id', $teacher->id)
                    ->with('activityResults')
                    ->orderByDesc('total_points')
                    ->get();

        $sections = $students->pluck('section')->unique()->filter()->values();

        return view('teacher.leaderboard', compact('students', 'sections'));
    }

    // Student leaderboard
    public function studentIndex()
    {
        $student   = auth()->user()->student;
        $classmates = Student::where('teacher_id', $student->teacher_id)
                     ->orderByDesc('total_points')->get();
        $myRank    = $classmates->search(fn($s) => $s->id === $student->id) + 1;

        return view('student.leaderboard', compact('classmates', 'student', 'myRank'));
    }
}