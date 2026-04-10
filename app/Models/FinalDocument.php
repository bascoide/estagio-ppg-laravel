<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalDocument extends Model
{
    protected $table = 'final_document';

    protected $fillable = ['user_id', 'pdf_path', 'document_id', 'status', 'plan_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubmittedPlan::class, 'plan_id');
    }

    public function fieldValues()
    {
        return $this->hasMany(FieldValue::class, 'final_document_id');
    }

    public function additions()
    {
        return $this->hasMany(Addition::class, 'final_document_id');
    }
}
