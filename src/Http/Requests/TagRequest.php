<?php

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // dam_tags.name uses the default case-insensitive collation, so this also
        // blocks "Summer" when "summer" already exists.
        $unique = Rule::unique('dam_tags', 'name');

        // On update the tag id arrives as a route parameter — ignore the row itself so a
        // no-op rename passes. Only call ignore() when editing: ignore(null) would compile
        // to "id <> NULL", which is never true and silently disables the whole check.
        if ($tagId = $this->route('id')) {
            $unique->ignore($tagId);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $unique,
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('dam::app.admin.dam.tag.datagrid.name'),
        ];
    }
}
