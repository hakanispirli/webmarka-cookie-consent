# Yapay zekâ ile kurulum promptu (Türkçe)

Aşağıdaki kutunun içindeki metnin tamamını kopyalayın. Laravel projeniz açıkken yapay zekâ kodlama asistanınıza (Claude Code, Cursor, GitHub Copilot, ChatGPT, Codex, Windsurf vb.) yapıştırın. Asistan önce projenizi inceler, karar gerektiren değişiklikler için size sorar.

````text
Laravel projemde çalışıyorsun. Projeye `webmarka/cookie-consent` Composer paketini
(https://github.com/hakanispirli/webmarka-cookie-consent) entegre ederek KVKK / GDPR uyumlu,
Google Consent Mode v2 destekli bir çerez onay banner'ı ekle.

Dokümantasyon: Paketi kurduktan sonra `vendor/webmarka/cookie-consent/README.md` ve
`vendor/webmarka/cookie-consent/llms.txt` dosyalarını oku ve doğru bilgi kaynağı olarak bunları esas al.
Orada yazmayan direktif, config anahtarı veya API uydurma.

## Adım 1: İnceleme (sadece oku, henüz hiçbir şeyi değiştirme)
Kısaca raporla:
1. Laravel ve PHP sürümü. Paket Laravel 13+ ve PHP 8.3+ ister. Karşılanmıyorsa dur ve bana söyle.
2. `<head>` ve `</body>` içeren Blade layout dosyaları (ör. `resources/views/layouts/*.blade.php`,
   `resources/views/components/layouts/*`, Inertia için `resources/views/app.blade.php`).
3. Tüm takip / üçüncü taraf kodları: Google Tag Manager, gtag.js / GA4, Google Ads, Meta Pixel, TikTok,
   LinkedIn Insight, Hotjar, Microsoft Clarity, Yandex Metrica, canlı destek widget'ları, YouTube / Google
   Maps embed'leri. Blade view'larında, JS dosyalarında, config'te ve `.env`'de ara.
4. Mevcut bir çerez banner'ı (paket veya özel kod) ya da mevcut `gtag('consent', ...)` çağrıları.
5. Frontend yapısı: Alpine.js, Tailwind CSS, Livewire, Inertia (Vue/React), Vite.
6. İçerik Güvenlik Politikası (CSP nonce) veya tam sayfa önbellek (Cloudflare, spatie/laravel-responsecache vb.).
7. Sitenin kullandığı diller.

## Adım 2: Kurulum
Çalıştır:
    composer require webmarka/cookie-consent
    php artisan cookie-consent:install

## Adım 3: Layout
Her ana layout dosyasında:
- `@cookieConsentHead` direktifini `<head>` içinde charset ve viewport meta etiketlerinden hemen sonra,
  Google Tag Manager / gtag.js kodlarından ÖNCE ilk öğe olarak ekle.
- `<x-cookie-consent::banner />` ve `@cookieConsentScripts` direktifini `</body>` etiketinden hemen önce ekle.
- Ziyaretçiler her sayfada onaylarını değiştirebilsin veya geri alabilsin diye footer'a
  `<x-cookie-consent::preferences-button />` ekle.
- Yoksa `<meta name="csrf-token" content="{{ csrf_token() }}">` ekle.
- Alpine.js CDN'den yükleniyorsa `@cookieConsentScripts` direktifini `<head>` içinde Alpine script'inden
  önce koy ve `'assets' => ['alpine' => true]` yap. Alpine Vite ile yükleniyorsa README'deki gibi
  plugin olarak kaydet.

## Adım 4: Google etiketleri
- Tercih edilen yol: GTM container ID'sini ve gtag ID'lerini `.env`'ye taşı (`COOKIE_CONSENT_GTM_ID`,
  `COOKIE_CONSENT_GOOGLE_TAG_IDS=G-...,AW-...`) ve sabit yazılmış snippet'leri kaldır. Paket bunları
  doğru sırayla basar.
- Aksi halde mevcut snippet'i koru ama `@cookieConsentHead` direktifinden SONRA geldiğinden emin ol.
- GTM'in `<noscript><iframe ...>` yedeğini kaldır. Onay olmadan yüklenir.
- Mevcut `gtag('consent', 'default' | 'update', ...)` kodlarını kaldır. Consent Mode'u paket yönetir.

