<?php

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DirectoryRequest extends FormRequest
{
    public function rules()
    {
        $directoryId = request()->get('id', null);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\\/\\\:\*\?\"\<\>\|]+$/',
                Rule::unique('dam_directories')->where(function ($query) {
                    return $query->where('parent_id', $this->input('parent_id', 1));
                })
                    ->ignore($directoryId),
            ],
            'parent_id' => 'sometimes|integer|exists:dam_directories,id',
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'The directory name must be unique within the same parent directory.',
        ];
    }
}
