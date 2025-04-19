<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Poll;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VotePollRequest extends FormRequest
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
            'option_id' => 'required|integer|exists:options,id',
            'poll_id' => 'required|integer|exists:polls,id'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }
}
