<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Enemy extends Model
{
    protected $fillable = ['name', 'sprite', 'max_hp', 'level', 'description'];

    public function gameSessions() {
        return $this->hasMany(GameSession::class);
    }
}