# CLAUDE.md

Bu dosya, bu repoda çalışan Claude Code (ve diğer katkıcılar) için proje bağlamı, kararlar ve kurallardır.
Araştırma tarihi: 2026-11-23. Hukuki/teknik gereksinimler değişebilir; "Doğrulanmalı" işaretli maddeleri kodlamadan önce birincil kaynaktan kontrol et.

---

## 1. Proje özeti

Laravel için açık kaynak (public GitHub, MIT) bir **çerez onay (cookie consent) paketi**.

İki ana hedef:
1. **Google Consent Mode v2** ile tam uyum (Google Ads / GA4 kullanan ve AEA/UK/CH trafiği alan siteler için).
2. **KVKK (Türkiye)** Çerez Uygulamaları Rehberi'ne uyum (açık rıza, aydınlatma, kayıt).

Referans ürün (SaaS): https://efilli.com/ — biz bunun **self-hosted, ücretsiz, Laravel-native** karşılığını hedefliyoruz.

- Paket adı: `webmarka/cookie-consent` (npm: `@webmarka/cookie-consent`)
- GitHub: https://github.com/hakanispirli/webmarka-cookie-consent (repo adı `webmarka-cookie-consent`)
- PHP namespace: `Webmarka\CookieConsent`
- Hedef: **Laravel 13+**, **PHP ≥ 8.3** (kullanıcı kararı, 2026-11-24). `illuminate/*: ^13.0`.
  - Laravel 14 (PHP 8.4) çıktığında `^13.0|^14.0` + Testbench sürümü eklenecek.

---

## 2. Mimari kararı: framework-agnostik çekirdek

Kullanıcıların bir kısmı Alpine.js / Tailwind CSS kullanmıyor (Bootstrap, Livewire, Inertia+Vue/React, düz Blade). Bu yüzden:

| Katman | Karar |
|---|---|
| **JS çekirdeği** | Bağımlılıksız **vanilla TypeScript**, tek dosya IIFE + ESM build. Hedef: < 10 KB gzip. Alpine/Tailwind **zorunlu değil**. |
| **CSS** | Paketle gelen, derlenmiş, **önekli** (`.cc-` ön eki) bağımsız CSS. Tema **CSS custom properties** ile (`--cc-primary`, `--cc-radius`, vb.). Dark mode `prefers-color-scheme` + `[data-cc-theme]`. Tailwind gerekmez. |
| **Blade** | Anonim/sınıf Blade bileşenleri (`<x-cookie-consent::banner />`) ve direktifler. Markup publish edilip özelleştirilebilir. |
| **Alpine adaptörü (opsiyonel)** | `Alpine.store('cookieConsent')` sağlayan küçük ek dosya. Çekirdeğin üstünde ince sarmalayıcı; mantık **çekirdekte** kalır. |
| **Tailwind (opsiyonel)** | `unstyled` modu (CSS yüklenmez, sadece `data-*` attribute'ları ve class hook'ları) + Tailwind kullanan için örnek publish edilebilir view'lar. |
| **Entegrasyon olayları** | `window` üzerinde `CustomEvent`'ler (`cookie-consent:ready`, `cookie-consent:updated`) + global `window.CookieConsent` API. Böylece Vue/React/Livewire/Alpine hepsi dinleyebilir. |

Kural: **Hiçbir iş mantığı Alpine'a veya Tailwind'e bağımlı olamaz.** Alpine/Tailwind yalnızca opsiyonel sunum katmanıdır.

### Dağıtım
- Derlenmiş JS/CSS `dist/` altında **repoya commit edilir** (kullanıcı npm kurmadan `php artisan vendor:publish --tag=cookie-consent-assets` ile alabilsin).
- İsteğe bağlı olarak aynı çekirdek npm paketi olarak da yayınlanabilir (Vite ile import etmek isteyenler için). Faz 2.
- `@cookieConsentScripts` direktifi publish edilmiş asset'i `asset()` ile yükler.

---

## 3. Google Consent Mode v2 — gereksinimler

