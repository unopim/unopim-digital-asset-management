<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\DAM\Traits\AutoTagEligibility;

class MassAiTagRequest extends FormRequest
{
    use AutoTagEligibility;

    public function authorize(): bool
    {
        return $this->autoTagEligible();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'indices'   => ['required', 'array', 'min:1', 'max:1000'],
            'indices.*' => ['integer'],
        ];
    }
}
