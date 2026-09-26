@if (empty($datos))
    <p class="text-muted">Sin datos.</p>
@else
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Campo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datos as $campo => $valor)
                    <tr>
                        <td>{{ $campo }}</td>
                        <td>
                            @if (is_array($valor))
                                <ul class="mb-0">
                                    @foreach ($valor as $subCampo => $subValor)
                                        <li>{{ $subCampo }}: {{ is_array($subValor) ? json_encode($subValor) : var_export($subValor, true) }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ var_export($valor, true) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
