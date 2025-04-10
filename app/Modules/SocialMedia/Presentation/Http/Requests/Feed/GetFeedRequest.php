<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Feed;

use Illuminate\Foundation\Http\FormRequest;

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
            'visibility' => 'sometimes|string|in:public,private,friends',
        ];
    }
}
