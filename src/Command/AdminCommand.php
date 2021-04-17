<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class AdminCommand extends Command {
    protected static $defaultName = 'postmill:admin';

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var UserRepository
     */
    private $users;

    public function __construct(EntityManagerInterface $manager, UserRepository $users) {
        $this->manager = $manager;
        $this->users = $users;

        parent::__construct();
    }

    protected function configure(): void {
        $this
            ->addArgument('user', InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'User(s) to manage admin privileges for')
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Remove privileges')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command gives or takes admin privileges from one or more users.

Examples:

- Giving admin privileges to emma and zach:
  <info>php %command.full_name% emma zach</info>

- Taking admin privileges from bob:
  <info>php %command.full_name% bob --remove</info>
EOF
)
            ->setDescription("Give or take users' admin privileges")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $remove = $input->getOption('remove');
        $users = $this->users->findByNormalizedUsername(array_map(
            [User::class, 'normalizeUsername'],
            $input->getArgument('user')
        ));

        if (!\count($users)) {
            $io->error('No user(s) found');

            return 1;
        }

        $this->manager->transactional(static function () use ($users, $remove): void {
            foreach ($users as $user) {
                $user->setAdmin(!$remove);
            }
        });

        $io->success(sprintf('%d user(s) were %sadmined', \count($users), $remove ? 'de-' : ''));

        return 0;
    }
}
