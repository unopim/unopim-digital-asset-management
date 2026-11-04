<?php

namespace Webkul\DAM\Validators\JobInstances\Import;

use Webkul\DataTransfer\Rules\SeparatorTypes;
use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;

class AssetJobValidator extends JobValidator
{
    public function getRules(array $options): array
    {
        $this->rules['file'] = [
            empty($options['id']) ? 'required' : 'nullable',
            'mimes:csv,txt,xlsx,xls,zip',
            'extensions:csv,xlsx,xls,zip',
        ];

        $this->rules['field_separator'] = ['required', new SeparatorTypes];

        return $this->rules;
    }

    protected array $rules = [
        'action'              => 'required:in:append,delete',
        'validation_strategy' => 'required:in:stop-on-errors,skip-errors',
        'allowed_errors'      => 'required|integer|min:0',
    ];

    protected array $attributeNames = [
        'action'              => 'Action',
        'validation_strategy' => 'Validation Strategy',
        'allowed_errors'      => 'Allowed Errors',
        'field_separator'     => 'Field Separator',
        'file'                => 'File',
    ];

    public function getMessages(array $options): array
    {
        return array_merge(parent::getMessages($options), [
            'file.mimes' => trans('validation.mimes', ['values' => 'csv,xls,xlsx,zip']),
        ]);
    }
}
