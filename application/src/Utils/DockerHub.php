<?php

namespace App\Utils;

use Composer\Semver\Semver;

class DockerHub
{
    const VERSION_REGEX = '/(\d*\.*)+\d/';

    public function getLatestTagForImage(string $image): ?string
    {
        $tags = $this->findTags($image);

        if (!$tags) {
            return null;
        }

        $tags = array_filter($tags, function (string $tag) {
           return preg_match(self::VERSION_REGEX, $tag);
        });

        $tags = Semver::rsort($tags);

        return $tags[0];
    }

    private function findTags(string $image): array
    {
        $json = file_get_contents(sprintf('https://registry.hub.docker.com/v1/repositories/%s/tags', $image));
        $tags = json_decode($json, true);

        if (!$tags) {
            return [];
        }

        return array_column($tags, 'name');
    }
}
