<?php

namespace Webkul\DAM\Validators\JobInstances\Import\Concerns;

/**
 * Widens the core file rule to accept an export archive as well as a bare data file.
 */
trait AcceptsAssetBundle
{
    public function getRules(array $options): array
    {
        $rules = parent::getRules($options);

        $rules['file'] = array_map(
            fn (mixed $rule): mixed => is_string($rule) ? $this->allowZip($rule) : $rule,
            $rules['file']
        );

        return $rules;
    }

    public function getMessages(array $options): array
    {
        return array_merge(parent::getMessages($options), [
            'file.mimes' => trans('validation.mimes', ['values' => 'csv,xls,xlsx,zip']),
        ]);
    }

    protected function allowZip(string $rule): string
    {
        foreach (['mimes:', 'extensions:'] as $prefix) {
            if (str_starts_with($rule, $prefix)) {
                return $rule.',zip';
            }
        }

        return $rule;
    }
}
