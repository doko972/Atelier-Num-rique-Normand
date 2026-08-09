{{--
    Barre de réglages d'affichage (codex §6).

    Elle n'apparaît que si JavaScript est actif : sans lui, les boutons
    n'auraient aucun effet, et un bouton inerte est pire que pas de bouton.

    Sur mobile, les libellés sont raccourcis visuellement — mais leur partie
    complémentaire reste dans le nom accessible, car elle est masquée par la
    technique « visually-hidden » et non par `display: none`.
--}}
<noscript>
    <style>.a11y-bar { display: none; }</style>
</noscript>

<div class="a11y-bar no-print">
    <div class="a11y-bar__inner">
        <p class="a11y-bar__label" id="a11y-titre">{{ __('site.a11y.label') }}</p>

        <div class="a11y-bar__group" role="group" aria-labelledby="a11y-titre">
            <button type="button" class="a11y-bar__btn" data-a11y="contrast" aria-pressed="false">
                Contraste<span class="a11y-bar__btn-extra">&nbsp;renforcé</span>
            </button>

            <button type="button" class="a11y-bar__btn" data-a11y="motion" aria-pressed="false">
                <span class="a11y-bar__btn-extra">Réduire les&nbsp;</span>animations
            </button>
        </div>

        <div class="a11y-bar__group">
            <span class="a11y-bar__size-label" aria-hidden="true">{{ __('site.a11y.text_size') }}</span>

            <button type="button" class="a11y-bar__btn a11y-bar__btn--icon" data-a11y="text-down">
                <span aria-hidden="true">A−</span>
                <span class="visually-hidden">{{ __('site.a11y.text_down') }}</span>
            </button>

            <button type="button" class="a11y-bar__btn a11y-bar__btn--icon" data-a11y="text-up">
                <span aria-hidden="true">A+</span>
                <span class="visually-hidden">{{ __('site.a11y.text_up') }}</span>
            </button>
        </div>
    </div>
</div>
