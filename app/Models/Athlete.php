<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'bib_number',
        'full_name',
        'finish_time_official',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'athlete_photo')
            ->withPivot('match_type')
            ->withTimestamps();
    }
}