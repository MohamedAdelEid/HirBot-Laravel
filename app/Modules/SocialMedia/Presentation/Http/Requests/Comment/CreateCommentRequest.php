<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Comment;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\RequiredIf;

class CreateCommentRequest extends FormRequest
{
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
        return [
            'content' => [new RequiredIf(!$this->hasFile('image')), 'nullable', 'string', 'max:1000'],
            'image' => [new RequiredIf(!$this->has('content') || empty($this->input('content'))), 'nullable', 'image', 'max:10240'],
            'parent_comment_id' => 'nullable|integer|exists:comments,id'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }
}
