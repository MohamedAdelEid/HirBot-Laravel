<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Connection;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendConnectionRequest extends FormRequest
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
            'receiver_id' => 'required|string|exists:users,Id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }
}
