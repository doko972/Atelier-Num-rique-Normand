<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideStep;
use App\Models\PracticalGuide;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Étapes numérotées d'une fiche pratique.
 *
 * Les étapes forment le cœur pédagogique de la fiche : une action par étape,
 * une capture d'écran, et si besoin une astuce.
 */
class GuideStepController extends Controller
{
    public function index(PracticalGuide $guide): View
    {
        $this->authorize('update', $guide);

        return view('admin.guides.steps', [
            'guide' => $guide->load('steps'),
        ]);
    }

    public function store(Request $request, PracticalGuide $guide): RedirectResponse
    {
        $this->authorize('update', $guide);

        $data = $this->validated($request);

        $data['position'] ??= (int) $guide->steps()->max('position') + 1;

        $guide->steps()->create($data);

        return redirect()
            ->route('admin.guides.steps.index', $guide)
            ->with('status', __('admin.guides.step_added'))
            ->with('status_variant', 'success');
    }

    public function update(Request $request, PracticalGuide $guide, GuideStep $step): RedirectResponse
    {
        $this->authorize('update', $guide);
        abort_unless($step->practical_guide_id === $guide->getKey(), 404);

        $step->update($this->validated($request));

        return redirect()
            ->route('admin.guides.steps.index', $guide)
            ->with('status', __('admin.guides.step_updated'))
            ->with('status_variant', 'success');
    }

    public function destroy(PracticalGuide $guide, GuideStep $step): RedirectResponse
    {
        $this->authorize('update', $guide);
        abort_unless($step->practical_guide_id === $guide->getKey(), 404);

        $step->delete();

        return redirect()
            ->route('admin.guides.steps.index', $guide)
            ->with('status', __('admin.guides.step_deleted'))
            ->with('status_variant', 'success');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'tip' => ['nullable', 'string', 'max:1000'],
            // Le texte alternatif accompagne obligatoirement toute capture.
            'image_alt' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:1', 'max:200'],
        ], attributes: [
            'title' => 'le titre de l’étape',
            'body' => 'le texte de l’étape',
            'tip' => 'l’astuce',
            'image_alt' => 'la description de l’image',
            'position' => 'le numéro de l’étape',
        ]);
    }
}
