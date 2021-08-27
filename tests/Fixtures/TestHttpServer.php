<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Fixtures;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function array_merge;
use function fopen;
use function register_shutdown_function;
use function usleep;

class TestHttpServer
{
    private static array $process = [];

    public static function start(int $port = 8057): Process
    {
        if (isset(self::$process[$port])) {
            self::$process[$port]->stop();
        } else {
            register_shutdown_function(static function () use ($port): void {
                self::$process[$port]->stop();
            });
        }

        $finder = new PhpExecutableFinder();
        $process = new Process(array_merge([$finder->find(false)], $finder->findArguments(), ['-dopcache.enable=0', '-dvariables_order=EGPCS', '-S', '127.0.0.1:' . $port]));
        $process->setWorkingDirectory(__DIR__ . '/web');
        $process->start();
        self::$process[$port] = $process;

        do {
            usleep(50000);
        } while (! @fopen('http://127.0.0.1:' . $port, 'r'));

        return $process;
    }
}
