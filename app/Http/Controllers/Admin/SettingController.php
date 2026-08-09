<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\OpeningHour;
use App\Models\SiteSetting;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Paramètres du site et horaires d'appel (codex §35).
 */
class SettingController extends Controller
{
    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function edit(): View
    {
        $this->authorize(Permission::ManageSettings->value);

        return view('admin.settings.edit', [
            'groups' => SiteSetting::query()
                ->orderBy('group')
                ->orderBy('position')
                ->get()
                ->groupBy('group'),
            'openingHours' => OpeningHour::query()->ordered()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize(Permission::ManageSettings->value);

        $settings = SiteSetting::query()->get()->keyBy('key');

        $rules = [];

        foreach ($settings as $key => $setting) {
            $rules["settings.{$key}"] = match ($setting->type) {
                'boolean' => ['nullable', 'boolean'],
                'integer' => ['nullable', 'integer'],
                'text' => ['nullable', 'string', 'max:5000'],
                default => ['nullable', 'string', 'max:1000'],
            };
        }

        $validated = $request->validate($rules)['settings'] ?? [];

        DB::transaction(function () use ($settings, $validated, $request): void {
            foreach ($settings as $key => $setting) {
                $value = $setting->type === 'boolean'
                    ? ($request->boolean("settings.{$key}") ? '1' : '0')
                    : ($validated[$key] ?? null);

                $setting->update([
                    'value' => $value,
                    'updated_by' => $request->user()->getKey(),
                ]);
            }
        });

        $this->settings->flush();

        return back()
            ->with('status', __('admin.settings.updated'))
            ->with('status_variant', 'success');
    }

    /**
     * Enregistre les horaires d'appel, un créneau par jour de la semaine.
     */
    public function updateHours(Request $request): RedirectResponse
    {
        $this->authorize(Permission::ManageSettings->value);

        $data = $request->validate([
            'hours' => ['required', 'array'],
            'hours.*.weekday' => ['required', 'integer', 'between:1,7'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i', 'after:hours.*.opens_at'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
            'hours.*.note' => ['nullable', 'string', 'max:200'],
        ], attributes: [
            'hours.*.opens_at' => 'l’heure d’ouverture',
            'hours.*.closes_at' => 'l’heure de fermeture',
        ]);

        DB::transaction(function () use ($data): void {
            OpeningHour::query()->delete();

            foreach ($data['hours'] as $index => $hour) {
                $closed = (bool) ($hour['is_closed'] ?? false)
                    || blank($hour['opens_at'])
                    || blank($hour['closes_at']);

                OpeningHour::create([
                    'weekday' => (int) $hour['weekday'],
                    'opens_at' => $closed ? null : $hour['opens_at'],
                    'closes_at' => $closed ? null : $hour['closes_at'],
                    'is_closed' => $closed,
                    'note' => $hour['note'] ?? null,
                    'position' => $index,
                ]);
            }
        });

        $this->settings->flush();

        return back()
            ->with('status', __('admin.settings.hours_updated'))
            ->with('status_variant', 'success');
    }
}
