<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityWordBank extends Model
{
    protected $table = 'activity_word_bank'; // add this line

    protected $fillable = ['activity_id', 'word', 'order', 'type'];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}