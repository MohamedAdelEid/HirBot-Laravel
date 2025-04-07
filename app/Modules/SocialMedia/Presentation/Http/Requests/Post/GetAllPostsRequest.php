<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class GetAllPostsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'visibility' => 'sometimes|string|in:public,private,friends',
            'sort_by' => 'sometimes|string|in:created_at,updated_at',
            'sort_direction' => 'sometimes|string|in:asc,desc',
        ];
    }
}
