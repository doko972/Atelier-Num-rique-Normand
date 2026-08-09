<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Comportements communs à tous les enums de statut de l'application.
 *
 * Les libellés sont systématiquement traduits via les fichiers de langue :
 * aucun texte métier n'est écrit en dur dans les contrôleurs ou les vues.
 */
trait HasLabel
{
    /**
     * Clé de traduction du libellé, dérivée du nom court de l'enum.
     */
    public function label(): string
    {
        $group = str(class_basename(static::class))->snake()->toString();

        return __("enums.{$group}.{$this->value}");
    }

    /**
     * Variante visuelle du badge associé au statut.
     */
    public function badgeVariant(): string
    {
        return 'neutral';
    }

    /**
     * Liste `valeur => libellé` exploitable directement par un `<select>`.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (static::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Toutes les valeurs brutes, pour les règles de validation `Rule::in()`.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }
}
