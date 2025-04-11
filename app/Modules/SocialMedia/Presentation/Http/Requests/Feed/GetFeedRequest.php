<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Feed;

use App\Modules\SocialMedia\Domain\Enums\Post\PostVisibilityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'visibility' => ['sometimes', 'string', Rule::in(PostVisibilityEnum::values())],
            'doFeedRefresh' => 'sometimes|boolean',
            'lastUpdatedAt' => 'sometimes|date_format:Y-m-d H:i:s|required_if:doFeedRefresh,true'
        ];
    }
}
