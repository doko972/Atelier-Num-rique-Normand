@extends('layouts.admin')

@section('title', __('admin.users.title'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.users.title') }}</h1>

        @can('create', \App\Models\User::class)
            <a class="btn btn--primary" href="{{ route('admin.users.create') }}">{{ __('admin.common.create') }}</a>
        @endcan
    </div>

    <div class="admin-panel">
        <div class="table-wrap">
            <table class="table table--stacked">
                <caption class="visually-hidden">{{ __('admin.users.title') }}</caption>

                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Adresse électronique</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">État</th>
                        <th scope="col">{{ __('admin.users.last_login') }}</th>
                        <th scope="col">{{ __('admin.common.actions') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td data-label="Nom">{{ $user->name }}</td>
                            <td data-label="Adresse électronique">{{ $user->email }}</td>

                            <td data-label="Rôle">
                                <x-badge :variant="$user->roleEnum()?->badgeVariant() ?? 'neutral'">
                                    {{ $user->roleEnum()?->label() ?? '—' }}
                                </x-badge>
                            </td>

                            <td data-label="État">
                                @if ($user->trashed())
                                    <x-badge variant="danger" square>Supprimé</x-badge>
                                @elseif (! $user->is_active)
                                    <x-badge variant="warning">{{ __('admin.users.inactive') }}</x-badge>
                                @else
                                    <x-badge variant="success">Actif</x-badge>
                                @endif

                                @if (! $user->hasVerifiedEmail())
                                    <x-badge variant="warning" square>{{ __('admin.users.unverified') }}</x-badge>
                                @endif
                            </td>

                            <td data-label="{{ __('admin.users.last_login') }}">
                                {{ $user->last_login_at?->format('d/m/Y à H\\hi') ?? __('admin.common.never') }}
                            </td>

                            <td data-label="{{ __('admin.common.actions') }}">
                                <div class="table__actions">
                                    @can('update', $user)
                                        <a class="btn btn--outline btn--sm" href="{{ route('admin.users.edit', $user) }}">
                                            {{ __('admin.common.edit') }}
                                            <span class="visually-hidden">: {{ $user->name }}</span>
                                        </a>
                                    @endcan

                                    @can('toggleActivation', $user)
                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                            @csrf
                                            <button type="submit" class="btn btn--ghost btn--sm">
                                                {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
                                                <span class="visually-hidden">le compte de {{ $user->name }}</span>
                                            </button>
                                        </form>
                                    @endcan

                                    @can('delete', $user)
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            data-confirm="{{ __('admin.common.delete_confirm') }}"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn--danger btn--sm">
                                                {{ __('admin.common.delete') }}
                                                <span class="visually-hidden">: {{ $user->name }}</span>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
@endsection
