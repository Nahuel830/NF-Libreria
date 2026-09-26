@props(['estado'])

@php
    $texto = mb_strtoupper((string) $estado);
    $color = match ($texto) {
        'ACTIVO', 'COMPLETADA', 'REGISTRADA' => 'success',
        'INACTIVO' => 'secondary',
        'ANULADA', 'SIN STOCK' => 'danger',
        'STOCK BAJO' => 'warning',
        default => 'secondary',
    };
    $claseTexto = $color === 'warning' ? ' text-dark' : '';
@endphp

<span class="badge bg-{{ $color }}{{ $claseTexto }}">{{ $texto }}</span>
