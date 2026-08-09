@extends('layouts.site')

@section('title', __('site.partnership.title'))
@section('meta_description', 'Permanences numériques, ateliers collectifs et sensibilisation aux arnaques par intelligence artificielle, pour les communes, associations et entreprises autour de Condé-en-Normandie.')

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.partnership.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ __('site.partnership.title') }}</h1>
            <p>{{ __('site.partnership.intro') }}</p>
        </div>
    </div>

    {{-- ============ Collectivités et associations ============ --}}
    <section class="section" aria-labelledby="offre-publique">
        <div class="container">
            <div class="section__header">
                <h2 id="offre-publique">{{ __('site.partnership.public_title') }}</h2>
                <p>{{ __('site.partnership.public_intro') }}</p>
            </div>

            <ul class="grid" role="list">
                @foreach (['permanence', 'workshops', 'scam_prevention', 'staff'] as $key)
                    <li class="card">
                        <div class="card__body">
                            <h3 class="card__title">{{ __("site.partnership.public_offers.{$key}.title") }}</h3>

                            <p class="text-small text-muted">
                                <x-icon name="horloge" width="16" height="16" style="display:inline;vertical-align:-2px" />
                                {{ __("site.partnership.public_offers.{$key}.duration") }}
                            </p>

                            <p>{{ __("site.partnership.public_offers.{$key}.text") }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ============ Entreprises ============ --}}
    <section class="section section--surface" aria-labelledby="offre-entreprise">
        <div class="container">
            <div class="section__header">
                <h2 id="offre-entreprise">{{ __('site.partnership.business_title') }}</h2>
                <p>{{ __('site.partnership.business_intro') }}</p>
            </div>

            <ul class="grid" role="list">
                @foreach (['ai_awareness', 'cybersecurity', 'ai_discovery', 'diagnosis'] as $key)
                    <li class="card">
                        <div class="card__body">
                            <x-icon
                                :name="in_array($key, ['ai_awareness', 'ai_discovery'], true) ? 'intelligence' : 'securite'"
                                class="card__icon"
                            />

                            <h3 class="card__title">{{ __("site.partnership.business_offers.{$key}.title") }}</h3>

                            <p class="text-small text-muted">
                                <x-icon name="horloge" width="16" height="16" style="display:inline;vertical-align:-2px" />
                                {{ __("site.partnership.business_offers.{$key}.duration") }}
                            </p>

                            <p>{{ __("site.partnership.business_offers.{$key}.text") }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ============ Modalités ============ --}}
    <section class="section" aria-labelledby="modalites">
        <div class="container container--narrow">
            <h2 id="modalites">{{ __('site.partnership.format_title') }}</h2>

            <ul class="check-list check-list--yes" role="list" style="margin-top: 1.5rem">
                @foreach (['onsite', 'group', 'materials', 'quote'] as $key)
                    <li>{{ __("site.partnership.format.{$key}") }}</li>
                @endforeach
            </ul>

            <p style="margin-top: 2rem">
                <a class="btn btn--outline btn--lg" href="{{ route('partnership.brochure') }}">
                    <x-icon name="document" class="btn__icon" />
                    {{ __('site.partnership.brochure') }}
                </a>
            </p>

            <p class="text-small text-muted">{{ __('site.partnership.brochure_help') }}</p>
        </div>
    </section>

    {{-- ============ Formulaire ============ --}}
    <section class="section section--surface" aria-labelledby="formulaire-partenariat">
        <div class="container container--narrow">
            <x-form-errors />

            <h2 id="formulaire-partenariat">{{ __('site.partnership.form_title') }}</h2>

            <p class="form__required-note">{{ __('site.common.required_fields') }}</p>

            <form method="POST" action="{{ route('partnership.store') }}" class="form" data-guard-submit>
                @csrf
                <x-honeypot />

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Votre structure</legend>

                    <x-field name="organisation_name" label="Nom de la structure" required />

                    <x-field
                        name="organisation_type"
                        type="select"
                        label="Type de structure"
                        :options="\App\Enums\PartnerType::options()"
                        required
                    />

                    <x-field
                        name="municipality_id"
                        type="select"
                        label="Commune"
                        :options="$municipalities->pluck('name', 'id')->all()"
                        empty-option="— Choisissez la commune —"
                    />

                    <x-field
                        name="municipality_name"
                        label="Commune (si absente de la liste)"
                        help="J’interviens aussi au-delà des communes listées, selon la distance."
                    />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Votre contact</legend>

                    <x-field name="contact_name" label="Nom du contact" required autocomplete="name" />
                    <x-field name="contact_role" label="Fonction" />
                    <x-field name="email" type="email" label="Adresse électronique" required autocomplete="email" />
                    <x-field name="phone" type="tel" label="Téléphone" autocomplete="tel" placeholder="02 31 00 00 00" />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Votre projet</legend>

                    <div class="field">
                        <fieldset>
                            <legend class="field__label">
                                Quel type de besoin ?
                                <span class="required-mark" aria-hidden="true">*</span>
                            </legend>

                            <span class="field__hint">Vous pouvez cocher plusieurs propositions.</span>

                            <div class="choice-list">
                                @foreach ($needs as $value => $label)
                                    <x-checkbox
                                        name="needs[]"
                                        :value="$value"
                                        :label="$label"
                                        :checked="in_array($value, (array) old('needs', []), true)"
                                    />
                                @endforeach
                            </div>

                            @error('needs')
                                <p class="field__error">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    </div>

                    <x-field
                        name="audience"
                        label="Public concerné"
                        help="Exemple : « résidents de l’EHPAD », « adhérents du club », « agents d’accueil », « équipe comptable »."
                    />

                    <x-field
                        name="estimated_participants"
                        type="number"
                        label="Nombre de participants estimé"
                        min="1"
                        max="1000"
                    />

                    <x-field name="desired_period" label="Période souhaitée" help="Exemple : « à partir de septembre », « une fois par mois »." />

                    <x-field name="message" type="textarea" label="Précisions" :rows="5" maxlength="3000" />

                    <x-checkbox name="quote_requested" label="Je souhaite recevoir un devis" />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Vos données</legend>

                    <x-checkbox
                        name="consent"
                        :label="__('consent.labels.partnership_request')"
                        :description="__('consent.statements.partnership_request')"
                        required
                    />
                </fieldset>

                <div class="form__actions">
                    <button type="submit" class="btn btn--accent btn--lg" data-submitting-label="{{ __('site.common.sending') }}">
                        {{ __('site.partnership.submit') }}
                    </button>
                </div>
            </form>

            <x-phone-cta />
        </div>
    </section>

    @if ($partners->isNotEmpty())
        <section class="section section--sunken" aria-labelledby="partenaires-actuels">
            <div class="container">
                <h2 id="partenaires-actuels">{{ __('site.partnership.current_partners') }}</h2>

                <ul class="partner-list" role="list" style="margin-top: 1.5rem">
                    @foreach ($partners as $partner)
                        <li class="partner-list__item">
                            @if ($partner->logo_path)
                                <img
                                    class="partner-list__logo"
                                    src="{{ \Illuminate\Support\Facades\Storage::url($partner->logo_path) }}"
                                    alt="{{ $partner->logo_alt ?: $partner->name }}"
                                    loading="lazy"
                                >
                            @else
                                {{ $partner->name }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
@endsection
