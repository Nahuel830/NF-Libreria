<?php

namespace Tests;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use RuntimeException;
use Symfony\Component\Process\Process;

abstract class DuskTestCase extends BaseTestCase
{
    protected static ?Process $edgeDriver = null;

    /**
     * Marca el TOTP como confirmado para navegar con loginAs.
     * El flujo real de activación se prueba en LoginTest.
     */
    protected function totpConfirmado(\App\Models\User $usuario): \App\Models\User
    {
        $usuario->forceFill(['totp_confirmado_en' => now()])->save();

        return $usuario->fresh();
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startEdgeDriver();
        }
    }

    protected static function startEdgeDriver(): void
    {
        if (static::$edgeDriver) {
            return;
        }

        $proceso = new Process([static::edgeDriverPath(), '--port=9515']);
        $proceso->setTimeout(null);
        $proceso->disableOutput();
        $proceso->start();

        static::$edgeDriver = $proceso;

        for ($i = 0; $i < 30; $i++) {
            $fp = @fsockopen('127.0.0.1', 9515, $errno, $errstr, 1);

            if ($fp) {
                fclose($fp);

                return;
            }

            sleep(1);
        }

        throw new RuntimeException('msedgedriver no respondió en el puerto 9515.');
    }

    protected static function edgeDriverPath(): string
    {
        $env = $_ENV['DUSK_EDGE_DRIVER'] ?? getenv('DUSK_EDGE_DRIVER');

        if (is_string($env) && $env !== '') {
            return $env;
        }

        $salida = trim((string) shell_exec('where msedgedriver 2>NUL'));
        $ruta = strtok($salida, "\r\n");

        if (is_string($ruta) && $ruta !== '' && is_file($ruta)) {
            return $ruta;
        }

        foreach (glob((string) getenv('LOCALAPPDATA').'\Microsoft\WinGet\Packages\Microsoft.EdgeDriver_*\msedgedriver.exe') ?: [] as $candidato) {
            if (is_file($candidato)) {
                return $candidato;
            }
        }

        throw new RuntimeException('No se encontró msedgedriver. Instálalo con: winget install --id Microsoft.EdgeDriver');
    }

    protected static function edgeBinaryPath(): string
    {
        $env = $_ENV['DUSK_EDGE_BINARY'] ?? getenv('DUSK_EDGE_BINARY');

        if (is_string($env) && $env !== '') {
            return $env;
        }

        foreach ([
            'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
        ] as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        throw new RuntimeException('No se encontró msedge.exe.');
    }

    /**
     * Create the RemoteWebDriver instance (Edge headless por defecto).
     */
    protected function driver(): RemoteWebDriver
    {
        $args = ['--window-size=1920,1080', '--disable-search-engine-choice-screen', '--no-sandbox'];

        if (! $this->hasHeadlessDisabled()) {
            $args[] = '--disable-gpu';
            $args[] = '--headless=new';
        }

        $capacidades = DesiredCapabilities::microsoftEdge();
        $capacidades->setCapability('ms:edgeOptions', [
            'binary' => static::edgeBinaryPath(),
            'args' => $args,
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? getenv('DUSK_DRIVER_URL') ?: 'http://localhost:9515',
            $capacidades
        );
    }
}
