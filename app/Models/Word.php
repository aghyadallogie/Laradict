<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    protected $fillable = ['user_id', 'translations'];

    protected $casts = [
        'translations' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
