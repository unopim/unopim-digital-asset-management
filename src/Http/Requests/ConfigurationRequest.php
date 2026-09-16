<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('dam.configuration.update');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'DAM_AI_TAGGING_PLATFORM_ID' => ['nullable', 'integer', 'exists:magic_ai_platforms,id'],
            'DAM_AI_TAGGING_MAX_TAGS'    => ['nullable', 'integer', 'min:1', 'max:20'],
            'DAM_AI_TAGGING_RATE_LIMIT'  => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'DAM_AI_TAGGING_PLATFORM_ID' => trans('dam::app.admin.configuration.ai-tagging.platform.label'),
            'DAM_AI_TAGGING_MAX_TAGS'    => trans('dam::app.admin.configuration.ai-tagging.max-tags.label'),
            'DAM_AI_TAGGING_RATE_LIMIT'  => trans('dam::app.admin.configuration.ai-tagging.rate-limit.label'),
        ];
    }
}
