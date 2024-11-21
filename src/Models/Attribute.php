<?php

namespace Webkul\DAM\Models;

use Webkul\Attribute\Models\Attribute as BaseAttribute;

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
                $rules[] = new \Webkul\DAM\Rules\AssetRule($this);

                break;
        }

        return $rules;
    }
}
