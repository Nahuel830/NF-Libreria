@props(['titulo' => null])

<div {{ $attributes->merge(['class' => 'card mb-3']) }}>
    @if ($titulo)
        <div class="card-header">{{ $titulo }}</div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
    @isset($pie)
        <div class="card-footer">{{ $pie }}</div>
    @endisset
</div>
