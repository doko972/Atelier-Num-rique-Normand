<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Socle des CRUD de contenu du back-office.
 *
 * Les quatorze ressources éditoriales partagent exactement le même
 * comportement : lister avec recherche et tri, créer, modifier, supprimer
 * (corbeille), restaurer. Chaque contrôleur concret se contente de décrire
 * son modèle et ses champs.
 */
abstract class AbstractResourceController extends Controller
{
    /**
     * Classe du modèle géré.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Segment de nom de route (`admin.services.index` => `services`).
     */
    abstract protected function routeKey(): string;

    /**
     * Champs du formulaire.
     *
     * @return array<int, Field>
     */
    abstract protected function fields(): array;

    /**
     * Libellés affichés (titre de page, singulier, pluriel).
     *
     * @return array{title: string, singular: string, plural: string, intro?: string}
     */
    abstract protected function labels(): array;

    /**
     * Colonne de tri par défaut.
     */
    protected function defaultSort(): string
    {
        return 'position';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }

    /**
     * Relations chargées dans la liste, pour éviter les requêtes en cascade.
     *
     * @return array<int, string>
     */
    protected function listRelations(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $this->authorize('viewAny', $this->modelClass());

        $validated = $request->validate([
            'recherche' => ['nullable', 'string', 'max:100'],
            'tri' => ['nullable', 'string', 'max:60'],
            'sens' => ['nullable', 'in:asc,desc'],
            'corbeille' => ['nullable', 'boolean'],
        ]);

        $sort = $this->resolveSortColumn($validated['tri'] ?? null);
        $direction = $validated['sens'] ?? $this->defaultSortDirection();

        $query = $this->modelClass()::query()->with($this->listRelations());

        if (($validated['corbeille'] ?? false) && $this->usesSoftDeletes()) {
            $query->onlyTrashed();
        }

        $this->applySearch($query, $validated['recherche'] ?? null);

        $records = $query
            ->orderBy($sort, $direction)
            ->paginate((int) config('site.per_page.admin'))
            ->withQueryString();

        return view('admin.resource.index', [
            'records' => $records,
            'fields' => $this->listedFields(),
            'labels' => $this->labels(),
            'routeKey' => $this->routeKey(),
            'filters' => $validated,
            'sort' => $sort,
            'direction' => $direction,
            'softDeletes' => $this->usesSoftDeletes(),
            'modelClass' => $this->modelClass(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', $this->modelClass());

        $model = new ($this->modelClass());

        return view('admin.resource.form', [
            'record' => $model,
            'fields' => $this->fields(),
            'labels' => $this->labels(),
            'routeKey' => $this->routeKey(),
            'isNew' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', $this->modelClass());

        $data = $this->validateFields($request);

        $record = $this->modelClass()::create($data);

        Log::channel('admin')->info('Contenu créé.', [
            'model' => class_basename($this->modelClass()),
            'id' => $record->getKey(),
        ]);

        return redirect()
            ->route("admin.{$this->routeKey()}.index")
            ->with('status', __('admin.common.created', ['name' => $this->labels()['singular']]))
            ->with('status_variant', 'success');
    }

    public function edit(string $key): View
    {
        $record = $this->findOrFail($key);

        $this->authorize('update', $record);

        return view('admin.resource.form', [
            'record' => $record,
            'fields' => $this->fields(),
            'labels' => $this->labels(),
            'routeKey' => $this->routeKey(),
            'isNew' => false,
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $record = $this->findOrFail($key);

        $this->authorize('update', $record);

        $record->update($this->validateFields($request, $record));

        return redirect()
            ->route("admin.{$this->routeKey()}.index")
            ->with('status', __('admin.common.updated', ['name' => $this->labels()['singular']]))
            ->with('status_variant', 'success');
    }

    public function destroy(string $key): RedirectResponse
    {
        $record = $this->findOrFail($key);

        $this->authorize('delete', $record);

        $record->delete();

        Log::channel('admin')->info('Contenu supprimé.', [
            'model' => class_basename($this->modelClass()),
            'id' => $record->getKey(),
        ]);

        return redirect()
            ->route("admin.{$this->routeKey()}.index")
            ->with('status', __('admin.common.deleted', ['name' => $this->labels()['singular']]))
            ->with('status_variant', 'success');
    }

    public function restore(int $id): RedirectResponse
    {
        abort_unless($this->usesSoftDeletes(), 404);

        $record = $this->modelClass()::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $record);

        $record->restore();

        return redirect()
            ->route("admin.{$this->routeKey()}.index")
            ->with('status', __('admin.common.restored', ['name' => $this->labels()['singular']]))
            ->with('status_variant', 'success');
    }

    // -------------------------------------------------------------------------
    // Outils internes
    // -------------------------------------------------------------------------

    /**
     * Valide la requête à partir des champs déclarés.
     *
     * @return array<string, mixed>
     */
    protected function validateFields(Request $request, ?Model $record = null): array
    {
        $rules = [];
        $attributes = [];

        foreach ($this->fields() as $field) {
            $rules[$field->name] = $this->rulesFor($field, $record);
            $attributes[$field->name] = $field->label;
        }

        $validated = $request->validate($rules, attributes: $attributes);

        foreach ($this->fields() as $field) {
            if ($field->type === 'boolean') {
                $validated[$field->name] = $request->boolean($field->name);
            }

            // Une saisie « une valeur par ligne » devient un tableau JSON.
            if ($field->type === 'lines') {
                $validated[$field->name] = collect(preg_split('/\r\n|\r|\n/', (string) ($validated[$field->name] ?? '')))
                    ->map(fn (string $line): string => trim($line))
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        return $validated;
    }

    /**
     * Complète les règles avec l'unicité lorsque le champ s'appelle « slug ».
     *
     * @return array<int, mixed>
     */
    protected function rulesFor(Field $field, ?Model $record): array
    {
        $rules = $field->rules;

        if ($field->name === 'slug') {
            $unique = Rule::unique(
                (new ($this->modelClass()))->getTable(),
                'slug',
            );

            if ($record?->exists) {
                $unique->ignore($record->getKey());
            }

            $rules[] = $unique;
        }

        return $rules;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applySearch(Builder $query, ?string $terms): void
    {
        if (blank($terms)) {
            return;
        }

        $columns = collect($this->fields())
            ->filter(fn (Field $field): bool => $field->searchable)
            ->pluck('name');

        if ($columns->isEmpty()) {
            return;
        }

        $query->where(function (Builder $query) use ($columns, $terms): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$terms}%");
            }
        });
    }

    /**
     * @return array<int, Field>
     */
    protected function listedFields(): array
    {
        return array_values(array_filter(
            $this->fields(),
            fn (Field $field): bool => $field->inList,
        ));
    }

    /**
     * N'accepte que les colonnes réellement déclarées : le tri ne doit jamais
     * pouvoir désigner une colonne arbitraire.
     */
    protected function resolveSortColumn(?string $requested): string
    {
        $allowed = collect($this->fields())
            ->filter(fn (Field $field): bool => $field->inList)
            ->pluck('name')
            ->push('id')
            ->push('created_at')
            ->push('updated_at')
            ->all();

        if ($requested !== null && in_array($requested, $allowed, strict: true)) {
            return $requested;
        }

        return $this->defaultSort();
    }

    protected function usesSoftDeletes(): bool
    {
        return method_exists($this->modelClass(), 'bootSoftDeletes');
    }

    /**
     * Résout l'enregistrement à partir du paramètre d'URL.
     *
     * La liaison implicite de Laravel ne peut pas s'appliquer ici : le type
     * du modèle n'est connu qu'à l'exécution, chaque contrôleur concret
     * déclarant le sien.
     */
    protected function findOrFail(string $key): Model
    {
        $model = new ($this->modelClass());

        $query = $this->modelClass()::query();

        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        return $query->where($model->getRouteKeyName(), $key)->firstOrFail();
    }
}
