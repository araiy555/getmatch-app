<?php

namespace App\Command;

use App\Repository\Contracts\PrunesIpAddresses;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for removing IP addresses associated with some entities.
 *
 * This is intended to be run in a cron job or similar to ensure visitor
 * privacy.
 */
final class PruneIpAddressesCommand extends Command {
    protected static $defaultName = 'postmill:prune-ips';

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var \App\Repository\Contracts\PrunesIpAddresses[]
     */
    private $pruners;

    /**
     * @param \App\Repository\Contracts\PrunesIpAddresses[]|iterable $pruners
     */
    public function __construct(EntityManagerInterface $manager, iterable $pruners) {
        parent::__construct();

        $this->manager = $manager;
        $this->pruners = $pruners;
    }

    protected function configure(): void {
        $this
            ->setAliases(['app:prune-ips'])
            ->setDescription('Prunes IP addresses associated with some entities')
            ->addOption('max-age', 'm', InputOption::VALUE_REQUIRED,
                'The maximum age (strtotime format) of an entity in seconds before its IP address is cleared.'
            )
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE,
                'Don\'t apply the changes to the database.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        if (!$io->confirm('Are you sure you wish to prune IP addresses?', !$input->isInteractive())) {
            $io->text('Aborting...');

            return 1;
        }

        if ($input->getOption('max-age')) {
            if (strtotime($input->getOption('max-age')) === false) {
                $io->error('Invalid date format');

                return 1;
            }

            $nowTime = new \DateTimeImmutable('@'.time());
            $maxTime = $nowTime->modify($input->getOption('max-age'));

            if ($maxTime > $nowTime) {
                $io->error('max-age option cannot be a future time');

                if ($io->isDebug()) {
                    $io->comment('now: '.$nowTime->format('c'));
                    $io->comment('max: '.$maxTime->format('c'));
                }

                return 1;
            }
        } else {
            $maxTime = null;
        }

        $this->manager->beginTransaction();

        $count = 0;
        foreach ($this->pruners as $pruner) {
            $count += $pruner->pruneIpAddresses($maxTime);
        }

        if ($input->getOption('dry-run')) {
            $this->manager->rollback();
        } else {
            $this->manager->commit();
            $this->manager->flush();
        }

        if ($count > 0) {
            $io->success(sprintf('Pruned IPs for %s entit%s.',
                number_format($count),
                $count !== 1 ? 'ies' : 'y'
            ));
        } else {
            $io->note('No entities with IP addresses.');
        }

        return 0;
    }
}
