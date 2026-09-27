<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * WEB-1 1.4: rechaza contraseñas triviales (complementa Password::defaults()).
 */
class PasswordNoTrivial implements ValidationRule
{
    public function __construct(
        protected ?string $usuario = null,
        protected ?string $negocio = null
    ) {}

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

            return;
        }

        foreach ($this->palabrasNegocio() as $palabra) {
            if (str_contains($clave, $palabra)) {
                $fail('La contraseña no puede contener el nombre del negocio.');

                return;
            }
        }
    }

    /**
     * @return string[]
     */
    protected function palabrasNegocio(): array
    {
        $negocio = mb_strtolower(trim((string) $this->negocio));

        if ($negocio === '') {
            return [];
        }

        $palabras = [$negocio];

        foreach (preg_split('/\s+/', $negocio) as $parte) {
            if (mb_strlen($parte) >= 5) {
                $palabras[] = $parte;
            }
        }

        return array_unique($palabras);
    }
}
