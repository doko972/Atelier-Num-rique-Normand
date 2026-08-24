<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\PartnershipRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Suppression définitive d'une demande.
 *
 * Elle existe pour les essais de mise en service et les doublons évidents —
 * ce qui n'a jamais correspondu à une personne réelle. Pour une demande
 * authentique, l'anonymisation est la bonne réponse : elle efface la personne
 * en gardant la trace, ce qu'attend le RGPD.
 *
 * L'effacement emporte les enregistrements de consentement et les notes
 * rattachés : les laisser derrière produirait un registre pointant vers une
 * demande qui n'existe plus.
 *
 * L'opération est tracée dans le journal d'audit **avant** l'effacement,
 * puisqu'il ne restera rien à décrire ensuite.
 */
class RequestEraser
{
    public static function erase(Appointment|ContactRequest|PartnershipRequest $request): void
    {
        AuditLog::record(action: 'delete', subject: $request);

        Log::channel('admin')->notice('Demande supprimée définitivement.', [
            'type' => $request::class,
            'reference' => $request->reference,
        ]);

        DB::transaction(function () use ($request): void {
            $request->consentLogs()->delete();

            if ($request instanceof Appointment) {
                $request->notes()->delete();
            }

            $request->forceDelete();
        });
    }
}
