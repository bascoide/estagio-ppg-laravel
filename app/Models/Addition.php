<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addition extends Model
{
    protected $table = 'addition';

    protected $fillable = ['final_document_id', 'name', 'path'];

    public function finalDocument()
    {
        return $this->belongsTo(FinalDocument::class, 'final_document_id');
    }
}
