<?php

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DirectorySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'      => ['required', 'string', 'min:2', 'max:100'],
            'offset' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => trans('dam::app.admin.dam.index.directory.search.placeholder'),
        ];
    }
}
