<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id', 'firstname', 'lastname', 'school_name'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function students() {
        return $this->hasMany(Student::class);
    }

    public function activities() {
        return $this->hasMany(Activity::class);
    }

    public function readingMaterials() {
        return $this->hasMany(ReadingMaterial::class);
    }

    public function evaluations() {
        return $this->hasMany(Evaluation::class);
    }
}