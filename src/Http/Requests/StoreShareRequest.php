<?php

namespace Webkul\DAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\DAM\Models\Share;

class StoreShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'share_type'  => 'required|in:'.Share::TYPE_ASSET.','.Share::TYPE_DIRECTORY,
            'target_id'   => 'required|integer|min:1',
            'expiry_days' => 'nullable|integer|min:1|max:365',
            'no_expiry'   => 'nullable|boolean',
        ];
    }
}
