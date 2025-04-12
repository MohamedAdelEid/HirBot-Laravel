<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\Post\PostMediaTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Support\Facades\Storage;

class PostMediaModel extends Model
{
    protected $table = 'post_media';

    protected $fillable = [
        'post_id',
        'type',
        'media_url'
    ];

    protected $casts = [
        'type' => PostMediaTypeEnum::class,
    ];

    /**
     * The attribute media_url form the post.
     *
     * @var list<string>
     */
    public function getMediaUrlAttribute(): string
    {
        $path = $this->attributes['media_url'] ?? '';

        return Storage::url($path);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(PostModel::class, 'post_id');
    }
}
