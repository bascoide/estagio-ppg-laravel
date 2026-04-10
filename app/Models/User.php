<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'user';

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin',
        'course_id',
        'verification_code',
        'verified',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'admin'    => 'boolean',
        'verified' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function finalDocuments()
    {
        return $this->hasMany(FinalDocument::class, 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(Logs::class, 'user_id');
    }
}
