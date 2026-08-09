<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsentPurpose;
use App\Models\ConsentLog;
use App\Support\Privacy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registre des consentements (codex §27).
 *
 * On enregistre le texte exact affiché au moment de la case cochée : c'est
 * cette formulation qui fait foi, et non la version actuelle du formulaire.
 */
class ConsentService
{
    /**
     * Version courante des mentions de consentement. À incrémenter dès que le
     * texte affiché change, afin de conserver un historique exploitable.
     */
    public const string CURRENT_VERSION = '1.0';

    public function record(
        Model $subject,
        ConsentPurpose $purpose,
        ?Request $request = null,
        bool $granted = true,
    ): ConsentLog {
        $request ??= request();

        return ConsentLog::create([
            'consentable_type' => $subject->getMorphClass(),
            'consentable_id' => $subject->getKey(),
            'purpose' => $purpose,
            'statement' => $this->statementFor($purpose),
            'version' => self::CURRENT_VERSION,
            'granted' => $granted,
            'granted_at' => now(),
            'ip_hash' => Privacy::hashIp($request->ip()),
            'user_agent_hash' => Privacy::hashUserAgent($request->userAgent()),
        ]);
    }

    /**
     * Texte affiché à la personne pour la finalité concernée.
     */
    public function statementFor(ConsentPurpose $purpose): string
    {
        return __("consent.statements.{$purpose->value}");
    }
}
