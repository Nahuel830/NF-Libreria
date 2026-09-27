<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordNoTrivial implements ValidationRule
{
    public function __construct(protected ?string $usuario = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $clave = mb_strtolower(trim((string) $value));

        $prohibidas = ['password', '123456'];
        $usuario = mb_strtolower(trim((string) $this->usuario));

        if ($usuario !== '') {
            $prohibidas[] = $usuario;
        }

        if (in_array($clave, $prohibidas, true)) {
            $fail('La contraseña no puede ser "password", "123456" ni igual a tu nombre de usuario.');
        }
    }
}
