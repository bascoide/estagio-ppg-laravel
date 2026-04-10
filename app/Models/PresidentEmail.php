<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresidentEmail extends Model
{
    protected $table = 'president_emails';
    public $timestamps = false;

    protected $fillable = ['email'];
}
