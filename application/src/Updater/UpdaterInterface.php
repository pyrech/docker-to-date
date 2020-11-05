<?php

namespace App\Updater;

interface UpdaterInterface
{
    public function supports(string $filename, string $content): bool;

    public function update(string $content): string;
}
