@props(['icono' => 'bi-inbox', 'mensaje', 'accionUrl' => null, 'accionTexto' => null])

<div class="text-center py-5">
    <i class="bi {{ $icono }} fs-1 text-secondary"></i>
    <p class="mt-2 text-secondary">{{ $mensaje }}</p>
    @if ($accionUrl && $accionTexto)
        <a href="{{ $accionUrl }}" class="btn btn-primary">{{ $accionTexto }}</a>
    @endif
</div>
