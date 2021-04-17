<?php

namespace App\Tests\Controller;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Repository\SiteRepository;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @covers \App\Controller\SubmissionController
 */
class SubmissionControllerTest extends WebTestCase {
    public function testCanCreateSubmission(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/submit');

        $form = $crawler->selectButton('Create submission')->form([
            'submission[title]' => 'Making a submission',
            'submission[url]' => 'http://www.foo.example/',
            'submission[body]' => "This is a test submission\n\na new line",
            'submission[forum]' => '2',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();

        $crawler = $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSame('Making a submission', $crawler->filter('.submission__link')->text());
        $this->assertSame('http://www.foo.example/', $crawler->filter('.submission__link')->attr('href'));
        $this->assertStringContainsString(
            'This is a test submission',
            $crawler->filter('.submission__body')->html(),
        );
    }

    public function testCanCreateSubmissionWithImage(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/submit');

        $form = $crawler->selectButton('Create submission')->form([
            'submission[title]' => 'Submission with image',
            'submission[mediaType]' => 'image',
            'submission[forum]' => '2',
        ]);
        /* @noinspection PhpPossiblePolymorphicInvocationInspection */
        $form['submission[image]']->upload(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png');

        $client->submit($form);

        self::assertResponseRedirects();
        $crawler = $client->followRedirect();

        self::assertResponseIsSuccessful();
        $this->assertSame(
            'http://localhost/submission_images/a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9.png',
            $crawler->filter('.submission__link')->attr('href')
        );
    }

    public function testCannotCreateSubmissionWithInvalidImage(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/submit');

        $form = $crawler->selectButton('Create submission')->form([
            'submission[title]' => 'Non-submission with non-image',
            'submission[mediaType]' => 'image',
            'submission[forum]' => '2',
        ]);
        /* @noinspection PhpPossiblePolymorphicInvocationInspection */
        $form['submission[image]']->upload(__DIR__.'/../Resources/garbage.bin');

        $client->submit($form);

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.form-error-list', 'This file is not a valid image.');
    }

    public function testCannotSubmitWithBannedPhrase(): void {
        $client = self::createUserClient();
        $client->request('GET', '/submit');

        $client->submitForm('Create submission', [
            'submission[title]' => 'I like Orson Pig :))',
            'submission[forum]' => '2',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.form-error-list', 'The field contains a banned word or phrase.');
    }

    /**
     * @see https://gitlab.com/postmill/Postmill/-/issues/74
     */
    public function testCannotEditSubmissionToIncludeBannedPhrase(): void {
        $client = self::createUserClient();
        $client->request('GET', '/f/cats/3/-/edit');

        $client->submitForm('Edit submission', [
            'submission[body]' => 'orson pig',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.form-error-list', 'The field contains a banned word or phrase');
    }

    /**
     * @see https://gitlab.com/postmill/Postmill/-/issues/63
     */
    public function testSubmittingWithWhitespaceOnlyBodyDoesNotFail(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/submit/cats');

        $form = $crawler->selectButton('Create submission')->form([
            'submission[title]' => 'whitespace body',
            'submission[body]' => " \r\n \r\n ",
        ]);
        $client->submit($form);

        self::assertResponseRedirects();

        $client->followRedirect();
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.submission__title', 'whitespace body');
    }

    public function testSubmissionJson(): void {
        $client = self::createClient();
        $client->request('GET', '/f/news/1.json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('http://www.example.com/some/thing', $data['url']);
        $this->assertSame('A submission with a URL and body', $data['title']);
        $this->assertSame('This is a body.', $data['body']);
        $this->assertSame('2017-03-03T03:03:00+00:00', $data['timestamp']);
        $this->assertStringContainsString(
            'This is a body.',
            $data['renderedBody'],
        );
    }

    public function testSubmissionShortcut(): void {
        $client = self::createClient();
        $client->request('GET', '/1');

        $this->assertTrue($client->getResponse()->isRedirect('/f/news/1/a-submission-with-a-url-and-body'));
    }

    public function testEditingOwnSubmission(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->click($crawler->selectLink('Edit')->link());
        $crawler = $client->submit($crawler->selectButton('Edit submission')->form([
            'submission[url]' => 'http://edited.url.example/',
            'submission[title]' => 'Edited submission title',
            'submission[body]' => 'Edited body',
        ]));

        $this->assertSame('http://edited.url.example/', $crawler->filter('.submission__link')->attr('href'));
        self::assertSelectorTextContains('.submission__link', 'Edited submission title');
        self::assertSelectorTextContains('.submission__body', 'Edited body');
    }

    public function testDeletingOwnSubmissionWithCommentsResultsInSoftDeletion(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/f/cats/3');
        $client->submit($crawler->selectButton('Delete')->form());

        $client->request('GET', '/f/cats/3');

        self::assertSelectorTextContains('.submission__link', '[deleted]');
    }

    public function testDeletingOwnSubmissionWithoutCommentsResultsInHardDeletion(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->submit($crawler->filter('.comment')->selectButton('Delete')->form());
        $client->submit($crawler->selectButton('Delete')->form());

        $client->request('GET', '/f/cats/3');
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testTrashingSubmissionOfOtherUser(): void {
        $client = self::createAdminClient();
        $client->followRedirects();

        self::$container->get(SiteRepository::class)->findCurrentSite()->setTrashEnabled(true);
        self::$container->get(EntityManagerInterface::class)->flush();

        $crawler = $client->request('GET', '/f/cats/3');
        self::assertSelectorNotExists('.submission__trashed-icon');

        $crawler = $client->click($crawler->selectLink('Delete')->link());
        $client->submit($crawler->selectButton('Delete')->form([
            'delete_reason[reason]' => 'some reason',
        ]));

        self::assertSelectorTextContains('.alert__text', 'The submission was deleted.');

        $client->request('GET', '/f/cats/3');
        self::assertSelectorExists('.submission__trashed-icon');
    }

    public function testSubmissionLocking(): void {
        $client = self::createAdminClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/f/news/1');

        $crawler = $client->submit($crawler->selectButton('Lock')->form());
        $this->assertCount(1, $crawler->filter('.submission--locked'));
        $this->assertCount(1, $crawler->filter('.submission__locked-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was locked.');

        $crawler = $client->submit($crawler->selectButton('Unlock')->form());
        $this->assertCount(0, $crawler->filter('.submission--locked'));
        $this->assertCount(0, $crawler->filter('.submission__locked-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was unlocked.');
    }

    public function testSubmissionPinning(): void {
        $client = self::createAdminClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/f/news/1');

        $crawler = $client->submit($crawler->selectButton('Pin')->form());
        $this->assertCount(1, $crawler->filter('.submission--sticky'));
        $this->assertCount(1, $crawler->filter('.submission__sticky-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was pinned.');

        $crawler = $client->submit($crawler->selectButton('Unpin')->form());
        $this->assertCount(0, $crawler->filter('.submission--sticky'));
        $this->assertCount(0, $crawler->filter('.submission__sticky-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was unpinned.');
    }

    /**
     * @dataProvider selfDeleteReferrerProvider
     */
    public function testRedirectsProperlyAfterDelete(string $expected, string $referrer): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', $referrer);

        $client->submit($crawler->selectButton('Delete')->form());

        self::assertResponseRedirects($expected, null, "expected: $expected, referrer: $referrer");
    }

    /**
     * @covers \App\Controller\UserController::notifications
     */
    public function testCanReceiveSubmissionNotifications(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/3');

        $form = $crawler->selectButton('Post')->form([
            'reply_to_submission_3[comment]' => 'You will be notified about this comment.',
        ]);

        $client->submit($form);
        self::ensureKernelShutdown();

        $client = self::createUserClient();
        $client->request('GET', '/notifications');

        self::assertSelectorTextContains('.comment__body', 'You will be notified about this comment.');
    }

    /**
     * @see https://gitlab.com/postmill/Postmill/-/issues/59
     *
     * @group time-sensitive
     */
    public function testNonWhitelistedUsersGetErrorWhenPostingRapidly(): void {
        $client = self::createUserClient();

        for ($i = 0; $i < 3; $i++) {
            $crawler = $client->request('GET', '/submit/cats');
            $client->submit($crawler->selectButton('Create submission')->form([
                'submission[title]' => 'post '.$i,
            ]));
            $client->followRedirect();
            self::assertSelectorTextContains('.submission__title', 'post '.$i);
        }

        $crawler = $client->request('GET', '/submit/cats');
        $client->submit($crawler->selectButton('Create submission')->form([
            'submission[title]' => 'will not be posted',
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains(
            '.form-error-list li',
            'You cannot post more. Wait a while before trying again.'
        );
    }

    public function testCanRestoreDeletedSubmissions(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/f/cats/4');
        self::assertSelectorExists('.submission__trashed-icon');

        $client->submit($crawler->filter('.submission')->selectButton('Restore')->form());
        self::assertResponseRedirects();

        $client->followRedirect();
        self::assertSelectorNotExists('.submission__trashed-icon');
    }

    public function testLoggedOutUsersCannotAccessTrashedSubmissions(): void {
        $client = self::createClient();
        $client->request('GET', '/f/cats/4');

        self::assertResponseStatusCodeSame(403);
    }

    public function testNonModeratorNonSubmitterUserCannotAccessTrashedSubmissions(): void {
        $client = self::createClientWithAuthenticatedUser('third');
        $client->request('GET', '/f/cats/4');

        self::assertResponseStatusCodeSame(403);
    }

    public function selfDeleteReferrerProvider(): iterable {
        yield ['http://localhost/', '/'];
        yield ['http://localhost/f/cats', '/f/cats'];
        yield ['/f/cats', '/f/cats/3'];
        yield ['/f/cats', '/f/cats/3/with-slug'];
    }

    public function testSubmissionLinksOutsideSubmissionHaveCorrectDestination(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/f/news');
        $link = $crawler->filterXPath('//a[normalize-space(text()) = "A submission with a URL and body"]');

        $this->assertCount(1, $link);
        $this->assertStringContainsString('://', $link->attr('href'));

        self::$container->get(SiteRepository::class)
            ->findCurrentSite()
            ->setSubmissionLinkDestination(SubmissionLinkDestination::SUBMISSION);
        self::$container->get(EntityManagerInterface::class)->flush();

        $crawler = $client->request('GET', '/f/news');
        $link = $crawler->filterXPath('//a[normalize-space(text()) = "A submission with a URL and body"]');

        $this->assertCount(1, $link);
        $this->assertStringNotContainsString('://', $link->attr('href'));
    }

    public function testSubmissionLinksInsideSubmissionGoToExternalUrl(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/f/news/1');
        $link = $crawler->filterXPath('//a[normalize-space(text()) = "A submission with a URL and body"]');

        $this->assertCount(1, $link);
        $this->assertStringContainsString('://', $link->attr('href'));

        self::$container->get(SiteRepository::class)
            ->findCurrentSite()
            ->setSubmissionLinkDestination(SubmissionLinkDestination::SUBMISSION);
        self::$container->get(EntityManagerInterface::class)->flush();

        $crawler = $client->request('GET', '/f/news/1');
        $link = $crawler->filterXPath('//a[normalize-space(text()) = "A submission with a URL and body"]');

        $this->assertCount(1, $link);
        $this->assertStringContainsString('://', $link->attr('href'));
    }
}
