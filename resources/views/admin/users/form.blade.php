@extends('layouts.admin')

@section('title', ($isNew ? __('admin.common.create') : __('admin.common.edit')).' — '.__('admin.users.title'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ $isNew ? 'Nouveau compte' : 'Modifier le compte' }}</h1>
        <a class="btn btn--ghost" href="{{ route('admin.users.index') }}">{{ __('admin.common.back') }}</a>
    </div>

    <div class="admin-panel">
        <x-form-errors />

        <p class="form__required-note">{{ __('admin.common.required_fields') }}</p>

        <form
            method="POST"
            action="{{ $isNew ? route('admin.users.store') : route('admin.users.update', $user) }}"
            class="form"
        >
            @csrf
            @unless ($isNew)
                @method('PUT')
            @endunless

            <x-field name="name" label="Nom et prénom" :value="$user->name" required autocomplete="name" />
            <x-field name="email" type="email" label="Adresse électronique" :value="$user->email" required autocomplete="email" />
            <x-field name="phone" type="tel" label="Téléphone" :value="$user->phone" />

            @if (! $isNew && auth()->user()->is($user))
                <div class="field">
                    <p class="field__label">Rôle</p>
                    <p>{{ $user->roleEnum()?->label() }}</p>
                    <p class="field__hint">{{ __('admin.users.self_role_locked') }}</p>
                    <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                </div>
            @else
                <x-field
                    name="role_id"
                    type="select"
                    label="Rôle"
                    :options="$roles->pluck('name', 'id')->all()"
                    :value="$user->role_id"
                    required
                    help="Le rôle détermine les parties de l’administration accessibles."
                />
            @endif

            <x-field
                name="password"
                type="password"
                label="Mot de passe"
                :required="$isNew"
                autocomplete="new-password"
                :help="$isNew ? 'Au moins douze caractères.' : __('admin.users.password_help')"
            />

            <x-field
                name="password_confirmation"
                type="password"
                label="Confirmer le mot de passe"
                :required="$isNew"
                autocomplete="new-password"
            />

            @if ($isNew)
                <x-checkbox name="is_active" label="Activer le compte immédiatement" :checked="true" />
            @endif

            <div class="form__actions">
                <button type="submit" class="btn btn--primary btn--lg">{{ __('admin.common.save') }}</button>
                <a class="btn btn--ghost" href="{{ route('admin.users.index') }}">{{ __('admin.common.cancel') }}</a>
            </div>
        </form>
    </div>

    @unless ($isNew)
        <div class="admin-panel">
            <h2 class="admin-panel__title">Permissions du rôle</h2>

            <ul class="tag-list" role="list">
                @foreach ($user->permissionSlugs() as $slug)
                    <li><x-badge variant="neutral">{{ __("enums.permission.{$slug}") }}</x-badge></li>
                @endforeach
            </ul>
        </div>
    @endunless
@endsection
