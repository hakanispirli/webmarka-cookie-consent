@php
    /** @var \Webmarka\CookieConsent\CookieConsent $cookieConsent */
    $cookieConsent = app(\Webmarka\CookieConsent\CookieConsent::class);
@endphp
@if ($cookieConsent->enabled())
@php
    $nonce = $cookieConsent->nonce();
    $nonceAttr = $nonce ? ' nonce="'.e($nonce).'"' : '';
    $config = json_encode($cookieConsent->clientConfig(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
@endphp
<script type="application/json" id="cookie-consent-config">{!! $config !!}</script>
<script src="{{ $cookieConsent->assetUrl('cookie-consent.js') }}" defer{!! $nonceAttr !!}></script>
@if ($cookieConsent->config('assets.alpine', false))
<script src="{{ $cookieConsent->assetUrl('cookie-consent-alpine.js') }}" defer{!! $nonceAttr !!}></script>
@endif
@endif
