<?php

namespace Webkul\DAM\Models;

use Webkul\Attribute\Models\Attribute as BaseAttribute;
use Webkul\DAM\Rules\AssetRule;

class Attribute extends BaseAttribute
{
    /**
     * {@inheritdoc}
     */
    public function fieldTypeValidations(): array
    {
        $rules = parent::fieldTypeValidations();

        switch ($this->type) {
            case 'asset':
                $rules[] = new AssetRule($this);

                break;
        }

        return $rules;
    }
}
