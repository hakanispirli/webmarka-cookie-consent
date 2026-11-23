@props([
    'layout' => null,
    'position' => null,
    'theme' => null,
])
@php
    /** @var \Webmarka\CookieConsent\CookieConsent $cookieConsent */
    $cookieConsent = app(\Webmarka\CookieConsent\CookieConsent::class);
    $layout = in_array($layout ?? $cookieConsent->config('ui.layout'), ['box', 'bar', 'modal'], true) ? ($layout ?? $cookieConsent->config('ui.layout')) : 'box';
    $position = in_array($position ?? $cookieConsent->config('ui.position'), ['bottom-left', 'bottom-right', 'bottom', 'top'], true) ? ($position ?? $cookieConsent->config('ui.position')) : 'bottom-left';
    $theme = in_array($theme ?? $cookieConsent->config('ui.theme'), ['auto', 'light', 'dark'], true) ? ($theme ?? $cookieConsent->config('ui.theme')) : 'auto';
    $privacyUrl = $cookieConsent->url('privacy_policy');
    $cookiePolicyUrl = $cookieConsent->url('cookie_policy');
    $controller = (array) $cookieConsent->config('data_controller', []);
    $showClose = (bool) $cookieConsent->config('ui.show_close_button', false);
@endphp
@if ($cookieConsent->enabled())
<div {{ $attributes->class(['cc-root']) }} data-cc-root data-cc-layout="{{ $layout }}" data-cc-position="{{ $position }}" data-cc-theme="{{ $theme }}">

    {{-- Layer 1: short notice. Accept and reject have equal prominence. --}}
    <section class="cc-banner" data-cc-banner role="region" aria-labelledby="cc-banner-title" aria-describedby="cc-banner-desc" hidden>
        <div class="cc-banner__body">
            <h2 class="cc-banner__title" id="cc-banner-title">{{ __('cookie-consent::cookie-consent.banner.title') }}</h2>
            <p class="cc-banner__text" id="cc-banner-desc">
                {{ __('cookie-consent::cookie-consent.banner.description') }}
                @if ($cookiePolicyUrl || $privacyUrl)
                    <span class="cc-banner__links">
                        @if ($cookiePolicyUrl)
                            <a class="cc-link" href="{{ $cookiePolicyUrl }}">{{ __('cookie-consent::cookie-consent.links.cookie_policy') }}</a>
                        @endif
                        @if ($privacyUrl)
                            <a class="cc-link" href="{{ $privacyUrl }}">{{ __('cookie-consent::cookie-consent.links.privacy_policy') }}</a>
                        @endif
                    </span>
                @endif
            </p>
        </div>
        <div class="cc-banner__actions">
            <button type="button" class="cc-btn cc-btn--primary" data-cc-action="reject-all">{{ __('cookie-consent::cookie-consent.buttons.reject_all') }}</button>
            <button type="button" class="cc-btn cc-btn--primary" data-cc-action="accept-all">{{ __('cookie-consent::cookie-consent.buttons.accept_all') }}</button>
            <button type="button" class="cc-btn cc-btn--link" data-cc-action="show-preferences" aria-haspopup="dialog">{{ __('cookie-consent::cookie-consent.buttons.preferences') }}</button>
        </div>
        @if ($showClose)
            <button type="button" class="cc-close" data-cc-action="close" aria-label="{{ __('cookie-consent::cookie-consent.buttons.close_reject') }}">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        @endif
    </section>

    {{-- Layer 2: preference center. --}}
    <div class="cc-modal" data-cc-preferences hidden>
        <div class="cc-modal__backdrop" data-cc-action="close-preferences"></div>
        <div class="cc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cc-prefs-title" tabindex="-1">
            <header class="cc-modal__header">
                <h2 class="cc-modal__title" id="cc-prefs-title">{{ __('cookie-consent::cookie-consent.preferences.title') }}</h2>
                <button type="button" class="cc-close" data-cc-action="close-preferences" aria-label="{{ __('cookie-consent::cookie-consent.buttons.close') }}">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </header>

            <div class="cc-modal__body">
                <p class="cc-text">{{ __('cookie-consent::cookie-consent.preferences.description') }}</p>

                @if (! empty($controller['name']))
                    <p class="cc-text cc-text--muted">
                        <strong>{{ __('cookie-consent::cookie-consent.preferences.data_controller') }}:</strong>
                        {{ $controller['name'] }}@if (! empty($controller['address'])), {{ $controller['address'] }}@endif
                        @if (! empty($controller['email']))
                            &middot; <a class="cc-link" href="mailto:{{ $controller['email'] }}">{{ $controller['email'] }}</a>
                        @endif
                    </p>
                @endif

                @if ($cookiePolicyUrl || $privacyUrl)
                    <p class="cc-text cc-text--muted">
                        @if ($privacyUrl)
                            <a class="cc-link" href="{{ $privacyUrl }}">{{ __('cookie-consent::cookie-consent.links.privacy_policy') }}</a>
                        @endif
                        @if ($cookiePolicyUrl)
                            <a class="cc-link" href="{{ $cookiePolicyUrl }}">{{ __('cookie-consent::cookie-consent.links.cookie_policy') }}</a>
                        @endif
                    </p>
                @endif

                <ul class="cc-categories">
                    @foreach ($cookieConsent->categories() as $category)
                        <li class="cc-category">
                            <div class="cc-category__header">
                                <label class="cc-switch" for="cc-toggle-{{ $category->key }}">
                                    <span class="cc-category__title">{{ $category->title }}</span>
                                    <input
                                        type="checkbox"
                                        role="switch"
                                        class="cc-switch__input"
                                        id="cc-toggle-{{ $category->key }}"
                                        data-cc-category-toggle="{{ $category->key }}"
                                        aria-describedby="cc-desc-{{ $category->key }}"
                                        @if ($category->required) checked disabled @endif
                                    >
                                    <span class="cc-switch__track" aria-hidden="true"></span>
                                </label>
                                @if ($category->required)
                                    <span class="cc-badge">{{ __('cookie-consent::cookie-consent.preferences.always_active') }}</span>
                                @endif
                            </div>
                            @if ($category->description !== '')
                                <p class="cc-category__desc" id="cc-desc-{{ $category->key }}">{{ $category->description }}</p>
                            @endif
                            @if ($category->cookies !== [])
                                <details class="cc-details">
                                    <summary>{{ __('cookie-consent::cookie-consent.preferences.cookie_details', ['count' => count($category->cookies)]) }}</summary>
                                    <x-cookie-consent::cookie-table :category="$category->key" />
                                </details>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <footer class="cc-modal__footer">
                <button type="button" class="cc-btn cc-btn--primary" data-cc-action="reject-all">{{ __('cookie-consent::cookie-consent.buttons.reject_all') }}</button>
                <button type="button" class="cc-btn cc-btn--secondary" data-cc-action="save">{{ __('cookie-consent::cookie-consent.buttons.save') }}</button>
                <button type="button" class="cc-btn cc-btn--primary" data-cc-action="accept-all">{{ __('cookie-consent::cookie-consent.buttons.accept_all') }}</button>
            </footer>
        </div>
    </div>
</div>
@endif
