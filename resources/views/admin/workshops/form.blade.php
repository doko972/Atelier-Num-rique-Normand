@extends('layouts.admin')

@section('title', ($isNew ? __('admin.common.create') : __('admin.common.edit')).' — '.__('admin.workshops.title'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ $isNew ? 'Nouvel atelier' : 'Modifier l’atelier' }}</h1>

        <a class="btn btn--ghost" href="{{ route('admin.workshops.index') }}">{{ __('admin.common.back') }}</a>
    </div>

    <div class="admin-panel">
        <x-form-errors />

        <p class="form__required-note">{{ __('admin.common.required_fields') }}</p>

        <form
            method="POST"
            action="{{ $isNew ? route('admin.workshops.store') : route('admin.workshops.update', $workshop) }}"
            class="form"
        >
            @csrf
            @unless ($isNew)
                @method('PUT')
            @endunless

            <fieldset class="fieldset">
                <legend class="fieldset__legend">{{ __('admin.workshops.sections.general') }}</legend>

                <x-field name="title" label="Titre de l’atelier" :value="$workshop->title" required />

                <x-field
                    name="slug"
                    label="Adresse de la page"
                    :value="$workshop->slug"
                    help="Laissez vide : elle sera créée à partir du titre."
                />

                <x-field
                    name="workshop_category_id"
                    type="select"
                    label="Thème"
                    :options="$categories->pluck('name', 'id')->all()"
                    :value="$workshop->workshop_category_id"
                    :empty-option="__('admin.common.none')"
                />

                <x-field
                    name="description"
                    type="textarea"
                    label="Description"
                    :value="$workshop->description"
                    :rows="8"
                    required
                    help="Écrivez comme vous présenteriez l’atelier à l’oral."
                />

                <x-field
                    name="objectives"
                    type="textarea"
                    label="Objectifs"
                    :value="is_array($workshop->objectives) ? implode(\"\n\", $workshop->objectives) : $workshop->objectives"
                    :rows="5"
                    help="Un objectif par ligne."
                />

                <x-field
                    name="prerequisites"
                    type="textarea"
                    label="Prérequis"
                    :value="$workshop->prerequisites"
                    :rows="3"
                />

                <x-field
                    name="level"
                    type="select"
                    label="Niveau"
                    :options="$levels"
                    :value="$workshop->level?->value ?? \App\Enums\SkillLevel::Everyone->value"
                    required
                />
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset__legend">{{ __('admin.workshops.sections.schedule') }}</legend>

                <x-field name="date" type="date" label="Date" :value="$workshop->date?->format('Y-m-d')" required />

                <x-field
                    name="start_time"
                    type="time"
                    label="Heure de début"
                    :value="$workshop->start_time ? \Carbon\CarbonImmutable::parse($workshop->start_time)->format('H:i') : null"
                    required
                />

                <x-field
                    name="end_time"
                    type="time"
                    label="Heure de fin"
                    :value="$workshop->end_time ? \Carbon\CarbonImmutable::parse($workshop->end_time)->format('H:i') : null"
                    required
                />

                <x-field
                    name="registration_deadline"
                    type="date"
                    label="Date limite d’inscription"
                    :value="$workshop->registration_deadline?->format('Y-m-d')"
                    help="Laissez vide pour accepter les inscriptions jusqu’au jour de l’atelier."
                />
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset__legend">{{ __('admin.workshops.sections.place') }}</legend>

                <x-field
                    name="location_id"
                    type="select"
                    label="Lieu"
                    :options="$locations->pluck('name', 'id')->all()"
                    :value="$workshop->location_id"
                    :empty-option="__('admin.common.none')"
                />

                <x-field
                    name="municipality_id"
                    type="select"
                    label="Commune"
                    :options="$municipalities->pluck('name', 'id')->all()"
                    :value="$workshop->municipality_id"
                    :empty-option="__('admin.common.none')"
                />

                <x-field
                    name="partner_id"
                    type="select"
                    label="Partenaire organisateur"
                    :options="$partners->pluck('name', 'id')->all()"
                    :value="$workshop->partner_id"
                    :empty-option="__('admin.common.none')"
                />

                <x-checkbox
                    name="is_accessible"
                    label="Locaux accessibles aux personnes à mobilité réduite"
                    :checked="(bool) $workshop->is_accessible"
                />
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset__legend">{{ __('admin.workshops.sections.capacity') }}</legend>

                <x-field
                    name="capacity"
                    type="number"
                    label="Nombre de places"
                    :value="$workshop->capacity ?? 8"
                    min="1"
                    max="200"
                    required
                    help="Le nombre de places restantes est calculé automatiquement à partir des inscriptions."
                />

                <x-checkbox
                    name="waiting_list_enabled"
                    label="Activer la liste d’attente"
                    :checked="$workshop->exists ? (bool) $workshop->waiting_list_enabled : true"
                    description="Les personnes seront prévenues automatiquement dès qu’une place se libère."
                />
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset__legend">{{ __('admin.workshops.sections.practical') }}</legend>

                <x-checkbox
                    name="equipment_provided"
                    label="Le matériel est fourni"
                    :checked="$workshop->exists ? (bool) $workshop->equipment_provided : true"
                />

                <x-checkbox
                    name="own_device_allowed"
                    label="Les participants peuvent apporter leur appareil"
                    :checked="$workshop->exists ? (bool) $workshop->own_device_allowed : true"
                />

                <x-checkbox
                    name="is_free"
                    label="Atelier gratuit"
                    :checked="$workshop->exists ? (bool) $workshop->is_free : true"
                />

                <x-field
                    name="price_cents"
                    type="number"
                    label="Tarif en centimes"
                    :value="$workshop->price_cents"
                    min="0"
                    help="Exemple : 500 pour 5,00 €. Laissez vide si l’atelier est gratuit."
                />

                <x-field name="instructor_name" label="Intervenant" :value="$workshop->instructor_name" />

                <x-field
                    name="image_alt"
                    label="Description de l’image"
                    :value="$workshop->image_alt"
                    help="Décrivez l’image pour les personnes qui ne la voient pas."
                />
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset__legend">{{ __('admin.workshops.sections.publication') }}</legend>

                <x-field
                    name="status"
                    type="select"
                    label="Statut"
                    :options="$statuses"
                    :value="$workshop->status?->value ?? \App\Enums\WorkshopStatus::Draft->value"
                    required
                />

                <x-field name="meta_title" label="Titre pour les moteurs de recherche" :value="$workshop->meta_title" />
                <x-field name="meta_description" label="Description pour les moteurs de recherche" :value="$workshop->meta_description" />
            </fieldset>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary btn--lg">{{ __('admin.common.save') }}</button>
                <a class="btn btn--ghost" href="{{ route('admin.workshops.index') }}">{{ __('admin.common.cancel') }}</a>
            </div>
        </form>
    </div>

    {{-- L'annulation prévient les inscrits : elle ne se confond pas avec la
         simple modification du statut. --}}
    @unless ($isNew)
        @can('cancel', $workshop)
            <div class="admin-panel">
                <h2 class="admin-panel__title">{{ __('admin.workshops.cancel_title') }}</h2>
                <p class="text-small text-muted">{{ __('admin.workshops.cancel_help') }}</p>

                <form
                    method="POST"
                    action="{{ route('admin.workshops.cancel', $workshop) }}"
                    data-confirm="Confirmez-vous l’annulation de cet atelier ? Les personnes inscrites seront prévenues."
                >
                    @csrf

                    <x-field
                        name="cancellation_reason"
                        type="textarea"
                        :label="__('admin.workshops.cancel_reason')"
                        :value="$workshop->cancellation_reason"
                        :rows="3"
                        required
                    />

                    <button type="submit" class="btn btn--danger">{{ __('admin.workshops.cancel_title') }}</button>
                </form>
            </div>
        @endcan
    @endunless
@endsection
