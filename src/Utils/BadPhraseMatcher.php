<?php

namespace App\Utils;

use App\Entity\BadPhrase;
use App\Repository\BadPhraseRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BadPhraseMatcher {
    /**
     * @var BadPhraseRepository
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        BadPhraseRepository $repository,
        ?LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
    }

    public function matches(string $subject): bool {
        foreach ($this->buildRegexes() as $regex) {
            $match = @preg_match($regex, $subject);

            if ($match === false) {
                $this->logger->error('Bad phrase: regex compilation failed', [
                    'error' => error_get_last()['message'],
                    'pattern' => $regex,
                    'subject' => $subject,
                ]);
            } elseif ($match) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return BadPhrase[]
     */
    public function findMatching(string $subject): array {
        $ids = [];

        foreach ($this->repository->findAll() as $entry) {
            switch ($entry->getPhraseType()) {
            case BadPhrase::TYPE_TEXT:
                $regex = '@\b'.preg_quote($entry->getPhrase(), '@').'\b@u';
                break;
            case BadPhrase::TYPE_REGEX:
                $regex = '@'.addcslashes($entry->getPhrase(), '@');

                if (preg_match('@\(\?[A-Za-z]*?x[A-Za-z]*\).*[^\\\\]#@', $entry->getPhrase())) {
                    // handle (?x) with comment
                    $regex .= "\n";
                }

                $regex .= '@u';
                break;
            default:
                throw new \DomainException('Unknown phrase type');
            }

            $match = @preg_match($regex, $subject, $matches);

            if ($match) {
                $ids[] = $entry->getId();
            } elseif ($match === false) {
                $this->logger->error('Regex compilation failed', [
                    'error' => error_get_last()['message'],
                    'pattern' => $regex,
                    'subject' => $subject,
                ]);
            }
        }

        return $this->repository->findBy(['id' => $ids], [
            'timestamp' => 'DESC',
            'id' => 'ASC',
        ]);
    }

    /**
     * @return string[]
     */
    private function buildRegexes(): array {
        $regexStart = '@';
        $regexEnd = '@u';
        $partOpen = '(?:';
        $partClose = ')';
        $maxRegexLength = 0x7ffe - \strlen($regexStart) + \strlen($regexEnd);

        $regexes = [];
        $regex = $regexStart;
        $regexLength = \strlen($regex);

        foreach ($this->repository->findAll() as $entry) {
            $part = $partOpen;

            switch ($entry->getPhraseType()) {
            case BadPhrase::TYPE_TEXT:
                $part .= '(?i)\b'.preg_quote($entry->getPhrase(), '@').'\b';
                break;
            case BadPhrase::TYPE_REGEX:
                $part .= addcslashes($entry->getPhrase(), '@');
                if (preg_match('@\(\?[A-Za-z]*?x[A-Za-z]*\).*[^\\\\]#@', $part)) {
                    // handle (?x) with comment
                    $part .= "\n";
                }
                break;
            default:
                throw new \DomainException('Unknown phrase type');
            }

            $part .= $partClose;
            $partLength = \strlen($part);

            $concatOverhead = $regexLength > \strlen($regexStart) ? 1 : 0;

            if ($regexLength + $partLength + $concatOverhead > $maxRegexLength) {
                $regex .= $regexEnd;
                $regexes[] = $regex;
                $regex = $regexStart;
                $regexLength = \strlen($regexStart);
            }

            if ($regexLength > \strlen($regexStart)) {
                $part = "|$part";
                $partLength += 1;
            }

            $regex .= $part;
            $regexLength += $partLength;
        }

        if ($regexLength > \strlen($regexStart)) {
            $regex .= $regexEnd;
            $regexes[] = $regex;
        }

        $this->logger->debug('Bad phrase: regexes built', [
            'regexes' => $regexes,
        ]);

        return $regexes;
    }
}
