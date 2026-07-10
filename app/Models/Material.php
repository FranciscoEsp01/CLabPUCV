<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['title', 'type', 'file_path', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
