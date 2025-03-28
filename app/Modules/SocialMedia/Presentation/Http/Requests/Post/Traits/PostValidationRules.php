<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Post\Traits;

use App\Modules\SocialMedia\Domain\Enums\PostMediaTypeEnum;
use App\Modules\SocialMedia\Domain\Enums\PostVisibilityEnum;
use App\Modules\SocialMedia\Domain\Enums\PrivacyCommentsEnum;
use Illuminate\Validation\Rule;

trait PostValidationRules
{
    protected function getPostRules(): array
    {
        return [
            'content' => 'required|string|max:1000',
            'privacy_comments' => ['required' , Rule::in(PrivacyCommentsEnum::values())],
            'visibility' => ['required' , Rule::in(PostVisibilityEnum::values())],
            'media' => ['nullable', 'array', function($attribute, $value, $fail) {
                if (isset($value)) {
                    $documentCount = collect($value)->where('type', 'document')->count();
                    if ($documentCount > 1) {
                        $fail('Only one document is allowed per post.');
                    }
                }
            }],
            'media.*.type' => [
                'required_with:media',
                Rule::in(PostMediaTypeEnum::values()),
            ],
            'media.*.file' => [
                'required_with:media',
                'file',
                Rule::when(fn($input) => data_get($input, 'media.*.type') === PostMediaTypeEnum::DOCUMENT , [
                    'mimes:pdf,doc,docx',
                ]),
                Rule::when(fn($input) => data_get($input, 'media.*.type') === PostMediaTypeEnum::IMAGE , [
                    'mimes:jpg,jpeg,png,gif',
                ]),
                Rule::when(fn($input) => data_get($input, 'media.*.type') === PostMediaTypeEnum::VIDEO , [
                    'mimes:mp4,mov,avi',
                ]),
            ],
            'poll_data' => 'required_if:type,poll|array',
            'poll_data.question' => 'required_if:type,poll|string',
            'poll_data.options' => 'required_if:type,poll|array|min:2|max:4',
            'poll_data.options.*' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'media.*.file.mimes' => 'The :attribute must be a file of type: :values for the selected media type.',
            'media.*.type.in' => 'The media type must be one of: image, video, document.',
        ];
    }
}