Kaynaklar:
- https://developers.google.com/tag-platform/security/concepts/consent-mode
- https://support.google.com/google-ads/answer/13695607 (AEA güncellemesi)
- https://support.google.com/google-ads/answer/13802165 (onay kurulumu)
- https://support.google.com/analytics/answer/14275483 (GA4 onay ayarları)

### 3.1 Onay türleri (hepsi desteklenmeli)

| Parametre | Ne kontrol eder | Paket kategorisi |
|---|---|---|
| `ad_storage` | Reklam çerezleri | `marketing` |
| `ad_user_data` | Reklam amaçlı kullanıcı verisinin Google'a gönderimi (**v2 yeni**) — dönüşüm takibi, enhanced conversions | `marketing` |
| `ad_personalization` | Kişiselleştirilmiş reklam / remarketing (**v2 yeni**) | `marketing` (opsiyonel ayrı alt anahtar) |
| `analytics_storage` | Analitik çerezler (GA4) | `analytics` |
| `functionality_storage` | İşlevsel (dil vb.) | `functional` |
| `personalization_storage` | Kişiselleştirme (öneriler vb.) | `functional` (ayrılabilir) |
| `security_storage` | Güvenlik / kimlik doğrulama | `necessary` → **her zaman `granted`** |

Kategori → Consent Mode eşlemesi `config/cookie-consent.php` içinde yapılandırılabilir olmalı.

Notlar:
- Kişiselleştirilmiş reklam için hem `ad_user_data` hem `ad_personalization` `granted` olmalı.
- Mart 2024'ten itibaren v1 kullananlarda `ad_storage` otomatik olarak `ad_user_data`'ya eşleniyor; **`ad_personalization` eşlenmiyor** → v2 parametrelerini her zaman açıkça gönder.
- Google Ads **reklamverenleri** için zorunluluk: AEA kullanıcılarından onay toplayıp sinyali Google'a iletmek. Yapılmazsa yaptırım "ceza" değil, **özellik kaybı** (remarketing, kitle, dönüşüm ölçümü kısıtlanır).

### 3.2 Zorunlu uygulama sırası (head içinde)

`@cookieConsentHead` direktifi, **GTM/gtag snippet'inden ÖNCE**, `<head>` içinde en üstte olmalı ve inline şunu üretmeli:

```html
<script nonce="...">
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  // 1) Varsayılan: necessary hariç her şey denied
  gtag('consent', 'default', {
    ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied',
    analytics_storage: 'denied', functionality_storage: 'denied',
    personalization_storage: 'denied', security_storage: 'granted',
    wait_for_update: 500
  });
  // (opsiyonel) bölgeye özel default: { ..., region: ['TR', 'DE', ...] }
  gtag('set', 'ads_data_redaction', true);  // config ile
  gtag('set', 'url_passthrough', false);    // config ile
  // 2) Daha önce kaydedilmiş onay varsa SENKRON olarak hemen update
  //    (çerez okunur, gtag('consent','update',{...}) çağrılır)
</script>
```

