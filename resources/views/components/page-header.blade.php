@props(['titulo', 'subtitulo' => null, 'migas' => []])

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        @if (! empty($migas))
            <nav aria-label="Ruta de navegación">
                <ol class="breadcrumb mb-1">
                    @foreach ($migas as $texto => $url)
                        @if ($loop->last || empty($url))
                            <li class="breadcrumb-item active" aria-current="page">{{ $texto }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $texto }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="mb-0">{{ $titulo }}</h1>
        @if ($subtitulo)
            <p class="text-secondary mb-0">{{ $subtitulo }}</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        {{ $slot }}
    </div>
</div>
