@php
    /** @var \Webmarka\CookieConsent\CookieConsent $cookieConsent */
    $cookieConsent = app(\Webmarka\CookieConsent\CookieConsent::class);
@endphp
@if ($cookieConsent->enabled())
@php
    $nonce = $cookieConsent->nonce();
    $nonceAttr = $nonce ? ' nonce="'.e($nonce).'"' : '';
    $basic = $cookieConsent->googleMode() === 'basic';
    $gtmId = $cookieConsent->config('google.gtm_id');
    $tagIds = array_values(array_filter((array) $cookieConsent->config('google.tag_ids', []), 'is_string'));
    // In basic mode, Google tags wait for analytics OR marketing consent.
    $deferredAttr = $basic ? ' type="text/plain" data-cc-category="analytics marketing" data-cc-require="any"' : '';
@endphp
<script{!! $nonceAttr !!}>
/* Cookie consent: Google Consent Mode v2 defaults. Must run before Google tags. */
(function (w, d) {
    var c = {{ \Illuminate\Support\Js::from($cookieConsent->headConfig()) }}, k, i, s = null;
    w.dataLayer = w.dataLayer || [];
    if (typeof w.gtag !== 'function') { w.gtag = function () { w.dataLayer.push(arguments); }; }
    if (c.cm) {
        for (i = 0; i < c.regions.length; i++) { w.gtag('consent', 'default', c.regions[i]); }
        var def = {};
        for (k in c.defaults) { def[k] = c.defaults[k]; }
        def.wait_for_update = c.wait;
        w.gtag('consent', 'default', def);
        if (c.redaction) { w.gtag('set', 'ads_data_redaction', true); }
        if (c.passthrough) { w.gtag('set', 'url_passthrough', true); }
    }
    try {
        var parts = d.cookie ? d.cookie.split('; ') : [];
        for (i = 0; i < parts.length; i++) {
            if (parts[i].indexOf(c.name + '=') === 0) {
                s = JSON.parse(decodeURIComponent(parts[i].substring(c.name.length + 1)));
                break;
            }
        }
        if (!s || s.v !== 1 || s.pv !== c.pv || !s.c || typeof s.c !== 'object') { s = null; }
    } catch (e) { s = null; }
    if (!s) { return; }
    var cats = {}, upd = {};
    for (k in c.map) {
        cats[k] = c.req[k] === true || s.c[k] === true;
        for (i = 0; i < c.map[k].length; i++) {
            upd[c.map[k][i]] = (upd[c.map[k][i]] !== 'denied' && cats[k]) ? 'granted' : 'denied';
        }
    }
    if (c.cm) {
        for (k in c.defaults) { if (!upd[k]) { upd[k] = 'denied'; } }
        w.gtag('consent', 'update', upd);
    }
    w.dataLayer.push({ event: c.event, cookie_consent: cats, cookie_consent_action: 'restore' });
})(window, document);
</script>
@if (is_string($gtmId) && $gtmId !== '')
<!-- Google Tag Manager -->
<script{!! $nonceAttr !!}{!! $deferredAttr !!}>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;var n=d.querySelector('[nonce]');n&&j.setAttribute('nonce',n.nonce||n.getAttribute('nonce'));f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',{{ \Illuminate\Support\Js::from($gtmId) }});
</script>
<!-- End Google Tag Manager -->
@endif
@if ($tagIds !== [])
<script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($tagIds[0]) }}"{!! $nonceAttr !!}{!! $deferredAttr !!}></script>
<script{!! $nonceAttr !!}{!! $deferredAttr !!}>
gtag('js', new Date());
@foreach ($tagIds as $tagId)
gtag('config', {{ \Illuminate\Support\Js::from($tagId) }});
@endforeach
</script>
@endif
@if ($cookieConsent->config('ui.styles', true))
<link rel="stylesheet" href="{{ $cookieConsent->assetUrl('cookie-consent.css') }}"{!! $nonceAttr !!}>
@endif
@endif
