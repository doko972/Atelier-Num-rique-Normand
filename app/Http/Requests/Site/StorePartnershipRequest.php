<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Enums\PartnerType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Demande émanant d'une commune, d'un CCAS ou d'une association (codex §15).
 */
class StorePartnershipRequest extends PublicFormRequest
{
    /**
     * Types de prestation proposés dans le formulaire.
     *
     * @return array<string, string>
     */
    public static function needOptions(): array
    {
        return __('site.partnership.needs');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->antiSpamRules(),

            'organisation_name' => ['required', 'string', 'max:200'],
            'organisation_type' => ['required', Rule::enum(PartnerType::class)],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_role' => ['nullable', 'string', 'max:150'],
            'email' => $this->emailRules(required: true),
            'phone' => $this->phoneRules(required: false),

            'municipality_id' => ['nullable', 'integer', Rule::exists('municipalities', 'id')->whereNull('deleted_at')],
            'municipality_name' => ['nullable', 'string', 'max:150'],

            'needs' => ['required', 'array', 'min:1'],
            'needs.*' => ['string', Rule::in(array_keys(self::needOptions()))],

            'audience' => ['nullable', 'string', 'max:200'],
            'estimated_participants' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'desired_period' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:3000'],
            'quote_requested' => ['nullable', 'boolean'],

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
    public function partnershipData(): array
    {
        $data = $this->safe()->except([
            'consent',
            self::HONEYPOT_FIELD,
            self::TIMESTAMP_FIELD,
        ]);

        $data['quote_requested'] = (bool) ($data['quote_requested'] ?? false);

        return $data;
    }
}
