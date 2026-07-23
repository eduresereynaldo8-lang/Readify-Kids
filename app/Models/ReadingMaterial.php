<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReadingMaterial extends Model
{
    protected $fillable = [
        'teacher_id', 'title', 'content',
        'difficulty_level', 'level', 'file_path'
    ];

    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }

    public function activities() {
        return $this->hasMany(Activity::class);
    }
}