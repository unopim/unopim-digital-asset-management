@php
    $value ??= [];

    $fieldName ??= 'assets';

    $field ??= [];

    $isLocked ??= false;

    $lockTitle ??= null;
@endphp

<fieldset @disabled($isLocked) class="border-0 p-0 m-0 min-w-0 {{ $isLocked ? 'opacity-60 cursor-not-allowed' : '' }}">
    @if ($isLocked)
        <div class="pointer-events-none" @if ($lockTitle) title="{{ $lockTitle }}" @endif>
    @endif

    <x-dam::asset.field
        :name="$fieldName"
        :asset-values="is_array($value) ? implode(',', $value) : $value"
        :readonly="$isLocked"
        showPlaceholders="true"
        width="210px"
    />

    @if ($isLocked)
        </div>
    @endif
</fieldset>
