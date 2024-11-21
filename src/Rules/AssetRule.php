<?php

namespace Webkul\DAM\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Attribute\Contracts\Attribute;

class AssetRule implements ValidationRule
{
    /**
     * Constructor.
     */
    public function __construct(
        protected Attribute $productAttribute,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = array_filter($value);

        if ($this->productAttribute->is_required && empty($value)) {
            $fail(trans('dam::app.admin.validation.asset.required'));
        }
    }
}
