<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class CompanySuggestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'industry' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'string', 'max:255'],
            'min_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'Page must be a valid integer.',
            'page.min' => 'Page must be at least 1.',
            'per_page.integer' => 'Per page must be a valid integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 50.',
            'industry.string' => 'Industry must be a valid string.',
            'industry.max' => 'Industry cannot exceed 255 characters.',
            'location.string' => 'Location must be a valid string.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'min_score.numeric' => 'Minimum score must be a valid number.',
            'min_score.min' => 'Minimum score cannot be less than 0.',
            'min_score.max' => 'Minimum score cannot exceed 100.',
        ];
    }
}
