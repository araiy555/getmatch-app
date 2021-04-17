<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class ChangePasswordCommand extends Command {
    protected static $defaultName = 'postmill:change-password';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var UserRepository
     */
    private $users;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        UserRepository $users
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->users = $users;

        parent::__construct();
    }

    protected function configure(): void {
        $this
            ->addArgument('user', InputArgument::REQUIRED, 'The user to change the password for')
            ->addOption('find-by-id', null, InputOption::VALUE_NONE, 'Look up user by ID instead of username')
            ->setDescription('Changes the password for a user account')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('user');

        if (!$input->getOption('find-by-id')) {
            $user = $this->users->loadUserByUsername($username);
        } else {
            $user = $this->users->find($username);
        }

        if (!$user) {
            $io->error(sprintf('No such user "%s"', $username));

            return 1;
        }

        $password = $io->askHidden('Type the new password', static function ($input) {
            if (\strlen($input) < 8 || \strlen($input) > 72) {
                throw new \RuntimeException('Password must be between 8 and 72 characters long');
            }

            return $input;
        });

        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $this->entityManager->flush();

        $io->success(sprintf('The password was changed for %s', $username));

        return 0;
    }
}
