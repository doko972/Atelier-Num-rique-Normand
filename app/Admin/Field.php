<?php

declare(strict_types=1);

namespace App\Admin;

/**
 * Description d'un champ de formulaire du back-office.
 *
 * Décrire les champs une seule fois évite d'écrire quatorze formulaires
 * quasi identiques, et surtout garantit que le libellé visible, l'aide, la
 * règle de validation et la colonne du tableau restent cohérents.
 */
final class Field
{
    /**
     * @param  array<string, string>  $options  pour les listes déroulantes
     * @param  array<int, mixed>  $rules
     */
    private function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $label,
        public readonly array $rules = [],
        public readonly ?string $help = null,
        public readonly array $options = [],
        public readonly bool $inList = false,
        public readonly bool $searchable = false,
        public readonly mixed $default = null,
        public readonly ?int $rows = null,
        public readonly ?string $listFormat = null,
    ) {}

    /**
     * @param  array<int, mixed>  $rules
     */
    public static function text(string $name, string $label, array $rules = ['required', 'string', 'max:255'], ?string $help = null): self
    {
        return new self($name, 'text', $label, $rules, $help);
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    public static function textarea(string $name, string $label, array $rules = ['nullable', 'string'], ?string $help = null, int $rows = 5): self
    {
        return new self($name, 'textarea', $label, $rules, $help, rows: $rows);
    }

    /**
     * Zone de texte longue destinée au contenu rédactionnel.
     *
     * @param  array<int, mixed>  $rules
     */
    public static function richText(string $name, string $label, array $rules = ['required', 'string'], ?string $help = null): self
    {
        return new self($name, 'richtext', $label, $rules, $help, rows: 16);
    }

    /**
     * @param  array<string, string>  $options
     * @param  array<int, mixed>  $rules
     */
    public static function select(string $name, string $label, array $options, array $rules = ['required'], ?string $help = null): self
    {
        return new self($name, 'select', $label, $rules, $help, options: $options);
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    public static function boolean(string $name, string $label, ?string $help = null, bool $default = false): self
    {
        return new self($name, 'boolean', $label, ['nullable', 'boolean'], $help, default: $default);
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    public static function number(string $name, string $label, array $rules = ['nullable', 'integer', 'min:0'], ?string $help = null): self
    {
        return new self($name, 'number', $label, $rules, $help);
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    public static function date(string $name, string $label, array $rules = ['nullable', 'date'], ?string $help = null): self
    {
        return new self($name, 'date', $label, $rules, $help);
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    public static function time(string $name, string $label, array $rules = ['required', 'date_format:H:i'], ?string $help = null): self
    {
        return new self($name, 'time', $label, $rules, $help);
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    public static function url(string $name, string $label, array $rules = ['nullable', 'url', 'max:255'], ?string $help = null): self
    {
        return new self($name, 'url', $label, $rules, $help);
    }

    /**
     * Liste de valeurs libres, une par ligne, stockée en JSON.
     *
     * @param  array<int, mixed>  $rules
     */
    public static function lines(string $name, string $label, ?string $help = null): self
    {
        return new self($name, 'lines', $label, ['nullable', 'string'], $help, rows: 6);
    }

    /**
     * Le champ apparaît comme colonne du tableau de la liste.
     */
    public function listed(?string $format = null): self
    {
        return $this->copyWith(inList: true, listFormat: $format);
    }

    /**
     * Le champ est pris en compte par la recherche.
     */
    public function searchable(): self
    {
        return $this->copyWith(searchable: true);
    }

    private function copyWith(
        ?bool $inList = null,
        ?bool $searchable = null,
        ?string $listFormat = null,
    ): self {
        return new self(
            name: $this->name,
            type: $this->type,
            label: $this->label,
            rules: $this->rules,
            help: $this->help,
            options: $this->options,
            inList: $inList ?? $this->inList,
            searchable: $searchable ?? $this->searchable,
            default: $this->default,
            rows: $this->rows,
            listFormat: $listFormat ?? $this->listFormat,
        );
    }

    public function isRequired(): bool
    {
        return in_array('required', $this->rules, strict: true);
    }
}
