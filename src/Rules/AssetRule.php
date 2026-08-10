<?php

namespace Webkul\DAM\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Attribute\Contracts\Attribute;

class AssetRule implements ValidationRule
{
    public function __construct(
        protected Attribute $productAttribute,
    ) {}

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = array_filter(is_array($value)
            ? $value
            : array_map(trim(...), explode(',', (string) $value)));

        if ($this->productAttribute->is_required && empty($value)) {
            $fail(trans('dam::app.admin.validation.asset.required'));
        }
    }
}
