<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ServerStartCommand extends Command {
    protected static $defaultName = 'server:start';

    protected function configure(): void {
        $this
            ->setAliases(['server:run', 'server:stop'])
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        $io->text('server:* commands have been removed.');
        $io->text('Run <info>php -S 127.0.0.1:8000 -t public</info> instead, or set up a proper web server.');

        return 1;
    }
}
