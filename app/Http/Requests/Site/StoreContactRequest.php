<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Enums\ContactPreference;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Message envoyé depuis la page Contact.
 */
class StoreContactRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->antiSpamRules(),

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => $this->phoneRules(required: false),
            'email' => $this->emailRules(),
            'municipality_id' => ['nullable', 'integer', Rule::exists('municipalities', 'id')->whereNull('deleted_at')],

            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'contact_preference' => ['required', Rule::enum(ContactPreference::class)],
            'voice_message_allowed' => ['nullable', 'boolean'],

            'consent' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->withAntiSpam($validator);

        $validator->after(function (Validator $validator): void {
            // Sans téléphone ni adresse électronique, aucune réponse ne serait
            // possible : on demande au moins l'un des deux.
            if (blank($this->input('phone')) && blank($this->input('email'))) {
                $validator->errors()->add('phone', __('validation_custom.contact_channel_required'));
            }

            if (
                $this->input('contact_preference') === ContactPreference::Email->value
                && blank($this->input('email'))
            ) {
                $validator->errors()->add('email', __('validation_custom.email_required_for_preference'));
            }

            if (
                in_array($this->input('contact_preference'), [
                    ContactPreference::Phone->value,
                    ContactPreference::Sms->value,
                ], strict: true)
                && blank($this->input('phone'))
            ) {
                $validator->errors()->add('phone', __('validation_custom.phone_required_for_preference'));
            }
        });
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
    public function contactData(): array
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
