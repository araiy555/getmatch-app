<?php

namespace App\Command;

use App\Repository\BundledThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SyncThemesCommand extends Command {
    protected static $defaultName = 'postmill:sync-themes';

    /**
     * @var BundledThemeRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(
        BundledThemeRepository $repository,
        EntityManagerInterface $manager
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->manager = $manager;
    }

    protected function configure(): void {
        $this
            ->setAliases(['app:theme:sync'])
            ->setDescription('Sync theme configuration with database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->repository->findThemesToCreate() as $theme) {
            $changes = true;
            $io->text(sprintf("Creating theme '%s'...", $theme->getName()));

            $this->manager->persist($theme);
        }

        foreach ($this->repository->findThemesToRemove() as $theme) {
            $changes = true;
            $io->text(sprintf("Removing theme '%s'...", $theme->getName()));

            $this->manager->remove($theme);
        }

        if (!($changes ?? false)) {
            $io->note('Nothing to be done.');

            return 0;
        }

        if (!$io->confirm('Is this OK?')) {
            $io->text('Aborting.');

            return 1;
        }

        $this->manager->flush();

        $io->success('Themes are synced!');

        return 0;
    }
}
