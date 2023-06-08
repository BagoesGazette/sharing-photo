<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharingPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'caption',
        'tags',
        'like'
    ];

    public function relatedHistory(){
        return $this->hasMany(HistoryPhoto::class, 'photo_id');
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => asset('/storage/sharing-photo/' . $value),
        );
    }
}
