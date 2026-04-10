<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmittedPlan extends Model
{
    protected $table = 'submitted_plans';

    protected $fillable = ['path', 'verified'];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public function finalDocument()
    {
        return $this->hasOne(FinalDocument::class, 'plan_id');
    }
}
