<?php

namespace App\Modules\SocialMedia\Presentation\Http\Requests\Post\Traits;

use App\Modules\SocialMedia\Domain\Enums\Post\PostMediaTypeEnum;
use App\Modules\SocialMedia\Domain\Enums\Post\PostVisibilityEnum;
use App\Modules\SocialMedia\Domain\Enums\Post\PrivacyCommentsEnum;
use Illuminate\Validation\Rule;

trait PostValidationRules
{
    protected function getCreatePostRules(): array
    {
        return [
            'content' => 'required|string|max:1000',
            'privacy_comments' => ['required', Rule::in(PrivacyCommentsEnum::values())],
            'visibility' => ['required', Rule::in(PostVisibilityEnum::values())],
            ...$this->getCommonRules(),
        ];
    }

    protected function getUpdatePostRules(): array
    {
        return [
            'content' => 'sometimes|string|max:10000',
            'privacy_comments' => ['sometimes', Rule::in(PrivacyCommentsEnum::values())],
            'visibility' => ['sometimes', Rule::in(PostVisibilityEnum::values())],
            'media_to_delete' => 'sometimes|array',
            'media_to_delete.*' => 'integer|exists:post_media,id',
            'options_to_delete' => 'sometimes|array',
            'options_to_delete.*' => 'integer|exists:poll_options,id',
            ...$this->getCommonRules(),
        ];
    }

    private function getCommonRules(): array
    {
        return [
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
                Rule::when(fn($input) => data_get($input, 'media.*.type') === PostMediaTypeEnum::DOCUMENT, [
                    'mimes:pdf,doc,docx',
                ]),
                Rule::when(fn($input) => data_get($input, 'media.*.type') === PostMediaTypeEnum::IMAGE, [
                    'mimes:jpg,jpeg,png,gif',
                ]),
                Rule::when(fn($input) => data_get($input, 'media.*.type') === PostMediaTypeEnum::VIDEO, [
                    'mimes:mp4,mov,avi',
                ]),
            ],
            'poll_data' => 'nullable|array',
            'poll_data.question' => 'required_with:poll_data.options|string|max:255',
            'poll_data.options' => 'required_with:poll_data.question|array',
            'poll_data.options.*' => 'array',
            'poll_data.options.*.content' => 'required|string|max:100|distinct',
            'poll_data.options.*.id' => 'sometimes|integer|exists:options,id',
        ];
    }

    public function messages(): array
    {
        return [
            'media.*.file.mimes' => 'The :attribute must be a file of type: :values for the selected media type.',
            'media.*.type.in' => 'The media type must be one of: image, video, document.',
            'media_to_delete.*.exists' => 'The selected media does not exist.',
            'options_to_delete.*.exists' => 'The selected poll option does not exist.',
            'poll_data.options.*.id.exists' => 'The selected poll option does not exist.',
        ];
    }
}

