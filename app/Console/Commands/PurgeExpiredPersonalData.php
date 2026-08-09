<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ConsentLog;
use App\Models\DataExportRequest;
use App\Services\GdprService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Purge automatique au terme des durées de conservation (codex §27).
 *
 * Les demandes closes sont anonymisées, pas supprimées : les bilans agrégés
 * remis aux communes restent exacts.
 */
class PurgeExpiredPersonalData extends Command
{
    protected $signature = 'rgpd:purger
                            {--simulation : Affiche le volume concerné sans rien modifier}';

    protected $description = 'Anonymise les données dont la durée de conservation est écoulée.';

    public function handle(GdprService $gdpr): int
    {
        if ($this->option('simulation')) {
            $this->warn('Mode simulation : aucune donnée ne sera modifiée.');
            $this->info('Durées de conservation configurées (en jours) :');

            foreach (config('site.retention') as $key => $days) {
                $this->line("  • {$key} : {$days}");
            }

            return self::SUCCESS;
        }

        $summary = $gdpr->purgeExpiredRecords();

        foreach ($summary as $type => $count) {
            $this->line("{$type} : {$count} enregistrement(s) rendu(s) anonyme(s).");
        }

        $this->purgeLogs();
        $this->purgeExpiredExports();

        $this->info('Purge terminée.');

        return self::SUCCESS;
    }

    /**
     * Supprime les journaux dépassant leur durée de conservation.
     */
    protected function purgeLogs(): void
    {
        $auditDeleted = AuditLog::query()
            ->where('created_at', '<', now()->subDays((int) config('site.retention.audit_logs')))
            ->delete();

        // Les consentements sont conservés plus longtemps : ils constituent la
        // preuve du traitement en cas de contrôle.
        $consentDeleted = ConsentLog::query()
            ->where('granted_at', '<', now()->subDays((int) config('site.retention.consent_logs')))
            ->delete();

        $this->line("journal d’audit : {$auditDeleted} entrée(s) supprimée(s).");
        $this->line("registre des consentements : {$consentDeleted} entrée(s) supprimée(s).");

        Log::channel('rgpd')->info('Purge des journaux.', [
            'audit_logs' => $auditDeleted,
            'consent_logs' => $consentDeleted,
        ]);
    }

    /**
     * Efface les archives d'export RGPD arrivées à expiration : une archive
     * oubliée sur le disque est une fuite de données en puissance.
     */
    protected function purgeExpiredExports(): void
    {
        $requests = DataExportRequest::query()
            ->whereNotNull('export_path')
            ->where('export_expires_at', '<', now())
            ->get();

        foreach ($requests as $request) {
            Storage::disk('local')->delete($request->export_path);

            $request->forceFill([
                'export_path' => null,
                'export_expires_at' => null,
            ])->save();
        }

        $this->line("archives d’export : {$requests->count()} fichier(s) supprimé(s).");
    }
}
