<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class GameRound extends Model
{
    protected $fillable = [
        'game_session_id', 'student_id', 'word_or_passage',
        'recording_path', 'ml_score', 'teacher_score',
        'final_score', 'damage_dealt', 'status'
    ];

    public function session() { return $this->belongsTo(GameSession::class, 'game_session_id'); }
    public function student() { return $this->belongsTo(Student::class); }
}