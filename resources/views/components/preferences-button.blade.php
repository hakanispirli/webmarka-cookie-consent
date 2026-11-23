{{-- Withdrawing consent must be as easy as giving it: keep this link reachable on every page (e.g. footer). --}}
<button type="button" {{ $attributes->class(['cc-preferences-button']) }} data-cc-action="show-preferences" aria-haspopup="dialog">
    {{ $slot->isEmpty() ? __('cookie-consent::cookie-consent.buttons.cookie_settings') : $slot }}
</button>
