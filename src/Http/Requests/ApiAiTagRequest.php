<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiAiTagRequest extends FormRequest
{
    /**
     * The api.scope middleware has already checked the key's api.dam.tags.ai-tag scope.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'asset_ids'   => ['required', 'array', 'min:1', 'max:1000'],
            'asset_ids.*' => ['integer', 'min:1'],
        ];
    }
}
