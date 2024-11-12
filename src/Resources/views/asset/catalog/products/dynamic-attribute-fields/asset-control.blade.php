@php
    $value ??= [];

    $fieldName ??= 'assets';

    $field ??= [];
@endphp

<x-dam::asset.field
    :name="$fieldName"
    :asset-values="is_array($value) ? implode(',', $value) : $value"
    showPlaceholders="true"
    width="210px"
/>
