<?php

namespace App\Tests;

/**
 * Simple availability tests to ensure the application isn't majorly screwed up.
 *
 * @coversNothing
 */
class ApplicationAvailabilityTest extends WebTestCase {
    /**
     * @dataProvider publicUrlProvider
     */
    public function testCanAccessPublicPages(string $url): void {
        $client = self::createClient();
        $client->request('GET', $url);

        self::assertResponseIsSuccessful("URL: $url");
    }

    /**
     * @dataProvider authUrlProvider
     */
    public function testCanAccessPagesThatNeedAuthentication(string $url): void {
        $client = self::createAdminClient();
        $client->request('GET', $url);

        self::assertResponseIsSuccessful("URL: $url");
    }

    /**
     * @dataProvider authUrlProvider
     */
    public function testCannotAccessPagesThatNeedAuthenticationWhenNotAuthenticated(string $url): void {
        self::createClient()->request('GET', $url);

        self::assertResponseRedirects('/login', null, "URL: $url");
    }

    /**
     * @dataProvider redirectUrlProvider
     */
    public function testRedirectedUrlsGoToExpectedLocation(string $expectedLocation, string $url): void {
        $client = self::createClient();
        $client->followRedirects();
        $client->request('GET', $url);

        self::assertResponseIsSuccessful("URL: $url; expected: $expectedLocation");

        $this->assertSame(
            "http://localhost{$expectedLocation}",
            $client->getHistory()->current()->getUri(),
            "URL: $url; expected: $expectedLocation"
        );
    }

    /**
     * Public URLs that should exist when fixtures are loaded into a fresh
     * database.
     */
    public function publicUrlProvider(): \Generator {
        yield ['/'];
        yield ['/hot'];
        yield ['/new'];
        yield ['/active'];
        yield ['/top'];
        yield ['/controversial'];
        yield ['/most_commented'];
        yield ['/all/hot'];
        yield ['/all/new'];
        yield ['/all/active'];
        yield ['/all/top'];
        yield ['/all/controversial'];
        yield ['/all/most_commented'];
        yield ['/all/hot.atom'];
        yield ['/all/new.atom'];
        yield ['/all/active.atom'];
        yield ['/all/top.atom'];
        yield ['/all/controversial.atom'];
        yield ['/all/most_commented.atom'];
        yield ['/featured/hot'];
        yield ['/featured/new'];
        yield ['/featured/top'];
        yield ['/featured/controversial'];
        yield ['/featured/most_commented'];
        yield ['/featured/hot.atom'];
        yield ['/featured/new.atom'];
        yield ['/featured/active.atom'];
        yield ['/featured/top.atom'];
        yield ['/featured/controversial.atom'];
        yield ['/featured/most_commented.atom'];
        yield ['/tag/pets'];
        yield ['/tag/pets/hot'];
        yield ['/tag/pets/new'];
        yield ['/tag/pets/active'];
        yield ['/tag/pets/top'];
        yield ['/tag/pets/controversial'];
        yield ['/tag/pets/most_commented'];
        yield ['/f/news'];
        yield ['/f/news/hot'];
        yield ['/f/news/new'];
        yield ['/f/news/active'];
        yield ['/f/news/top'];
        yield ['/f/news/controversial'];
        yield ['/f/news/most_commented'];
        yield ['/f/news/hot.atom'];
        yield ['/f/news/new.atom'];
        yield ['/f/news/active.atom'];
        yield ['/f/news/top.atom'];
        yield ['/f/news/controversial.atom'];
        yield ['/f/news/most_commented.atom'];
        yield ['/f/news/1/-/comment/1'];
        yield ['/f/news/bans'];
        yield ['/f/news/comments'];
        yield ['/f/news/moderation_log'];
        yield ['/forums'];
        yield ['/forums/by_name'];
        yield ['/forums/by_title'];
        yield ['/forums/by_subscribers'];
        yield ['/forums/by_submissions'];
        yield ['/forums/by_name/1'];
        yield ['/forums/by_title/1'];
        yield ['/forums/by_subscribers/1'];
        yield ['/forums/by_submissions/1'];
        yield ['/comments'];
        yield ['/wiki'];
        yield ['/login'];
        yield ['/registration'];
        yield ['/user/emma'];
        yield ['/user/emma/submissions'];
        yield ['/user/emma/comments'];
        yield ['/reset_password'];
    }

    public function redirectUrlProvider(): iterable {
        yield ['/tag/pets', '/c/pets'];
        yield ['/tag/pets/hot', '/c/pets/hot'];
        yield ['/tag/pets/new', '/c/pets/new'];
        yield ['/tag/pets/active', '/c/pets/active'];
        yield ['/tag/pets/top', '/c/pets/top'];
        yield ['/tag/pets/controversial', '/c/pets/controversial'];
        yield ['/tag/pets/most_commented', '/c/pets/most_commented'];
        yield ['/tag/pets', '/tag/PETS'];
        yield ['/f/news', '/f/news/'];
        yield ['/f/news/new', '/f/NeWs/new'];
        yield ['/f/news/top', '/f/NeWs/top'];
        yield ['/f/news/controversial', '/f/NeWs/controversial'];
        yield ['/f/news/most_commented', '/f/NeWs/most_commented'];
        yield ['/f/news/1/-/comment/1', '/f/NeWs/1/comment/1'];
    }

    /**
     * URLs that need authentication to access.
     */
    public function authUrlProvider(): iterable {
        yield ['/subscribed/hot'];
        yield ['/subscribed/new'];
        yield ['/subscribed/active'];
        yield ['/subscribed/top'];
        yield ['/subscribed/controversial'];
        yield ['/subscribed/most_commented'];
        yield ['/moderated/hot'];
        yield ['/moderated/new'];
        yield ['/moderated/active'];
        yield ['/moderated/top'];
        yield ['/moderated/controversial'];
        yield ['/moderated/most_commented'];
        yield ['/create_forum'];
        yield ['/f/news/edit'];
        yield ['/f/news/appearance'];
        yield ['/f/news/add_moderator'];
        yield ['/f/news/delete'];
        yield ['/notifications'];
        yield ['/submit'];
        yield ['/submit/news'];
        yield ['/user/emma/block_list'];
        yield ['/f/cats/trash'];
        yield ['/trash'];
        yield ['/site/bad_phrases'];
        yield ['/site/bad_phrases/search'];
        yield ['/site/themes'];
        yield ['/site/themes/css/create'];
        yield ['/site/trash'];
        yield ['/user/emma/trash'];
    }
}
