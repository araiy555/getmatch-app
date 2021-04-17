<?php

namespace App\Command;

use App\Repository\SiteRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ActivateMaintenanceMode extends Command {
    protected static $defaultName = 'postmill:maintenance';

    /**
     * @var SiteRepository
     */
    private $sites;

    /**
     * @var string
     */
    private $includeFilePath;

    public function __construct(SiteRepository $sites) {
        parent::__construct();

        $this->sites = $sites;
        $this->includeFilePath = __DIR__.'/../../var/maintenance.php';
    }

    public function configure(): void {
        $this
            ->setAliases(['app:maintenance'])
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Custom message to display.')
            ->addOption('deactivate', 'd', InputOption::VALUE_NONE, 'Deactivate maintenance mode')
            ->setDescription('Manages maintenance mode')
            ->setHelp(<<<EOHELP
Takes the application down for maintenance.

To set a custom message, use the <info>-m</info> option.

To deactivate maintenance mode, use the <info>-d</info> option.
EOHELP
);
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('deactivate')) {
            $this->writeIncludeFile($input->getOption('message'));

            $io->success('Maintenance mode has been activated.');
        } else {
            if (!@unlink($this->includeFilePath)) {
                $io->error('Could not remove '.$this->includeFilePath);

                return 1;
            }

            $io->success('Maintenance mode is now deactivated.');
        }

        if (\function_exists('opcache_invalidate')) {
            /* @noinspection PhpComposerExtensionStubsInspection */
            opcache_invalidate($this->includeFilePath, true);
        }

        return 0;
    }

    private function writeIncludeFile($message): void {
        $title = $this->sites->getCurrentSiteName();
        $img = base64_encode(file_get_contents(__DIR__.'/../../public/apple-touch-icon-precomposed.png'));
        $message = nl2br($message ?: 'The site will return shortly.');

        $php = <<<EOPHP
<?php http_response_code(503) ?>
<!DOCTYPE html>
<meta charset="utf-8">
<title>{$title}</title>
<style>
body {
  background: #f8f8f8;
  color: #444;
  font-family: sans-serif;
  margin: 2em;
  text-align: center;
}
</style>
<p><img src="data:image/png;base64,{$img}" alt=""></p>
<h1>{$title} is down for maintenance</h1>
<p>$message</p>
<?php exit ?>
EOPHP;

        $tempnam = tempnam(sys_get_temp_dir(), 'ra');

        if (!@file_put_contents($tempnam, $php)) {
            throw new \RuntimeException("Couldn't write temporary file");
        }

        @chmod($tempnam, 0666 & ~umask());

        if (!@rename($tempnam, $this->includeFilePath)) {
            throw new \RuntimeException("Couldn't write to file");
        }
    }
}
