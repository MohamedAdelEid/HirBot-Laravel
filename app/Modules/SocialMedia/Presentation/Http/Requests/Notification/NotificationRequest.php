<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Notification;

use App\Shared\Enums\NotifiableTypeEnum;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class NotificationRequest extends FormRequest
{
    public function __construct(
        private readonly ApiResponse $response
    ) {
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $socialMediaCategories = NotifiableTypeEnum::socialMediaCategories();

        return [
            'after' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
            'is_read' => 'nullable|boolean',
            'type' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($socialMediaCategories) {
                    if ($value) {
                        $types = explode(',', $value);
                        foreach ($types as $type) {
                            $type = trim($type);
                            if (!in_array($type, $socialMediaCategories)) {
                                $fail("The {$attribute} contains invalid notification type: {$type}. Allowed types: " . implode(', ', $socialMediaCategories));
                            }
                        }
                    }
                }
            ],
            'search' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'after.date' => 'The after parameter must be a valid date.',
            'limit.integer' => 'The limit parameter must be an integer.',
            'limit.min' => 'The limit parameter must be at least 1.',
            'limit.max' => 'The limit parameter cannot exceed 100.',
            'is_read.boolean' => 'The is_read parameter must be true or false.',
            'search.max' => 'The search parameter cannot exceed 255 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response->error('Validation failed', $validator->errors(), 422)
        );
    }

    /**
     * Get the validated and processed notification types.
     *
     * @return array
     */
    public function getNotificationTypes(): array
    {
        $typeString = $this->query('type');

        if (!$typeString) {
            return NotifiableTypeEnum::socialMediaTypes();
        }

        $types = array_map('trim', explode(',', $typeString));
        $categories = [];

        foreach ($types as $type) {
            $enum = NotifiableTypeEnum::fromCategory($type);
            if ($enum) {
                $categories[] = $enum->value;
            }
        }

        return !empty($categories) ? $categories : NotifiableTypeEnum::socialMediaTypes();
    }
}
