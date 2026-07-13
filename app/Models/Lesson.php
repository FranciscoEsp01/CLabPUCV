<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['module_id', 'title', 'content', 'video_url', 'pdf_path', 'order'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_completed')->withTimestamps();
    }
}
