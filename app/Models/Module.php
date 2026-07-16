<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['title', 'description', 'order', 'is_visible', 'available_from'];

    protected $casts = [
        'is_visible' => 'boolean',
        'available_from' => 'datetime',
    ];

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}
