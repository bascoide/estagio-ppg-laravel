<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Professor extends Model
{
    protected $table = 'professor';
    public $timestamps = false;

    protected $fillable = ['name'];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'professor_course', 'professor_id', 'course_id')
            ->withPivot('intern_id')
            ->withTimestamps();
    }
}
