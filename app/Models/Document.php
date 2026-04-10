<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'document';
    public $timestamps = false;

    protected $fillable = ['docx_path', 'name', 'type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(Field::class, 'document_id');
    }

    public function finalDocuments()
    {
        return $this->hasMany(FinalDocument::class, 'document_id');
    }

    public function typeCourses()
    {
        return $this->belongsToMany(TypeCourse::class, 'document_type_course', 'document_id', 'type_course_id');
    }
}
