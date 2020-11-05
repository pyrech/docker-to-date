<?php

namespace App\Updater;

use App\Utils\DockerHub;

class DockerfileUpdater implements UpdaterInterface
{
    const REGEX_PATTERNS = [
        '%(?<before>[^\r\n\t\f\v ]*FROM\s+)(?<image>\S*)(?<after>\s)%i',
        '%(?<before>[^\r\n\t\f\v ]*COPY\s+.*--from=)(?<image>\S*)(?<after>\s)%i',
    ];

    private DockerHub $dockerHub;

    public function __construct(DockerHub $dockerHub)
    {
        $this->dockerHub = $dockerHub;
    }

    public function supports(string $filename, string $content): bool
    {
        return (bool) preg_match('/dockerfile/i', $filename);
    }

    public function update(string $content): string
    {
        foreach (self::REGEX_PATTERNS as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                if ($newImage = $this->shouldUpdate($matches['image'])) {
                    $content = preg_replace_callback($pattern, function (array $matches) use ($newImage) {
                        return sprintf('%s%s%s', $matches['before'], $newImage, $matches['after']);
                    }, $content);
                }
            }
        }

        return $content;
    }

    private function shouldUpdate($image): ?string
    {
        if (!str_contains($image, ':')) {
            return null;
        }

        [$image, $version] = explode(':', $image, 2);

        if (!preg_match(DockerHub::VERSION_REGEX, $version)) {
            return null;
        }

        $latestTag = $this->dockerHub->getLatestTagForImage($image);

        if (!$latestTag) {
            return null;
        }

        return $image . ':' . $latestTag;
    }
}
