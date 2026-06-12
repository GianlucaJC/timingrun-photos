<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'original_name',
        'original_path',
        'watermarked_path',
        'thumbnail_path',
        'admin_thumbnail_path',
        'taken_at',
        'status',
        'photo_usage_type',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bibs()
    {
        return $this->hasMany(PhotoBib::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'athlete_photo')
            ->withPivot('match_type')
            ->withTimestamps();
    }
}
