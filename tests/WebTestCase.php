<?php

namespace App\Tests;

use App\Repository\UserRepository;
use PHPUnit\Framework\Constraint\LogicalAnd;
use PHPUnit\Framework\Constraint\LogicalOr;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseHeaderSame;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseIsRedirected;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseStatusCodeSame;

abstract class WebTestCase extends BaseWebTestCase {
    /**
     * Override Symfony's `assertResponseRedirects` to let "/foo" also match
     * "http://localhost/foo".
     */
    public static function assertResponseRedirects(
        string $expectedLocation = null,
        int $expectedCode = null,
        string $message = ''
    ): void {
        $constraint = new ResponseIsRedirected();

        if ($expectedLocation) {
            $constraint = LogicalAnd::fromConstraints(
                $constraint,
                new ResponseHeaderSame('Location', $expectedLocation),
            );

            if (preg_match('~^/(?!/)~', $expectedLocation)) {
                $constraint = LogicalOr::fromConstraints(
                    $constraint,
                    new ResponseHeaderSame(
                        'Location',
                        "http://localhost{$expectedLocation}",
                    ),
                );
            }
        }

        if ($expectedCode) {
            $constraint = LogicalAnd::fromConstraints(
                $constraint,
                new ResponseStatusCodeSame($expectedCode),
            );
        }

        self::assertThatForResponse($constraint, $message);
    }

    public static function createAdminClient(): KernelBrowser {
        return self::createClientWithAuthenticatedUser('emma');
    }

    public static function createUserClient(): KernelBrowser {
        return self::createClientWithAuthenticatedUser('araiy');
    }

    public static function createClientWithAuthenticatedUser(string $username): KernelBrowser {
        $client = self::createClient([], [
            'HTTP_X_EXPERIMENTAL_API' => 1,
        ]);

        $user = self::$container
            ->get(UserRepository::class)
            ->loadUserByUsername($username);

        $client->loginUser($user);

        return $client;
    }
}
