<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoBib extends Model
{
    use HasFactory;
    protected $fillable = ['photo_id', 'bib_number', 'confidence'];

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }
}
