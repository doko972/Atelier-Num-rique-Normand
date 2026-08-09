<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Enums\AgeRange;
use App\Enums\DeviceType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Inscription à un atelier depuis le site public (codex §10).
 */
class StoreWorkshopRegistrationRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->antiSpamRules(),

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => $this->phoneRules(),
            'email' => $this->emailRules(),

            'municipality_id' => ['nullable', 'integer', Rule::exists('municipalities', 'id')->whereNull('deleted_at')],
            'municipality_name' => ['nullable', 'string', 'max:150'],

            'age_range' => ['nullable', Rule::enum(AgeRange::class)],
            'device' => ['nullable', Rule::enum(DeviceType::class)],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            'voice_message_allowed' => ['nullable', 'boolean'],

            'consent' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->withAntiSpam($validator);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('validation_custom.attributes');
    }

    /**
     * @return array<string, mixed>
     */
    public function registrationData(): array
    {
        $data = $this->safe()->except([
            'consent',
            self::HONEYPOT_FIELD,
            self::TIMESTAMP_FIELD,
        ]);

        $data['voice_message_allowed'] = (bool) ($data['voice_message_allowed'] ?? false);

        return $data;
    }
}
