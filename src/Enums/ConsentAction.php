<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Enums;

enum ConsentAction: string
{
    case AcceptAll = 'accept_all';
    case RejectAll = 'reject_all';
    case Custom = 'custom';
}
