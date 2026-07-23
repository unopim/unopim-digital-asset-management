<?php

namespace Webkul\DAM\Models;

use Webkul\Attribute\Models\Attribute as BaseAttribute;
use Webkul\DAM\Rules\AssetRule;

class Attribute extends BaseAttribute
{
    /**
     * Build the validation rules for the attribute field type.
     */
    public function fieldTypeValidations(?int $id = null, array $allowedPathPrefixes = []): array
    {
        $rules = parent::fieldTypeValidations($id, $allowedPathPrefixes);

        switch ($this->type) {
            case 'asset':
                $rules[] = new AssetRule($this);

                break;
        }

        return $rules;
    }
}
