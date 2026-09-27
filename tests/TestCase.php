<?php

namespace Tests;

use App\Enums\Rol;
use App\Services\TotpService;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * actingAs equivale a una sesión completa (con TOTP ya superado).
     * El flujo real de TOTP en el login se prueba en TotpTest.
     */
    public function actingAs(UserContract $user, $guard = null): static
    {
        if (
            $user instanceof \App\Models\User
            && in_array($user->rol, [Rol::Admin, Rol::Encargado], true)
            && $user->totp_confirmado_en === null
            && app(TotpService::class)->obligatorioPara($user)
        ) {
            $user->forceFill(['totp_confirmado_en' => now()])->save();
        }

        return parent::actingAs($user, $guard);
    }
}
