<?php

namespace App\Utils;

class Git
{
    private Docker $docker;
    private string $sandboxPath;

    public function __construct(Docker $docker, string $sandboxPath)
    {
        $this->docker = $docker;
        $this->sandboxPath = $sandboxPath;
    }

    public function forkRepository(string $url, string $destination): void
    {
        $targetDestination = '/home/app/sandbox/' . $destination;

        $this->docker->build();

        $this->docker->run([
            'rm',
            '-rf',
            $targetDestination,
        ]);

        $this->docker->run([
            'git',
            'clone',
            '--depth',
            '1',
            $url,
            $targetDestination,
        ]);
    }

    public function getFileContent(string $repository, string $file): string
    {
        return file_get_contents($this->sandboxPath . '/' . $repository . '/' . $file);
    }

    public function setFileContent(string $repository, string $file, string $content): string
    {
        return file_put_contents($this->sandboxPath . '/' . $repository . '/' . $file, $content);
    }
}
