{{--
    Récapitulatif des erreurs en tête de formulaire.

    Chaque ligne renvoie vers le champ concerné : sur un formulaire long, la
    personne n'a pas à chercher où se trouve le problème.
--}}
@if ($errors->any())
    <div class="form-errors" role="alert" tabindex="-1" id="erreurs-formulaire">
        <h2>{{ __('site.common.errors_title') }}</h2>

        <p>{{ __('site.common.errors_intro') }}</p>

        <ul>
            @foreach ($errors->keys() as $key)
                <li>
                    <a href="#champ-{{ str_replace(['[', ']', '.', '_'], '-', $key) }}">
                        {{ $errors->first($key) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