- Kullanıcı seçim yaptığında: `gtag('consent', 'update', {...})` + `dataLayer.push({ event: 'cookie_consent_update', ... })` (GTM tetikleyicileri için).
- **Basic mod**: Google etiketleri onaydan önce hiç yüklenmez (paket gtag/GTM'i kendisi yükler). **Advanced mod**: etiketler yüklenir, denied iken cookieless ping gönderir (daha iyi modelleme). Her ikisi de config ile seçilebilir; varsayılan **advanced** (Google Ads kullanıcıları için daha değerli). README'de KVKK açısından basic modun daha temkinli olduğu belirtilmeli. *Doğrulanmalı (hukuki yorum).*
- Opsiyonel: paket `gtm_id` / `ga4_id` / `google_ads_id` config'i ile snippet'i kendisi basabilir (`@cookieConsentGoogleTags`).
- CSP: tüm inline script'ler `Vite::cspNonce()` / config'ten nonce desteklemeli.

### 3.3 IAB TCF ve "Google sertifikalı CMP" — kapsam dışı (v1)

- Google'ın **sertifikalı CMP + IAB TCF v2.2** zorunluluğu (AEA/UK 16 Ocak 2024, İsviçre 31 Temmuz 2024) **yayıncılar** için: **AdSense, AdMob, Ad Manager** ile kişiselleştirilmiş reklam *gösteren* siteler.
- Sadece **Google Ads reklamı veren** (reklamveren) siteler için TCF şart değil; **Consent Mode v2 yeterli**.
- TCF uygulamak; IAB Europe'a CMP olarak kayıt (ücretli), GVL (Global Vendor List) yönetimi, TC string üretimi ve Google sertifikasyonu gerektirir → açık kaynak bir paket için v1'de **kapsam dışı**. README'de açıkça yazılmalı: "AdSense/AdMob/Ad Manager yayıncısıysanız sertifikalı bir CMP kullanmalısınız."
- Kaynak: https://support.google.com/admanager/answer/13554116

---

## 4. KVKK (Türkiye) — gereksinimler

Birincil kaynak: KVKK **Çerez Uygulamaları Hakkında Rehber** (ilk yayın 2022, **Temmuz 2025 güncellemesi**)
https://kvkk.gov.tr/Icerik/7353/Cerez-Uygulamalari-Hakkinda-Rehber

> ⚠️ Araştırma sırasında resmi PDF okunamadı (kvkk.gov.tr TLS sertifika zinciri hatası). Aşağıdaki maddeler ikincil kaynaklara (hukuk bürosu/sektör yazıları) dayanır ve **resmi rehberle doğrulanmalı**. Bu doğrulama, v1.0 yayınından önce yapılacak işler listesindedir.

### 4.1 Banner / rıza kuralları (pakette varsayılan davranış)
- **Zorunlu çerezler** (oturum, CSRF, yük dengeleme, güvenlik, onay tercihinin kendisi) rıza gerektirmez → her zaman aktif, kapatılamaz, ama **aydınlatmada listelenir**.
- Zorunlu olmayan çerezler (analitik, pazarlama, işlevsel) **rıza alınmadan önce çalışmamalı/yerleşmemeli**.
- **Önceden işaretli kutu yok.** Tüm opsiyonel kategoriler varsayılan **kapalı**.
- **Sayfada gezinmek, kaydırmak, banner'ı kapatmak (X) rıza değildir.** X = reddet ya da "karar yok" (hiçbir şey açılmaz).
- **"Tümünü Reddet", "Tümünü Kabul Et" ile aynı katmanda ve eşit görünürlükte** (renk/boyut/konum). Reddi alt menüye gizlemek yasak (dark pattern). Bu davranış config ile *kapatılamaz* olmalı (ya da kapatılırsa uyarı loglanmalı).
- **Katmanlı yaklaşım**: 1. katman kısa bilgi + Kabul / Reddet / Tercihler; 2. katman kategori bazlı seçim (granüler, belirli konuya ilişkin rıza).
- **Cookie wall yok**: Site içeriğine erişim rızaya bağlanamaz.
- **Rızayı geri alma** verme kadar kolay olmalı: kalıcı "Çerez Tercihleri" butonu/linki (`<x-cookie-consent::preferences-button />`) + `window.CookieConsent.show()`.
- Geri alındığında ilgili çerezler mümkün olduğunca silinmeli (bilinen first-party çerez adları config'te tanımlanır, JS ile silinir).

### 4.2 Aydınlatma yükümlülüğü (KVKK md. 10)
Banner/tercih merkezi şunlara erişim sağlamalı (link veya içerik):
- Veri sorumlusunun kimliği
- İşleme amaçları (kategori bazında)
- Aktarılan alıcılar (ör. Google LLC) ve **yurt dışı aktarım** bilgisi
- Toplama yöntemi ve hukuki sebep
- Saklama süreleri
- İlgili kişi hakları (md. 11)
- **Aydınlatma ile açık rıza ayrı tutulmalı** (aydınlatma metni rıza metni içine gömülmez).

Paket: config'te `privacy_policy_url`, `cookie_policy_url`, `data_controller` alanları + her çerez için tablo (ad, sağlayıcı, amaç, süre, kategori, yurt dışı mı). `<x-cookie-consent::cookie-table />` bileşeni çerez politikası sayfasında kullanılabilir.

### 4.3 Yurt dışı aktarım (KVKK md. 9, 7499 sayılı Kanun ile 12.03.2024'te değişti)
- Google Analytics / Google Ads çerezleri pratikte **yurt dışına aktarım** içerir.
- Yeni md. 9'da sıra: yeterlilik kararı → uygun güvenceler (standart sözleşme vb., Kurul'a bildirim) → arızi aktarım istisnaları. Açık rıza, arızi aktarımlarda bir istisna; düzenli/süreklilik arz eden aktarımlar için tek başına dayanak olmaz. *Doğrulanmalı.*
- Paket bu hukuki süreci çözmez; sadece: (a) her çerez/hizmet için `transfers_abroad: true` bilgisini ve hedef ülkeyi gösterir, (b) aydınlatmada bunu listeler. README'de **hukuki danışmanlık değildir** uyarısı.

### 4.4 Rıza kaydı (ispat yükü veri sorumlusunda)
- Opsiyonel **veritabanı loglama** (`consent_logs` tablosu, migration publish edilebilir):
  - `consent_id` (UUID, çerezde de saklanır), kategoriler (JSON), Consent Mode durumları (JSON), aksiyon (`accept_all|reject_all|custom|withdraw`), `policy_version`, banner dili, URL, **hash'lenmiş/kısaltılmış IP** (tam IP saklama default kapalı), user agent (kısaltılmış), `user_id` (giriş yaptıysa, nullable), timestamp.
- Endpoint: `POST /cookie-consent` (route adı/prefix config'ten), CSRF muaf **değil** — token meta'dan alınır; rate limit; `throttle` middleware.
- Saklama süresi config'li + `php artisan cookie-consent:prune` (Laravel `Prunable`).
- Export: `php artisan cookie-consent:export --from= --to=` (CSV).

### 4.5 Yeniden onay
- `policy_version` config değeri değiştiğinde (çerez listesi/amaç değişti) mevcut onay geçersiz sayılır, banner tekrar gösterilir.
- Onay çerezi ömrü config'li, varsayılan **365 gün** (sonra yeniden sorulur). *Doğrulanmalı: KVKK rehberinde süre önerisi var mı.*

---

## 5. Özellik listesi (efilli karşılaştırmalı)

| Özellik | efilli | Bu paket v1 | Sonra |
|---|---|---|---|
| Banner + tercih merkezi (kategori bazlı) | ✅ | ✅ | |
| Eşit görünür Reddet, önceden işaretsiz | ✅ | ✅ (zorunlu) | |
| Script engelleme (onaydan önce çalışmaz) | ✅ otomatik | ✅ `type="text/plain" data-cc-category="..."` + Blade direktifi | Otomatik engelleme (MutationObserver) |
| Google Consent Mode v2 | ✅ | ✅ | |
| GTM entegrasyonu | ✅ rehber | ✅ dataLayer event + README | GTM Community Template |
| Rıza kayıtları + export | ✅ | ✅ DB + artisan | Filament/admin panel eklentisi |
| Çoklu dil | ✅ | ✅ `tr`, `en` (publish edilebilir lang) | de, fr, ar... (katkıyla) |
| Tasarım özelleştirme | ✅ editör | ✅ CSS vars + publish view + layout seçenekleri (bar / box / modal) | |
| Çerez tarayıcı | ✅ SaaS | ❌ | `cookie-consent:scan` (headless tarayıcı ile, opsiyonel) |
| IAB TCF | ✅ (2.x) | ❌ kapsam dışı | |
| Geo-targeting | ? | ⚠️ opsiyonel: `CF-IPCountry` vb. header'dan bölge; Consent Mode `region` parametresi | |
| Mobil SDK | ✅ | ❌ | |
| Çoklu domain paneli | ✅ | ❌ (her site kendi paketi) | |

---

## 6. Teknik detaylar ve tuzaklar

- **Onay çerezi**: adı `cookie_consent` (config). İçerik: `{v: schemaVersion, id: uuid, pv: policyVersion, c: {analytics: bool, ...}, ts}`. JS tarafından okunabilmeli → `HttpOnly` **değil**; `SameSite=Lax`, HTTPS'te `Secure`.
- **Laravel `EncryptCookies`**: Bu çerez şifrelenmemeli, servis sağlayıcıda `EncryptCookies::except()` ile hariç tut (Laravel 11+ `bootstrap/app.php` yapısı: `$middleware->encryptCookies(except: [...])` — paket bunu `booted` sırasında programatik yapmalı, kullanıcıdan istemeden).
- **Full-page cache uyumu** (Cloudflare, Varnish, `spatie/laravel-responsecache`): Banner'ın görünüp görünmeyeceği **istemci tarafında** karar verilmeli. Server-side `@cookieConsentAllowed('analytics')` direktifi sadece cache'lenmeyen sayfalar için kolaylıktır; README'de bu ayrım belirtilmeli. Varsayılan yöntem: `<script type="text/plain" data-cc-category="analytics">` ile istemci tarafı aktivasyon.
- **SPA / Livewire navigate / Inertia / Turbo**: Çekirdek idempotent init; `livewire:navigated`, `turbo:load` gibi olaylarda banner tekrar bağlanmalı, çift instance oluşmamalı.
- **Erişilebilirlik (WCAG 2.2 AA)**: modal'da focus trap, `role="dialog"`, `aria-modal`, `aria-labelledby`; Esc davranışı; klavye ile tüm kontroller; kontrast; `prefers-reduced-motion`. Banner CLS (layout shift) üretmemeli (fixed konum).
- **Performans**: JS `defer`; head'deki inline Consent Mode kodu < 1 KB ve senkron.
- **Güvenlik**: Kullanıcı girdisi içeren çeviri/metinler Blade'de escape; HTML içeren açıklamalar sadece config/lang'dan (geliştirici kontrolünde). Log endpoint'i sadece beklenen anahtarları kabul eder (FormRequest validation).
- **Gizlilik**: Paket kendisi hiçbir dış servise istek atmaz, CDN kullanmaz.

---

## 7. Dizin yapısı

```
config/cookie-consent.php               # tüm ayarlar (kategoriler, google, ui, assets, logging, route)
database/migrations/create_consent_logs_table.php   # publishesMigrations ile timestamp'li yayınlanır
lang/{tr,en}/cookie-consent.php
resources/views/head.blade.php          # @cookieConsentHead: inline Consent Mode script + (ops.) GTM/gtag + CSS
resources/views/scripts.blade.php       # @cookieConsentScripts: JSON config + core JS (+ alpine)
resources/views/components/             # anonim bileşenler: banner, preferences-button, cookie-table, script
resources/js/core/                      # vanilla TS çekirdek: manager, ui, cookies, consent-mode, scripts, types
resources/js/boot.ts | browser.ts | index.ts   # auto-boot (IIFE) ve ESM girişleri
resources/js/adapters/alpine*.ts        # opsiyonel $store.cookieConsent
resources/css/cookie-consent.css        # .cc- önekli, CSS değişkenli tema
dist/                                   # derlenmiş JS/CSS — COMMIT EDİLİR (npm run build)
routes/web.php                          # GET assets/{file} (hash'li cache), POST / (sadece logging açıkken)
src/CookieConsent.php                   # scoped servis: kategoriler, state, consent mode eşleme, client config
src/Support/{Category,CookieDefinition,ConsentState}.php
src/Http/Controllers/{AssetController,ConsentLogController}.php
src/Console/{Install,Export,Prune}Command.php
tests/{Unit,Feature}  (TestCase)  tests/Logging (LoggingTestCase: logging açık + sqlite)
tests-js/                               # Vitest + jsdom
docs/images/                            # README ekran görüntüleri (headless Chrome ile üretildi)
docs/ai/install-prompt.{en,tr}.md       # kullanıcıların AI asistanına yapıştıracağı kurulum promptları
llms.txt                                # AI ajanları için kısa API özeti (Composer paketine DAHİL)
```

Dokümantasyon senkronu: Public API, direktif, config anahtarı veya .env değişkeni değiştiğinde
`README.md`, `README.tr.md`, `llms.txt` ve `docs/ai/*` dosyalarını birlikte güncelle. README'deki
promptlar `docs/ai/*` dosyalarının kısaltılmış kopyalarıdır.
README görselleri `raw.githubusercontent.com/hakanispirli/webmarka-cookie-consent/main/docs/images/...` mutlak URL'leriyle
kullanılır (docs/ Composer arşivinden hariç tutulduğu için Packagist'te de çalışsın diye).

Önemli uygulama detayları:
- Head inline script'i `resources/views/head.blade.php` içinde elle yazılmış ES5 JS'tir (derlenmez); JS çekirdeğiyle **aynı** cookie formatını ve "tip ancak onu kullanan tüm kategoriler onaylıysa granted" kuralını uygular. Birini değiştirirsen diğerini + `CookieConsent::consentModeFor()` (PHP) kodunu da güncelle.
- Cookie formatı: `encodeURIComponent(JSON)` → `{v:1,id:uuid,pv,c:{kategori:bool},ts}`. PHP `ConsentState::fromCookie()` okur.
- UI, document üzerinde event delegation kullanır (`data-cc-action`), bu yüzden Livewire/Turbo DOM değişimlerinde yeniden bağlama gerekmez.
- jsdom'da script'ler ayrı realm'de çalışır: testlerde `window.x` yerine DOM attribute ile doğrula.
- Blade `@else` öncesinde boşluk ister (`@consent('x') a @else b @endconsent`).

---

## 8. Geliştirme komutları

```bash
composer install && npm install
composer test            # Pest (47+ test)
composer analyse         # PHPStan/Larastan level 8 (src, routes)
composer format          # Pint (laravel preset, strict_types)
npm run typecheck        # tsc --noEmit
npm test                 # Vitest
npm run build            # esbuild → dist/ (her JS/CSS değişikliğinden sonra çalıştır ve commit et)
```

CI (`.github/workflows/tests.yml`): PHP 8.3/8.4/8.5 × prefer-lowest/stable, PHPStan + Pint, JS typecheck/test ve `dist/` güncellik kontrolü (`npm ci` + build + `git diff --exit-code dist/`).

---

## 9. Kodlama kuralları

- Kod, yorumlar, commit mesajları, README: **İngilizce** (uluslararası katkı için). README'nin ayrıca `README.tr.md` Türkçe versiyonu olur. Varsayılan dil dosyaları `tr` ve `en`.
- PHP: `declare(strict_types=1);`, final sınıflar (genişletme noktası gerekmedikçe), PSR-12/Pint, PHPStan level 8.
- JS: TypeScript strict, runtime bağımlılık **yok**. Public API değişiklikleri semver'e tabi.
- Her davranış değişikliği test ile gelir. Özellikle test edilmesi gerekenler:
  - Onay yokken `consent default` tüm opsiyonelleri `denied` üretir.
  - Kayıtlı onay varken `update` senkron ve GTM'den önce çalışır.
  - Reddet butonu Kabul ile aynı katmanda render edilir.
  - Opsiyonel kategoriler varsayılan işaretsiz.
  - `policy_version` değişince banner tekrar çıkar.
  - `text/plain` script'ler sadece ilgili kategori onaylıyken aktive olur, geri alınınca tekrar çalışmaz.
- Hukuki iddia içeren README/metinlerde kaynak linki ver ve "hukuki danışmanlık değildir" uyarısını koru.

---

## 10. Açık konular / yapılacak doğrulamalar

- [ ] KVKK Çerez Rehberi (Temmuz 2025) resmi PDF'ini oku; Bölüm 4'teki "Doğrulanmalı" maddeleri teyit et/düzelt.
- [ ] KVKK Yurt Dışı Aktarım Rehberi (02.01.2025) — Google hizmetleri için README notunu netleştir.
- [x] Paket adı: `webmarka/cookie-consent`, repo: `hakanispirli/webmarka-cookie-consent`.
- [x] Varsayılan Consent Mode modu: **advanced** (config ile basic seçilebilir).
- [x] Geo-targeting: v1'de sadece Consent Mode `region_defaults`; banner herkese gösterilir.
- [ ] Faz 2: otomatik script engelleme (MutationObserver), `cookie-consent:scan`, GTM Community Template, Filament paneli, ek diller.
