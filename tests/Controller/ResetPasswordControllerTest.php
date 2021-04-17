<?php

namespace App\Tests\Controller;

use App\Controller\ResetPasswordController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\PasswordResetHelper;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @covers \App\Controller\ResetPasswordController
 * @group time-sensitive
 */
class ResetPasswordControllerTest extends WebTestCase {
    /**
     * @var AbstractBrowser
     */
    private $client;

    /**
     * @var PasswordResetHelper
     */
    private $helper;

    public static function setUpBeforeClass(): void {
        ClockMock::register(PasswordResetHelper::class);
        ClockMock::register(ResetPasswordController::class);
    }

    protected function setUp(): void {
        $this->client = self::createClient();
        $this->helper = self::$container->get(PasswordResetHelper::class);
    }

    public function testCanRequestPasswordReset(): void {
        $crawler = $this->client->request('GET', '/reset_password');

        $form = $crawler->selectButton('Submit')->form([
            'request_password_reset[email]' => 'emma@example.com',
            'request_password_reset[verification]' => 'bypass',
        ]);

        $this->client->submit($form);

        $user = $this->getUser();

        self::assertResponseRedirects();
        self::assertEmailCount(1);
        $mail = self::getMailerMessage(0);
        self::assertEmailHeaderSame($mail, 'From', 'Postmill <no-reply@example.com>');
        self::assertEmailHeaderSame($mail, 'To', 'emma <emma@example.com>');
        self::assertEmailTextBodyContains($mail, $this->helper->generateResetUrl($user));
    }

    public function testCanResetPassword(): void {
        $user = $this->getUser();
        $url = $this->helper->generateResetUrl($user);

        $crawler = $this->client->request('GET', $url);

        $form = $crawler->selectButton('Save')->form([
            'user[password][first]' => 'badshit1',
            'user[password][second]' => 'badshit1',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects();

        /** @var UserPasswordEncoderInterface $encoder */
        $encoder = self::$container->get('security.password_encoder');

        $user = self::$container->get(UserRepository::class)->findOneByUsername('emma');

        $this->assertTrue($encoder->isPasswordValid($user, 'badshit1'));
    }

    public function testResetLinkDoesNotWorkAfterTwentyFourHours(): void {
        $url = $this->helper->generateResetUrl($this->getUser());

        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();

        sleep(86400);

        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(403);
    }

    public function testResetLinkWithBogusUrlDoesNotWork(): void {
        $hash = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        $this->client->request('GET', 'http://localhost/reset_password/1/'.time().'/'.$hash);

        self::assertResponseStatusCodeSame(403);
    }

    private function getUser(): User {
        return self::$container->get(UserRepository::class)
            ->findOneByUsername('emma');
    }
}
