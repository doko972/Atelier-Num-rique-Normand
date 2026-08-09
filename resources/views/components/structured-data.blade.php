{{--
    Bloc de données structurées Schema.org.

    Le tableau est construit par App\Support\StructuredData, en PHP : écrire
    la clé « @context » directement dans un fichier Blade la ferait compiler
    comme une directive, ce qui corromprait le JSON produit.
--}}
@props(['data' => null])

@if (filled($data))
    <script type="application/ld+json">{!! \App\Support\StructuredData::toJson($data) !!}</script>
@endif
