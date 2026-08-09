@extends('layouts.site')

@section('title', __('site.faq.title'))
@section('meta_description', __('site.faq.intro'))

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.faq.title') => null]" />
@endsection

@push('head')
    <x-structured-data :data="\App\Support\StructuredData::faqPage($groups->flatten())" />
@endpush

@section('content')
    <div class="page-header">
        <div class="container container--narrow">
            <h1>{{ __('site.faq.title') }}</h1>
            <p>{{ __('site.faq.intro') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container container--narrow">
            @if ($groups->isEmpty())
                <p>{{ __('site.faq.empty') }}</p>
            @else
                @foreach ($groups as $category => $faqs)
                    <section aria-labelledby="faq-{{ $category }}" style="margin-bottom: 2.5rem">
                        <h2 id="faq-{{ $category }}">
                            {{ __("site.faq.groups.{$category}") }}
                        </h2>

                        {{-- <details>/<summary>, accessibles nativement et sans JavaScript --}}
                        <div class="faq" style="margin-top: 1rem">
                            @foreach ($faqs as $faq)
                                <details class="faq__item">
                                    <summary class="faq__question">{{ $faq->question }}</summary>
                                    <div class="faq__answer">{!! nl2br(e($faq->answer)) !!}</div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif

            <x-phone-cta />
        </div>
    </section>
@endsection
