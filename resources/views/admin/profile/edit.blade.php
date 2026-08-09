@extends('layouts.admin')

@section('title', __('admin.profile.title'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.profile.title') }}</h1>
    </div>

    <div class="split">
        <section class="admin-panel" aria-labelledby="infos">
            <h2 class="admin-panel__title" id="infos">{{ __('admin.profile.details') }}</h2>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="form">
                @csrf
                @method('PUT')

                <x-field name="name" label="Nom et prénom" :value="$user->name" required autocomplete="name" />

                <x-field
                    name="email"
                    type="email"
                    label="Adresse électronique"
                    :value="$user->email"
                    required
                    autocomplete="email"
                    help="Changer d’adresse demandera une nouvelle vérification."
                />

                <x-field name="phone" type="tel" label="Téléphone" :value="$user->phone" />

                <button type="submit" class="btn btn--primary">{{ __('admin.common.save') }}</button>
            </form>

            <dl class="text-small text-muted" style="margin-top: 1.5rem">
                <div>
                    <dt><strong>Rôle</strong></dt>
                    <dd>{{ $user->roleEnum()?->label() }}</dd>
                </div>

                <div>
                    <dt><strong>{{ __('admin.users.last_login') }}</strong></dt>
                    <dd>{{ $user->last_login_at?->format('d/m/Y à H\\hi') ?? __('admin.common.never') }}</dd>
                </div>
            </dl>
        </section>

        <div>
            <section class="admin-panel" aria-labelledby="mot-de-passe">
                <h2 class="admin-panel__title" id="mot-de-passe">{{ __('admin.profile.password') }}</h2>

                <form method="POST" action="{{ route('admin.profile.password') }}" class="form">
                    @csrf
                    @method('PUT')

                    <x-field
                        name="current_password"
                        type="password"
                        label="Mot de passe actuel"
                        required
                        autocomplete="current-password"
                    />

                    <x-field
                        name="password"
                        type="password"
                        label="Nouveau mot de passe"
                        required
                        autocomplete="new-password"
                        help="Au moins douze caractères, différents de ceux utilisés ailleurs."
                    />

                    <x-field
                        name="password_confirmation"
                        type="password"
                        label="Confirmer le nouveau mot de passe"
                        required
                        autocomplete="new-password"
                    />

                    <button type="submit" class="btn btn--primary">{{ __('admin.common.save') }}</button>
                </form>
            </section>

            <section class="admin-panel" aria-labelledby="sessions">
                <h2 class="admin-panel__title" id="sessions">{{ __('admin.profile.sessions') }}</h2>

                <p class="text-small text-muted">{{ __('admin.profile.sessions_help') }}</p>

                <form method="POST" action="{{ route('admin.profile.sessions') }}" class="form">
                    @csrf

                    <x-field
                        name="password"
                        type="password"
                        label="Votre mot de passe"
                        required
                        autocomplete="current-password"
                        id="champ-password-sessions"
                    />

                    <button type="submit" class="btn btn--outline">{{ __('admin.profile.revoke') }}</button>
                </form>
            </section>
        </div>
    </div>
@endsection
