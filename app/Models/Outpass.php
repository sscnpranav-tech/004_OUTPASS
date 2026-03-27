<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outpass extends Model
{
   protected $fillable = [
        'rollno', 'name', 'gender', 'class', 'house',
        'type', 'from_date', 'to_date', 'from_time', 'to_time',
        'reason', 'status'
    ];
}
