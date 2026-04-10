<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'field';
    public $timestamps = false;

    protected $fillable = ['document_id', 'name', 'data_type'];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function fieldValues()
    {
        return $this->hasMany(FieldValue::class, 'field_id');
    }
}
