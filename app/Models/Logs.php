<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $table = 'logs';
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'name', 'final_document_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function finalDocument()
    {
        return $this->belongsTo(FinalDocument::class, 'final_document_id');
    }
}
