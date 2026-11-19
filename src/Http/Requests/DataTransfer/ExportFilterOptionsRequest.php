<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Requests\DataTransfer;

use Illuminate\Foundation\Http\FormRequest;

class ExportFilterOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query'              => ['nullable', 'string', 'max:255'],
            'page'               => ['nullable', 'integer', 'min:1'],
            'identifiers'        => ['nullable', 'array'],
            'identifiers.values' => ['nullable'],
        ];
    }

    public function searchTerm(): string
    {
        return trim((string) $this->input('query', ''));
    }

    public function pageNumber(): int
    {
        return max(1, (int) $this->input('page', 1));
    }

    /**
     * @return array<int, string>|null
     */
    public function identifierValues(): ?array
    {
        $values = $this->input('identifiers.values');

        if ($values === null || $values === '' || $values === []) {
            return null;
        }

        $values = is_array($values) ? $values : explode(',', (string) $values);

        $values = array_values(array_filter(array_map(trim(...), $values), 'strlen'));

        return $values === [] ? null : $values;
    }
}
