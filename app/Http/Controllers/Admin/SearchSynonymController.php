<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Models\SearchSynonym;
use Illuminate\Contracts\View\View;

/**
 * Dictionnaire de synonymes de la recherche publique.
 */
class SearchSynonymController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return SearchSynonym::class;
    }

    protected function routeKey(): string
    {
        return 'search-synonyms';
    }

    protected function defaultSort(): string
    {
        return 'label';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Synonymes de recherche',
            'singular' => 'le groupe de synonymes',
            'plural' => 'groupes de synonymes',
            'intro' => 'Chaque groupe réunit des mots tenus pour équivalents : chercher l’un trouve aussi les pages qui emploient les autres. Inutile de gérer les accents, les majuscules ou le pluriel : la recherche s’en charge.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('label', 'Mot principal', ['required', 'string', 'max:100'],
                'Le mot employé dans vos contenus. Exemple : « courriel ».')
                ->listed()
                ->searchable(),
            Field::lines('words', 'Mots équivalents',
                'Un mot ou une expression par ligne, tels que les visiteurs les écrivent. Exemple : mail, e-mail, boîte mail.')
                ->listed(),
        ];
    }

    /**
     * Le relevé des recherches sans réponse propose d'ajouter le mot
     * manquant : il arrive ici prérempli.
     */
    public function create(): View
    {
        $view = parent::create();
        $word = request()->query('mot');

        if (is_string($word) && $word !== '') {
            $view->with('record', new SearchSynonym([
                'words' => [mb_substr($word, 0, 100)],
            ]));
        }

        return $view;
    }
}
