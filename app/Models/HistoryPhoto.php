<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryPhoto extends Model
{
    use HasFactory;

    protected $table = 'history_sharing_photos';

    protected $fillable = [
        'photo_id',
        'user_id'
    ];

    public function relatedPhoto(){
        return $this->belongsTo(SharingPhoto::class, 'photo_id');
    }
}
