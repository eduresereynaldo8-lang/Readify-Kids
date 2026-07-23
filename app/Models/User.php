<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'username', 'email', 'password', 'role', 'status'
    ];

    protected $hidden = ['password'];

    public function teacher() {
        return $this->hasOne(Teacher::class);
    }

    public function student() {
        return $this->hasOne(Student::class);
    }
}