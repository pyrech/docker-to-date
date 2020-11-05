<?php

namespace App\Utils;

use Symfony\Component\Process\Process;

class Docker
{
    private string $sandboxPath;

    public function __construct(string $sandboxPath)
    {
        $this->sandboxPath = $sandboxPath;
    }

    public function build(): void
    {
        $this->exec([
            'docker',
            'build',
            '--tag=docker-to-date/git',
            '--build-arg',
            'USER_ID=' . $this->getUserId(),
            realpath(dirname(dirname(dirname(__DIR__))) . '/infrastructure/git/'),
        ]);
    }

    public function run(array $args): void
    {
        $this->exec(
            array_merge([
                'docker',
                'run',
                '--mount',
                sprintf('type=bind,src=%s,dst=/home/app/sandbox', $this->sandboxPath),
                '-i',
                '--rm',
                '-u',
                'app',
                'docker-to-date/git',
            ], $args)
        );
    }

    private function exec(array $args): Process
    {
        $process = new Process($args);
        $process->mustRun();

        return $process;
    }

    private function getUserId(): int
    {
        $userId = (int) $this->exec(['id', '-u'])->getOutput();

        if ($userId === 0 || $userId > 256000) {
            $userId = 1000;
        }

        return $userId;
    }
}
