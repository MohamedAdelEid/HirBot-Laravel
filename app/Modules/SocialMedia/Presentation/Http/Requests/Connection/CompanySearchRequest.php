<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Connection;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanySearchRequest extends FormRequest
{
    public function __construct(
        private readonly ApiResponse $response
    ) {
        parent::__construct();
    }

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
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }
}
