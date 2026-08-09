<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Enums\AppointmentType;
use App\Enums\ContactPreference;
use App\Enums\DeviceType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Demande de rendez-vous envoyée depuis le site public (codex §11).
 *
 * L'adresse électronique reste facultative : une personne qui n'en possède pas
 * doit pouvoir demander un rendez-vous.
 */
class StoreAppointmentRequest extends PublicFormRequest
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

            'type' => ['required', Rule::enum(AppointmentType::class)],
            'need_description' => ['required', 'string', 'min:10', 'max:2000'],
            'device' => ['nullable', Rule::enum(DeviceType::class)],
            'availability' => ['nullable', 'string', 'max:500'],
            'contact_preference' => ['required', Rule::enum(ContactPreference::class)],

            'home_visit_requested' => ['nullable', 'boolean'],
            'has_mobility_difficulty' => ['nullable', 'boolean'],
            'voice_message_allowed' => ['nullable', 'boolean'],

            'consent' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->withAntiSpam($validator);

        $validator->after(function (Validator $validator): void {
            // Choisir « par courrier électronique » sans donner d'adresse
            // rendrait toute réponse impossible.
            if (
                $this->input('contact_preference') === ContactPreference::Email->value
                && blank($this->input('email'))
            ) {
                $validator->errors()->add('email', __('validation_custom.email_required_for_preference'));
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
     * Données prêtes pour la création du modèle.
     *
     * @return array<string, mixed>
     */
    public function appointmentData(): array
    {
        $data = $this->safe()->except([
            'consent',
            self::HONEYPOT_FIELD,
            self::TIMESTAMP_FIELD,
        ]);

        // Les cases à cocher absentes valent « non » et non « null ».
        foreach (['home_visit_requested', 'has_mobility_difficulty', 'voice_message_allowed'] as $flag) {
            $data[$flag] = (bool) ($data[$flag] ?? false);
        }

        // Une intervention à domicile est implicite lorsque c'est le type
        // de rendez-vous choisi.
        if (($data['type'] ?? null) === AppointmentType::Home->value) {
            $data['home_visit_requested'] = true;
        }

        return $data;
    }
}
