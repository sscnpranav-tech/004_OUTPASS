<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    /**
     * Explicitly define the table to prevent any Laravel pluralization errors.
     */
    protected $table = 'schedules';

    /**
     * The attributes that are mass assignable.
     * Every single field from our advanced Livewire component is listed here.
     */
    protected $fillable = [
        'type',             // e.g., 'General Outpass', 'Night Outpass'
        'from_date',
        'to_date',
        'from_time',
        'to_time',
        'reason',
        'is_active',
        'target_mode',      // e.g., 'classes' or 'cadets'
        'allowed_classes',  // JSON array of class names
        'allowed_cadets',   // JSON array of specific cadet IDs
    ];

    /**
     * The attributes that should be cast to native types.
     * This is the "Vital Detail" that automatically converts JSON to PHP arrays.
     */
    protected $casts = [
        'is_active'       => 'boolean',
        'allowed_classes' => 'array',
        'allowed_cadets'  => 'array',
    ];

    /**
     * RELATIONSHIP: One Schedule manages Many Outpasses.
     */
    public function outpasses(): HasMany
    {
        return $this->hasMany(Outpass::class, 'schedule_id');
    }

    /**
     * SCOPE: Quickly filter only active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
