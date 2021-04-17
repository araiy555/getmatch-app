<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class InitAssets extends Command {
    private const ASSETS = [
        'apple-touch-icon-precomposed.png',
        'favicon.ico',
        'robots.txt',
    ];

    protected static $defaultName = 'postmill:init-assets';

    protected function configure(): void {
        $this
            ->setDescription('Install default site assets')
            ->setHelp(<<<EOHELP
Copies default, overrideable Postmill assets into your public/ directory.

You can replace the copied files with your own, and Postmill will leave them
alone.
EOHELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $rootDir = \dirname(__DIR__, 2);
        $filesCopied = 0;

        if (file_exists("$rootDir/assets/public")) {
            foreach (self::ASSETS as $file) {
                $dest = "$rootDir/public/$file";

                if (!file_exists($dest)) {
                    if (!copy("$rootDir/assets/public/$file", $dest)) {
                        $io->error("Couldn't copy $file");

                        return 1;
                    }

                    $filesCopied++;
                }
            }

            if ($filesCopied > 0) {
                $io->success(sprintf('%d file(s) copied', $filesCopied));
            } else {
                $io->note('All files exist, none copied.');
            }
        } else {
            $io->warning('Source directory missing. Assuming assets are managed another way.');
        }

        return 0;
    }
}
