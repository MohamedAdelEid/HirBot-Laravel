<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class GetPostInteractionsRequest extends FormRequest
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
        ];
    }
}
