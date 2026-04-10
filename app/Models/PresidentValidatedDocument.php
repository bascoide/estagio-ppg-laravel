<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresidentValidatedDocument extends Model
{
    protected $table = 'president_validated_documents';
    public $timestamps = false;

    protected $fillable = ['uuid', 'final_document_id', 'is_verified', 'is_validated'];

    protected $casts = [
        'is_verified'  => 'boolean',
        'is_validated' => 'boolean',
    ];

    public function finalDocument()
    {
        return $this->belongsTo(FinalDocument::class, 'final_document_id');
    }
}
