<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webmarka\CookieConsent\CookieConsent;
use Webmarka\CookieConsent\Enums\ConsentAction;

final class StoreConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cookieConsent = $this->container->make(CookieConsent::class);

        $rules = [
            'id' => ['required', 'uuid'],
            'action' => ['required', Rule::enum(ConsentAction::class)],
            'policy_version' => ['required', 'string', Rule::in([$cookieConsent->policyVersion()])],
            'categories' => ['required', 'array:'.implode(',', array_keys($cookieConsent->categories()))],
            'url' => ['nullable', 'string', 'max:2048'],
        ];

        foreach (array_keys($cookieConsent->categories()) as $key) {
            $rules['categories.'.$key] = ['required', 'boolean'];
        }

        return $rules;
    }
}
