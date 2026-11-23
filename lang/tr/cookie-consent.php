<?php

declare(strict_types=1);

return [

    'banner' => [
        'title' => 'Çerez tercihleriniz',
        'description' => 'Sitemizin çalışması için zorunlu çerezleri kullanıyoruz. Açık rızanız olursa analitik ve pazarlama çerezleri de kullanırız. Tercihinizi istediğiniz zaman değiştirebilirsiniz.',
    ],

    'buttons' => [
        'accept_all' => 'Tümünü kabul et',
        'reject_all' => 'Tümünü reddet',
        'preferences' => 'Tercihleri yönet',
        'save' => 'Seçimlerimi kaydet',
        'close' => 'Kapat',
        'close_reject' => 'Kapat (isteğe bağlı çerezleri reddet)',
        'cookie_settings' => 'Çerez ayarları',
    ],

    'links' => [
        'privacy_policy' => 'Aydınlatma metni',
        'cookie_policy' => 'Çerez politikası',
    ],

    'preferences' => [
        'title' => 'Çerez tercihleri',
        'description' => 'Aşağıdan hangi çerez kategorilerine izin verdiğinizi seçebilirsiniz. Zorunlu çerezler sitenin çalışması için gereklidir ve kapatılamaz. Diğer kategoriler yalnızca açık rızanızla etkinleşir; rızanızı istediğiniz zaman geri alabilirsiniz.',
        'data_controller' => 'Veri sorumlusu',
        'always_active' => 'Her zaman etkin',
        'cookie_details' => 'Çerez ayrıntıları (:count)',
    ],

    'categories' => [
        'necessary' => [
            'title' => 'Zorunlu çerezler',
            'description' => 'Oturum yönetimi, güvenlik ve çerez tercihinizin saklanması gibi sitenin temel işlevleri için gereklidir. Bu çerezler için rıza gerekmez.',
        ],
        'functional' => [
            'title' => 'İşlevsel çerezler',
            'description' => 'Dil ve bölge tercihleriniz gibi seçimlerinizi hatırlayarak size daha kişisel bir deneyim sunar.',
        ],
        'analytics' => [
            'title' => 'Analitik çerezler',
            'description' => 'Ziyaretçilerin siteyi nasıl kullandığını anlamamıza ve sitemizi geliştirmemize yardımcı olur (ör. Google Analytics).',
        ],
        'marketing' => [
            'title' => 'Pazarlama çerezleri',
            'description' => 'Size ilgi alanlarınıza uygun reklamlar göstermek ve reklam kampanyalarının etkinliğini ölçmek için kullanılır (ör. Google Ads).',
        ],
    ],

    'table' => [
        'name' => 'Çerez',
        'category' => 'Kategori',
        'provider' => 'Sağlayıcı',
        'purpose' => 'Amaç',
        'duration' => 'Süre',
        'transfers_abroad' => 'Yurt dışı aktarım',
        'first_party' => 'Birinci taraf',
        'no' => 'Yok',
    ],

    'cookies' => [
        'session' => 'Oturumunuzu sürdürür.',
        'xsrf' => 'Form gönderimlerini sahte isteklere (CSRF) karşı korur.',
        'consent' => 'Çerez tercihlerinizi saklar.',
        'ga' => 'Ziyaretçileri ayırt ederek site kullanım istatistikleri oluşturur.',
        'gcl' => 'Reklam tıklamalarını ve dönüşümleri ölçer.',
    ],

    'durations' => [
        'session' => 'Oturum süresince',
        'ninety_days' => '90 gün',
        'one_year' => '1 yıl',
        'two_years' => '2 yıl',
    ],

];
