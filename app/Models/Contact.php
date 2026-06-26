<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TranslateAttributes;

class Contact extends Model
{
    use TranslateAttributes;
    protected $fillable = ['name', 'email', 'message', 'status'];
}
