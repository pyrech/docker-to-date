<?php

namespace App\Repository;

use App\Updater\UpdaterInterface;
use App\Utils\Git;
use Github\Client;
use Github\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GithubParser
{
    private Client $client;
    private LoggerInterface $logger;
    private Git $git;
    /** @var iterable<UpdaterInterface> */
    private iterable $updaters;
    private array $repositories;
    private string $githubUsername;
    private string $githubAccessToken;

    public function __construct(
        Client $client,
        LoggerInterface $logger,
        Git $git,
        iterable $updaters,
        array $repositories,
        string $githubUsername,
        string $githubAccessToken
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->git = $git;
        $this->updaters = $updaters;
        $this->repositories = $repositories['github'];
        $this->githubUsername = $githubUsername;
        $this->githubAccessToken = $githubAccessToken;

    }

    public function check(OutputInterface $output)
    {
        foreach ($this->repositories as $repositoryName => $config) {
            $output->writeln(sprintf('Processing repository <info>%s</info> on GitHub', $repositoryName));

            [$user, $name] = explode('/', $repositoryName, 2);

            try {
                $repository = $this->client->api('repo')->show($user, $name);
            } catch (RuntimeException $e) {
                $this->logger->error($e->getMessage(), [
                    'exception' => $e,
                ]);
                continue;
            }

            $destination = 'github/' . $repository['full_name'];

            $this->git->forkRepository(
                str_replace(
                    'https://',
                    sprintf('https://%s:%s@', $this->githubUsername, $this->githubAccessToken),
                    $repository['clone_url']
                ),
                $destination
            );

            foreach ($config['paths'] as $path) {
                $output->writeln('');
                $output->writeln(sprintf('- Checking file <info>%s</info>', $path));
                $initialContent = $content = $this->git->getFileContent($destination, $path);

                $fixed = false;
                foreach ($this->updaters as $updater) {
                    if (!$updater->supports(basename($path), $content)) {
                        continue;
                    }

                    $fixed = true;
                    $content = $updater->update($content);
                }

                if ($fixed) {
                    $this->git->setFileContent($destination, $path, $content);

                    if ($initialContent !== $content) {
                        $output->writeln('<info>    Update(s) found</info>');
                    } else {
                        $output->writeln(sprintf('<fg=yellow>   Nothing to update in file "%s"</>', $path));
                    }
                } else {
                    $output->writeln(sprintf('<fg=red>   Don\'t know how to check file "%s"</>', $path));
                }

            }
        }
    }
}
