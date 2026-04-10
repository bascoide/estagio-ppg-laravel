<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeCourse extends Model
{
    protected $table = 'type_course';
    public $timestamps = false;

    protected $fillable = ['name'];

    public function courses()
    {
        return $this->hasMany(Course::class, 'type_course_id');
    }
}
