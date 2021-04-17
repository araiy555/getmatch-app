<?php

namespace App\Tests\Controller;

use App\Entity\Site;
use App\Entity\User;
use App\Repository\SiteRepository;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @covers \App\Controller\UserController
 */
class UserControllerTest extends WebTestCase {
    public function testSignUp(): void {
        $client = self::createClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/registration');

        $client->submit($crawler->selectButton('Sign up')->form([
            'user[username]' => 'shrek',
            'user[password][first]' => 'donkeykong123',
            'user[password][second]' => 'donkeykong123',
        ]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-nav__link strong', 'shrek');
        self::assertSelectorTextContains('.alert__text', 'Your account has been registered.');
    }

    public function testCannotSignUpWithPasswordLongerThan72Characters(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/registration');

        $password = str_repeat('a', 73);

        $client->submit($crawler->selectButton('Sign up')->form([
            'user[username]' => 'random4',
            'user[password][first]' => $password,
            'user[password][second]' => $password,
        ]));

        self::assertSelectorTextContains(
            '.form-error-list li',
            'This value is too long. It should have 72 characters or less.'
        );
    }

    public function testCannotSignUpWhileLoggedIn(): void {
        self::createUserClient()->request('GET', '/registration');

        self::assertResponseRedirects('/');
    }

    public function testCannotSignUpIfRegistrationsAreDisabled(): void {
        $client = self::createClient();

        /** @var \App\Entity\Site $site */
        $site = self::$container->get(SiteRepository::class)->findCurrentSite();
        $site->setRegistrationOpen(false);
        self::$container->get(EntityManagerInterface::class)->flush();

        $client->request('GET', '/registration');

        self::assertResponseStatusCodeSame(403);
        self::assertSelectorTextContains('.alert', 'disabled by the administrator');
    }

    public function testCannotSignUpWithInvalidCaptcha(): void {
        $client = self::createClient();
        self::enableRegistrationCaptcha();

        $crawler = $client->request('GET', '/registration');

        $client->submit($crawler->selectButton('Sign up')->form([
            'user[username]' => 'shrek',
            'user[password][first]' => 'donkeykong123',
            'user[password][second]' => 'donkeykong123',
            'user[verification]' => 'not valid',
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.form-error-list li', 'Code does not match');
    }

    public function testCanSignUpWithValidCaptcha(): void {
        $client = self::createClient();
        self::enableRegistrationCaptcha();

        $crawler = $client->request('GET', '/registration');
        $client->submit($crawler->selectButton('Sign up')->form([
            'user[username]' => 'shrek',
            'user[password][first]' => 'donkeykong123',
            'user[password][second]' => 'donkeykong123',
            'user[verification]' => 'bypass',
        ]));

        $this->assertResponseRedirects();

        // follow login link
        $client->followRedirect();
        $this->assertResponseRedirects('/');

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.user-logged-in');
        $this->assertBrowserHasCookie('REMEMBERME');
    }

    public function testCanChangeOwnUsernameAndRemainLoggedIn(): void {
        $client = self::createClientWithAuthenticatedUser('zach');
        $client->followRedirects();
        $crawler = $client->request('GET', '/user/zach/account');

        $client->submit($crawler->selectButton('Save changes')->form([
            'user[username]' => 'troll',
        ]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-nav__link strong', 'troll');
    }

    public function testCanChangeLocale(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/user/zach/preferences');

        $client->submit($crawler->selectButton('Save changes')->form([
            'user_settings[locale]' => 'nb',
        ]));

        $client->request('GET', '/');

        self::assertSelectorTextContains('a[href="/submit"]', 'Nytt innlegg');
    }

    public function testCanToggleFullWidthDisplay(): void {
        $client = self::createUserClient();
        $client->request('GET', '/user/zach/preferences');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.full-width');

        $client->submitForm('Save changes', [
            'user_settings[fullWidthDisplayEnabled]' => true,
        ]);
        self::assertResponseRedirects();

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.full-width');
    }

    public function testCanEditBiography(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/user/zach/edit_biography');

        $form = $crawler->selectButton('Save')->form([
            'user_biography[biography]' => 'tortilla enthusiast',
        ]);

        $client->submit($form);
        $client->request('GET', '/user/zach');

        self::assertSelectorTextContains('.user-bio__biography', 'tortilla enthusiast');
    }

    public function testDeleteAccount(): void {
        $client = self::createClientWithAuthenticatedUser('zach');

        $crawler = $client->request('GET', '/user/zach/delete_account');
        $client->submit($crawler->selectButton('Delete account')->form([
            'confirm_deletion[name]' => 'zach',
            'confirm_deletion[confirm]' => true,
        ]));
        self::assertResponseRedirects('/');

        $client->request('GET', '/user/zach');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/user/!deleted2');
        self::assertSelectorExists('body.user-anonymous');
        self::assertSelectorNotExists('.comment');
        self::assertSelectorNotExists('.submission');
    }

    public function testCanDeleteOtherPersonsAccountAsAdminWithoutBeingLoggedOut(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/user/zach/delete_account');
        $client->submit($crawler->selectButton('Delete account')->form([
            'confirm_deletion[name]' => 'zach',
            'confirm_deletion[confirm]' => true,
        ]));
        self::assertResponseRedirects('/');

        $client->request('GET', '/user/zach');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/');
        self::assertSelectorExists('body.user-logged-in');
    }

    public function testToggleNightMode(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/');
        self::assertSelectorExists('html[data-night-mode="auto"]');

        $crawler = $client->submit($crawler->selectButton('Dark mode')->form());
        self::assertSelectorExists('html[data-night-mode="dark"]');

        $client->submit($crawler->selectButton('Light mode')->form());
        self::assertSelectorExists('html[data-night-mode="light"]');
    }

    public function testCanClearSingleNotification(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/notifications');
        self::assertResponseIsSuccessful();

        $buttons = $crawler->filter('.clear-notification-button');
        $this->assertCount(2, $buttons);

        $client->submit($buttons->first()->form());
        self::assertResponseRedirects('/notifications');

        $crawler = $client->followRedirect();
        $buttons = $crawler->filter('.clear-notification-button');
        $this->assertCount(1, $buttons);
    }

    public function testCanClearAllNotificationsOnPage(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/notifications');
        $client->submit($crawler->selectButton('Clear all')->form());
        self::assertResponseRedirects('/notifications');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.clear-notification-button');
    }

    public function testCanListUsers(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/users');

        $this->assertCount(3, $crawler->filter('main tbody tr'));
    }

    public function testCanFilterUsersByRole(): void {
        $client = self::createAdminClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/users');

        $crawler = $client->submit($crawler->selectButton('Filter')->form([
            'user_filter[role]' => 'admin',
        ]));

        $this->assertCount(1, $crawler->filter('main tbody tr'));
    }

    public function testCanHideAndUnhideForums(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats');

        $client->submit($crawler->filter('.sidebar')->selectButton('Hide')->form());
        self::assertResponseRedirects('http://localhost/f/cats');

        $crawler = $client->request('GET', '/user/emma/hidden_forums');
        self::assertSelectorTextContains('main tbody tr td', 'cats');

        $client->submit($crawler->selectButton('Delete')->form());

        $client->followRedirect();
        self::assertSelectorNotExists('main tbody');
    }

    public function testCanBlockAndUnblockUsers(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/user/zach/block_user');

        $client->submit($crawler->selectButton('Block')->form([
            'user_block[comment]' => 'shit head',
        ]));

        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('main tbody tr td', 'zach');

        $client->submit($crawler->selectButton('Unblock')->form());
        $client->followRedirect();
        self::assertSelectorNotExists('main tbody tr td');
    }

    public function testUserIsLoggedOutOnChange(): void {
        $client = self::createClientWithAuthenticatedUser('emma');
        $client->request('GET', '/');

        self::assertSelectorExists('body.user-logged-in');

        $this->changeUser();

        $client->request('GET', '/');
        self::assertSelectorExists('body.user-anonymous');
    }

    public function testCanViewOwnTrash(): void {
        $client = self::createUserClient();
        $client->request('GET', '/user/zach/trash');

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.submission__title', 'Submission in the trash');
    }

    public function testLoggedOutUsersArePromptedToLogInWhenAccessingTrash(): void {
        $client = self::createClient();
        $client->request('GET', '/user/zach/trash');

        self::assertResponseRedirects('/login');
    }

    public function testCannotViewAnotherPersonsTrash(): void {
        $client = self::createUserClient();
        $client->request('GET', '/user/emma/trash');

        self::assertResponseStatusCodeSame(403);
    }

    private function changeUser(): void {
        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /** @var User $user */
        $user = $em->find(User::class, '1');
        $user->setUsername('not_emma');
        $em->persist($user);
        $em->flush();
    }

    private static function enableRegistrationCaptcha(): void {
        /** @var Site $site */
        $site = self::$container->get(SiteRepository::class)->findCurrentSite();
        $site->setRegistrationCaptchaEnabled(true);
        self::$container->get(EntityManagerInterface::class)->flush();
    }
}
