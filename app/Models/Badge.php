<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = [
        'badge_name', 'description', 'badge_icon', 'criteria'
    ];

    public function studentBadges() {
        return $this->hasMany(StudentBadge::class);
    }
}