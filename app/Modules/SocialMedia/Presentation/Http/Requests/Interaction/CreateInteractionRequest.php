<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Interaction;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractionTypeEnum;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateInteractionRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(InteractionTypeEnum::values())]
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }
}
