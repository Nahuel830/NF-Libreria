<div class="toasts-nf" aria-live="polite">
    @foreach (['success' => ['success', true], 'info' => ['info', true], 'warning' => ['warning', false], 'error' => ['danger', false]] as $clave => [$tipo, $auto])
        @if (session($clave))
            <div class="toast align-items-center text-bg-{{ $tipo }} border-0 show" role="alert"
                @if ($auto) data-bs-autohide="true" data-bs-delay="4000" @else data-bs-autohide="false" @endif>
                <div class="d-flex">
                    <div class="toast-body">{{ session($clave) }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
            </div>
        @endif
    @endforeach

    @if (isset($errors) && $errors->any())
        <div class="toast align-items-center text-bg-danger border-0 show" role="alert" data-bs-autohide="false">
            <div class="d-flex">
                <div class="toast-body">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    @endif
</div>
