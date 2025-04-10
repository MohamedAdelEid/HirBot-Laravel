<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\Post\PostMediaTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;

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

    public function post(): BelongsTo
    {
        return $this->belongsTo(PostModel::class, 'post_id');
    }
}
