<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\DownloadableFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Téléchargement des documents publics.
 *
 * Le fichier n'est jamais servi directement depuis le disque : passer par le
 * contrôleur permet de vérifier qu'il est bien public et de compter les
 * téléchargements sans déposer le moindre traceur.
 */
class DownloadController extends Controller
{
    public function __invoke(DownloadableFile $file): StreamedResponse
    {
        abort_unless($file->is_public, 404);
        abort_unless($file->exists(), 404);

        $file->increment('download_count');

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name,
            ['Content-Type' => $file->mime_type],
        );
    }
}
