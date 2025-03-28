<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Post;

use App\Modules\SocialMedia\Presentation\Http\Requests\Post\Traits\PostValidationRules;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreatePostRequest extends FormRequest
{
    use PostValidationRules;

    public function __construct(
        private readonly ApiResponse $response
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->getPostRules();
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }
}
