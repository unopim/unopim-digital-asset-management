<?php

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // On update the tag id arrives as a route parameter — ignore the row itself
        // so a no-op rename passes.
        $tagId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                // Case-insensitive uniqueness portable across MySQL and PostgreSQL.
                // MySQL's default collation is case-insensitive, but PostgreSQL is
                // not, so compare on LOWER(name) explicitly instead of relying on the
                // collation (otherwise "summer" would not collide with "Summer" on PG).
                function (string $attribute, mixed $value, \Closure $fail) use ($tagId): void {
                    $duplicate = DB::table('dam_tags')
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))])
                        ->when($tagId, fn ($query) => $query->where('id', '!=', $tagId))
                        ->exists();

                    if ($duplicate) {
                        $fail(trans('validation.unique', [
                            'attribute' => $this->attributes()['name'] ?? $attribute,
                        ]));
                    }
                },
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
