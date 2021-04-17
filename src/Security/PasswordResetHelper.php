<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetHelper {
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string|null
     */
    private $noReplyAddress;

    /**
     * @var string
     */
    private $secret;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ?string $noReplyAddress,
        string $secret
    ) {
        if ($secret === '') {
            throw new \InvalidArgumentException('$secret is empty');
        }

        $this->urlGenerator = $urlGenerator;
        $this->noReplyAddress = $noReplyAddress;
        $this->secret = $secret;
    }

    public function canReset(): bool {
        return !empty($this->noReplyAddress);
    }

    public function generateResetUrl(User $user): string {
        $expires = time() + 86400; // 24 hours

        return $this->urlGenerator->generate('password_reset', [
            'id' => $user->getId(),
            'expires' => $expires,
            'checksum' => $this->createChecksum($user, $expires),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Create a checksum based on an expiry time and a user's ID & hashed
     * password.
     */
    public function createChecksum(User $user, int $expires): string {
        $data = sprintf('%d~%s~%d',
            $user->getId(),
            $user->getPassword(),
            $expires
        );

        return hash_hmac('sha256', $data, $this->secret);
    }

    /**
     * Ensure checksum is valid and link hasn't expired.
     *
     * @throws AccessDeniedHttpException
     */
    public function denyUnlessValidChecksum(string $checksum, User $user, int $expires): void {
        if (time() >= $expires) {
            throw new AccessDeniedHttpException('Link expired');
        }

        if (!hash_equals($this->createChecksum($user, $expires), $checksum)) {
            throw new AccessDeniedHttpException('Invalid checksum');
        }
    }
}
