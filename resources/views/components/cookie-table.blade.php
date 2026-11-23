@props([
    // Limit to one category key; null shows every category (e.g. on your cookie policy page).
    'category' => null,
])
@php
    /** @var \Webmarka\CookieConsent\CookieConsent $cookieConsent */
    $cookieConsent = app(\Webmarka\CookieConsent\CookieConsent::class);
    $categories = $category !== null
        ? array_filter([$cookieConsent->category($category)])
        : $cookieConsent->categories();
@endphp
<div {{ $attributes->class(['cc-table-wrap']) }}>
    <table class="cc-table">
        <thead>
            <tr>
                <th scope="col">{{ __('cookie-consent::cookie-consent.table.name') }}</th>
                @if ($category === null)
                    <th scope="col">{{ __('cookie-consent::cookie-consent.table.category') }}</th>
                @endif
                <th scope="col">{{ __('cookie-consent::cookie-consent.table.provider') }}</th>
                <th scope="col">{{ __('cookie-consent::cookie-consent.table.purpose') }}</th>
                <th scope="col">{{ __('cookie-consent::cookie-consent.table.duration') }}</th>
                <th scope="col">{{ __('cookie-consent::cookie-consent.table.transfers_abroad') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $item)
                @foreach ($item->cookies as $cookie)
                    <tr>
                        <td><code>{{ $cookie->name }}</code></td>
                        @if ($category === null)
                            <td>{{ $item->title }}</td>
                        @endif
                        <td>{{ $cookie->provider === 'first-party' ? __('cookie-consent::cookie-consent.table.first_party') : $cookie->provider }}</td>
                        <td>{{ $cookie->purpose }}</td>
                        <td>{{ $cookie->duration }}</td>
                        <td>{{ $cookie->transfersAbroad ?? __('cookie-consent::cookie-consent.table.no') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
