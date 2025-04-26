<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\Post\PostMediaTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Support\Facades\Storage;

class PostMediaModel extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \App\Modules\SocialMedia\Database\Factories\PostMediaModelFactory::new();
    }
    protected $table = 'post_media';

    protected $fillable = [
        'post_id',
        'type',
        'media_url',
        'poster_url',
    ];

    protected $casts = [
        'type' => PostMediaTypeEnum::class,
    ];

    public function getMediaUrlAttribute(): string
    {
        $path = $this->attributes['media_url'] ?? '';

        return Storage::url($path);
    }

    public function getPosterUrlAttribute(): string
    {
        $path = $this->attributes['poster_url'] ?? '';

        return Storage::url($path);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(PostModel::class, 'post_id');
    }
}
