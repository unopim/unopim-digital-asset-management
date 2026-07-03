@props(['size' => 'h-4 w-4'])

<svg {{ $attributes->merge(['class' => 'animate-spin '.$size.' text-violet-600 dark:text-violet-400']) }}
     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" role="status" aria-hidden="true">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
</svg>
