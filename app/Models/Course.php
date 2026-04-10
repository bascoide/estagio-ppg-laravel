<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'course';
    public $timestamps = false;

    protected $fillable = ['name', 'type_course_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function typeCourse()
    {
        return $this->belongsTo(TypeCourse::class, 'type_course_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'course_id');
    }
}
