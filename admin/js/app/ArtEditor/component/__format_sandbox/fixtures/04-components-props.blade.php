@props(['title' => '', 'items' => []])

<x-card>
@component('components.alert', ['type' => 'error'])
@slot('title')
{{ $title }}
@endslot
<p>Body text</p>
@endcomponent
</x-card>

@once
@push('scripts')
<script>console.log('x')</script>
@endpush
@endonce
