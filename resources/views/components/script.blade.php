@props([
    // One or more category keys separated by spaces.
    'category',
    // "all" (default): every category must be granted. "any": one is enough.
    'require' => 'all',
    'src' => null,
])
@php
    $nonce = app(\Webmarka\CookieConsent\CookieConsent::class)->nonce();
@endphp
{{-- Rendered inert (type="text/plain"); the JavaScript core activates it after consent. --}}
<script type="text/plain" data-cc-category="{{ $category }}"@if ($require === 'any') data-cc-require="any"@endif @if ($src) src="{{ $src }}"@endif @if ($nonce) nonce="{{ $nonce }}"@endif {{ $attributes }}>{{ $slot }}</script>
