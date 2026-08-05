@php
    $value ??= [];

    $fieldName ??= 'assets';

    $field ??= [];

    $isLocked ??= false;

    $lockTitle ??= null;
@endphp

{{--
    A locked field is inherited from an ancestor product, so the control is rendered
    read-only and submits nothing: the child must never store its own copy, or it
    would stop tracking the parent's assets. The disabled fieldset suppresses the
    hidden inputs and pointer-events-none blocks the picker, matching how core
    renders every other locked attribute type.
--}}
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
