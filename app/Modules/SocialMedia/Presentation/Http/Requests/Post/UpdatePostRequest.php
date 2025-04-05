<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Post;

use App\Modules\SocialMedia\Presentation\Http\Requests\Post\Traits\PostValidationRules;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdatePostRequest extends FormRequest
{
    use PostValidationRules;

    public function __construct(
        private readonly ApiResponse $response
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        $post = $this->route('post');
        return $post && $post->user_id === Auth::user()->Id;
    }

    public function rules(): array
    {
        return $this->getUpdatePostRules();
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }
}

