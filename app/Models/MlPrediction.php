<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MlPrediction extends Model
{
    protected $fillable = [
        'student_id', 'activity_id', 'predicted_level',
        'prediction_confidence', 'recommended_difficulty'
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}