<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cadets extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Required for the Bulk Upload 'Cadet::create' logic to work.
     */
    protected $fillable = [
        'rollno',
        'name',
        'class',
        'house',
        'gender',
        'is_active',
    ];

    /**
     * Relationship: A Cadet can have many Outpasses.
     */
    public function outpasses()
    {
        return $this->hasMany(Outpass::class);
    }

    /**
     * Scope: Quickly filter active cadets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
