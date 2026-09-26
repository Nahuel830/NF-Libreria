@props(['accion', 'metodo' => 'GET'])

<div class="mb-3">
    <button class="btn btn-outline-secondary d-md-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filtros-listado" aria-expanded="false" aria-controls="filtros-listado">
        <i class="bi bi-funnel"></i> Filtros
    </button>
    <div class="collapse d-md-block" id="filtros-listado">
        <form method="{{ $metodo }}" action="{{ $accion }}" class="row g-2">
            {{ $slot }}
        </form>
    </div>
</div>
