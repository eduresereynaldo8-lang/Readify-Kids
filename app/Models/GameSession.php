<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'student_id', 'activity_id', 'enemy_id',
        'enemy_current_hp', 'enemy_max_hp', 'total_damage',
        'rounds_played', 'status', 'points_earned'
    ];

    public function student()   { return $this->belongsTo(Student::class); }
    public function activity()  { return $this->belongsTo(Activity::class); }
    public function enemy()     { return $this->belongsTo(Enemy::class); }
    public function rounds()    { return $this->hasMany(GameRound::class); }
}