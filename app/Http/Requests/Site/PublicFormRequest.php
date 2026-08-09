<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Base des formulaires publics.
 *
 * Protection anti-spam accessible (codex §25) : aucun captcha visuel, qui
 * exclurait une partie du public. On combine un champ leurre invisible et un
 * délai minimal de remplissage — deux signaux qu'un automate franchit, mais
 * qu'un être humain ne rencontre jamais.
 */
abstract class PublicFormRequest extends FormRequest
{
    /**
     * Nom du champ leurre. Il est masqué visuellement et retiré de l'arbre
     * d'accessibilité : un lecteur d'écran ne l'annonce donc pas.
     */
    public const string HONEYPOT_FIELD = 'site_web_complementaire';

    /**
     * Champ horodaté à l'affichage du formulaire.
     */
    public const string TIMESTAMP_FIELD = 'formulaire_ouvert_a';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles communes à tous les formulaires publics.
     *
     * @return array<string, mixed>
     */
    protected function antiSpamRules(): array
    {
        return [
            self::HONEYPOT_FIELD => ['nullable', 'string', 'max:255'],
            self::TIMESTAMP_FIELD => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Vérifications anti-spam appliquées après la validation des champs.
     */
    protected function withAntiSpam(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (filled($this->input(self::HONEYPOT_FIELD))) {
                $this->rejectAsSpam($validator, 'champ leurre rempli');

                return;
            }

            if ($this->submittedTooFast()) {
                $this->rejectAsSpam($validator, 'envoi trop rapide');
            }
        });
    }

    /**
     * Un formulaire renvoyé en moins de quelques secondes n'a pas pu être lu.
     */
    protected function submittedTooFast(): bool
    {
        $openedAt = $this->input(self::TIMESTAMP_FIELD);

        if (blank($openedAt) || ! is_numeric($openedAt)) {
            // Absence d'horodatage : on ne bloque pas, le champ peut avoir été
            // filtré par un navigateur ancien ou une extension.
            return false;
        }

        return (time() - (int) $openedAt) < (int) config('site.security.min_form_seconds');
    }

    protected function rejectAsSpam(Validator $validator, string $reason): void
    {
        Log::channel('securite')->info('Envoi de formulaire rejeté.', [
            'form' => static::class,
            'reason' => $reason,
        ]);

        // Le message reste neutre : il ne faut ni accuser la personne, ni
        // indiquer à un automate quel contrôle il a déclenché.
        $validator->errors()->add(
            self::HONEYPOT_FIELD,
            __('validation_custom.spam_detected'),
        );
    }

    /**
     * Règles d'une adresse électronique.
     *
     * La vérification DNS est volontairement écartée : elle ajoute un appel
     * réseau à chaque envoi de formulaire, et rejette des adresses pourtant
     * valides lorsque la résolution échoue temporairement. Pour ce public, une
     * adresse valide refusée coûte bien plus cher qu'une faute de frappe
     * acceptée — le téléphone reste de toute façon le canal principal.
     *
     * @return array<int, mixed>
     */
    protected function emailRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'email:rfc',
            'max:180',
        ];
    }

    /**
     * Règles d'un numéro de téléphone français, saisi avec ou sans espaces.
     *
     * @return array<int, mixed>
     */
    protected function phoneRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:30',
            'regex:/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.\-]*\d{2}){4}$/',
        ];
    }
}