## Adım 5: Diğer üçüncü taraf kodları onaya kadar engelle
- `<script>` → `<script type="text/plain" data-cc-category="KATEGORI">` (`src` ve diğer attribute'ları koru).
- `<iframe src="...">` → `<iframe data-cc-src="..." data-cc-category="KATEGORI">`.
- Kategoriler: `analytics` (Hotjar, Clarity, Yandex...), `marketing` (Meta Pixel, TikTok, LinkedIn,
  YouTube embed...), `functional` (canlı destek, harita...).
- Sitenin çalışması için gereken kodları asla engelleme (uygulama bundle'ı, ödeme, CSRF, formlar için
  zorunlu captcha).
- Blade'de `<x-cookie-consent::script category="...">...</x-cookie-consent::script>` bileşenini de kullanabilirsin.

## Adım 6: Yapılandırma
`.env` dosyasını doldur. Bu değerleri BANA SOR. Şirket bilgisi veya URL uydurma:
    COOKIE_CONSENT_CONTROLLER_NAME=
    COOKIE_CONSENT_CONTROLLER_EMAIL=
    COOKIE_CONSENT_PRIVACY_URL=
    COOKIE_CONSENT_COOKIE_POLICY_URL=
`config/cookie-consent.php` içinde, Adım 1'de bulunan servislerin kullandığı her çerezi doğru kategoriye
ekle (`name` veya regex `pattern`, `provider`, `purpose`, `duration`, `transfers_abroad` ülke kodu veya null).
`necessary` kategorisini session çerezi adıyla uyumlu tut.

## Adım 7: İsteğe bağlı (önce bana sor)
- Rıza kayıtları: `php artisan cookie-consent:install --migrations`, `php artisan migrate`,
  `COOKIE_CONSENT_LOGGING=true` ve `routes/console.php` içinde `cookie-consent:prune` komutunu günlük
  zamanlama.
- `<x-cookie-consent::cookie-table />` bileşenini gösteren bir çerez politikası sayfası.
- Tailwind ile stil (`'ui' => ['styles' => false]` + `php artisan vendor:publish --tag=cookie-consent-views`)
  veya Google Consent Mode için `advanced` yerine `basic` mod.

## Adım 8: Doğrulama
- `php artisan optimize:clear` komutunu ve projenin testlerini çalıştır.
- Bir sayfanın HTML çıktısında `gtag('consent', 'default'` satırının tüm `googletagmanager.com`
  script'lerinden önce geldiğini doğrula.
- Bana tarayıcıda yapmam gereken manuel kontrol listesini ver: gizli pencerede banner görünüyor mu; seçim
  yapılmadan `_ga` / `_gcl_*` / `_fbp` çerezleri oluşmuyor mu; "Tümünü kabul et" sonrası `dataLayer`'da
  `granted` değerli `consent update` var mı; "Tümünü reddet" sonrası `denied` kalıyor mu; footer butonu
  tercihleri yeniden açıyor mu; Google Tag Assistant onay durumlarını gösteriyor mu.

## Kurallar
- Küçük ve odaklı değişiklikler yap. İlgisiz kodları yeniden düzenleme.
- Sonunda değişen tüm dosyaları ve benim elle yapmam gerekenleri listele.
- Bunun teknik bir entegrasyon olduğunu, hukuki danışmanlık olmadığını; metinlerin, çerez listesinin ve
  yurt dışı aktarım dayanağının bir hukukçuya kontrol ettirilmesi gerektiğini hatırlat.
````

> [!TIP]
> Basit projeler için kısa sürüm:
>
> ```text
> Bu Laravel projesine vendor/webmarka/cookie-consent/README.md dosyasına göre `webmarka/cookie-consent` paketini kur:
> kurulum komutunu çalıştır, <head> içine en başa (GTM/gtag'den önce) @cookieConsentHead ekle, </body> öncesine
> banner bileşenini ve @cookieConsentScripts ekle, footer'a tercihler butonunu koy, GTM/gtag ID'lerini .env'ye taşı,
> diğer takip script'lerini type="text/plain" data-cc-category="..." yap; veri sorumlusu adı, e-posta ve politika
> URL'lerini bana sor. Sonunda değişen dosyaları listele.
> ```
