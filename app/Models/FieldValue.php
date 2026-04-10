<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldValue extends Model
{
    protected $table = 'field_value';
    public $timestamps = false;

    protected $fillable = ['document_id', 'user_id', 'field_id', 'value', 'final_document_id'];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function finalDocument()
    {
        return $this->belongsTo(FinalDocument::class, 'final_document_id');
    }
}
