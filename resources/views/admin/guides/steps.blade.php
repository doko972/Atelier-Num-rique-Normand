@extends('layouts.admin')

@section('title', __('admin.guides.steps_title', ['title' => $guide->title]))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.guides.steps_title', ['title' => $guide->title]) }}</h1>
            <p>{{ __('admin.guides.steps_intro') }}</p>
        </div>

        <a class="btn btn--ghost" href="{{ route('admin.guides.index') }}">{{ __('admin.common.back') }}</a>
    </div>

    <div class="admin-panel">
        <x-form-errors />

        @if ($guide->steps->isEmpty())
            <p class="table__empty">{{ __('admin.guides.no_steps') }}</p>
        @else
            @foreach ($guide->steps as $step)
                <section class="fieldset" aria-labelledby="etape-{{ $step->id }}">
                    <h2 class="fieldset__legend" id="etape-{{ $step->id }}">
                        Étape {{ $step->position }} — {{ $step->title }}
                    </h2>

                    <form method="POST" action="{{ route('admin.guides.steps.update', [$guide, $step]) }}" class="form">
                        @csrf
                        @method('PUT')

                        <x-field :name="'title'" label="Titre de l’étape" :value="$step->title" required :id="'champ-titre-'.$step->id" />

                        <x-field
                            name="body"
                            type="textarea"
                            label="Texte de l’étape"
                            :value="$step->body"
                            :rows="5"
                            required
                            :id="'champ-texte-'.$step->id"
                        />

                        <x-field
                            name="tip"
                            type="textarea"
                            label="Astuce"
                            :value="$step->tip"
                            :rows="2"
                            :id="'champ-astuce-'.$step->id"
                        />

                        <x-field
                            name="image_alt"
                            label="Description de la capture d’écran"
                            :value="$step->image_alt"
                            :id="'champ-image-'.$step->id"
                            help="Obligatoire dès qu’une image accompagne l’étape."
                        />

                        <x-field
                            name="position"
                            type="number"
                            label="Numéro de l’étape"
                            :value="$step->position"
                            min="1"
                            :id="'champ-position-'.$step->id"
                        />

                        <div class="form__actions">
                            <button type="submit" class="btn btn--primary">{{ __('admin.common.save') }}</button>
                        </div>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('admin.guides.steps.destroy', [$guide, $step]) }}"
                        data-confirm="Confirmez-vous la suppression de cette étape ?"
                        style="margin-top: 0.5rem"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--danger btn--sm">
                            {{ __('admin.common.delete') }}
                            <span class="visually-hidden">l’étape {{ $step->position }}</span>
                        </button>
                    </form>
                </section>
            @endforeach
        @endif
    </div>

    <div class="admin-panel">
        <h2 class="admin-panel__title">{{ __('admin.guides.add_step') }}</h2>

        <form method="POST" action="{{ route('admin.guides.steps.store', $guide) }}" class="form">
            @csrf

            <x-field name="title" label="Titre de l’étape" required id="champ-nouvelle-titre" />
            <x-field name="body" type="textarea" label="Texte de l’étape" :rows="5" required id="champ-nouvelle-texte" />
            <x-field name="tip" type="textarea" label="Astuce" :rows="2" id="champ-nouvelle-astuce" />
            <x-field name="image_alt" label="Description de la capture d’écran" id="champ-nouvelle-image" />

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">{{ __('admin.guides.add_step') }}</button>
            </div>
        </form>
    </div>
@endsection
