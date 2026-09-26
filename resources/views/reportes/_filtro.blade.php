<form method="GET" action="{{ $accion }}" class="row g-2 mb-3">
    <div class="col-md-3">
        <input type="date" class="form-control" name="desde" value="{{ $desde }}" aria-label="Desde">
    </div>
    <div class="col-md-3">
        <input type="date" class="form-control" name="hasta" value="{{ $hasta }}" aria-label="Hasta">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
    </div>
    <div class="col-md-3">
        <a href="{{ $accion }}?desde={{ $desde }}&hasta={{ $hasta }}&formato=csv" class="btn btn-outline-success w-100">Exportar CSV</a>
    </div>
</form>
