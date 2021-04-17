<?php

namespace App\Tests\Entity;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Entity\CssTheme;
use App\Entity\Site;
use App\Entity\Submission;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * @covers \App\Entity\Site
 */
class SiteTest extends TestCase {
    private function site(): Site {
        return new Site();
    }

    public function testGetId(): void {
        $this->assertEquals(Uuid::NIL, $this->site()->getId());
    }

    public function testGetSiteName(): void {
        $this->assertSame('Postmill', $this->site()->getSiteName());
    }

    public function testSetSiteName(): void {
        $site = $this->site();

        $site->setSiteName('Ghostmill');

        $this->assertSame('Ghostmill', $site->getSiteName());
    }

    public function testIsRegistrationOpen(): void {
        $this->assertTrue($this->site()->isRegistrationOpen());
    }

    public function testSetRegistrationOpen(): void {
        $site = $this->site();

        $site->setRegistrationOpen(false);

        $this->assertFalse($site->isRegistrationOpen());
    }

    public function testGetDefaultSortMode(): void {
        $this->assertSame(
            Submission::SORT_HOT,
            $this->site()->getDefaultSortMode(),
        );
    }

    public function testSetDefaultSortMode(): void {
        $site = $this->site();

        $site->setDefaultSortMode(Submission::SORT_ACTIVE);

        $this->assertSame(Submission::SORT_ACTIVE, $site->getDefaultSortMode());
    }

    public function testGetDefaultTheme(): void {
        $this->assertNull($this->site()->getDefaultTheme());
    }

    public function testSetDefaultTheme(): void {
        $site = $this->site();
        $theme = new CssTheme('a', 'a{}');

        $site->setDefaultTheme($theme);

        $this->assertSame($theme, $site->getDefaultTheme());
    }

    public function testIsWikiEnabled(): void {
        $this->assertTrue($this->site()->isWikiEnabled());
    }

    public function testSetWikiEnabled(): void {
        $site = $this->site();

        $site->setWikiEnabled(false);

        $this->assertFalse($site->isWikiEnabled());
    }

    public function testGetForumCreateRole(): void {
        $this->assertSame('ROLE_USER', $this->site()->getForumCreateRole());
    }

    /**
     * @dataProvider provideUserRoles
     */
    public function testSetForumCreateRole(string $role): void {
        $site = $this->site();

        $site->setForumCreateRole($role);

        $this->assertSame($role, $site->getForumCreateRole());
    }

    public function testThrowsOnInvalidForumCreateRole(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->site()->setForumCreateRole('invalid');
    }

    public function testGetImageUploadRole(): void {
        $this->assertSame('ROLE_USER', $this->site()->getImageUploadRole());
    }

    /**
     * @dataProvider provideUserRoles
     */
    public function testSetImageUploadRole(string $role): void {
        $site = $this->site();

        $site->setImageUploadRole($role);

        $this->assertSame($role, $site->getImageUploadRole());
    }

    public function testThrowsOnInvalidImageUploadRole(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->site()->setImageUploadRole('invalid');
    }

    public function testGetWikiEditRole(): void {
        $this->assertSame('ROLE_USER', $this->site()->getWikiEditRole());
    }

    /**
     * @dataProvider provideUserRoles
     */
    public function testSetWikiEditRole(string $role): void {
        $site = $this->site();

        $site->setWikiEditRole($role);

        $this->assertSame($role, $site->getWikiEditRole());
    }

    public function testThrowsOnInvalidWikiEditRole(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->site()->setWikiEditRole('invalid');
    }

    public function testIsTrashEnabled(): void {
        $this->assertFalse($this->site()->isTrashEnabled());
    }

    public function testSetTrashEnabled(): void {
        $site = $this->site();

        $site->setTrashEnabled(true);

        $this->assertTrue($site->isTrashEnabled());
    }

    public function testIsRegistrationCaptchaEnabled(): void {
        $this->assertFalse($this->site()->isRegistrationCaptchaEnabled());
    }

    public function testSetRegistrationCaptchaEnabled(): void {
        $site = $this->site();

        $site->setRegistrationCaptchaEnabled(true);

        $this->assertTrue($site->isRegistrationCaptchaEnabled());
    }

    public function testIsUrlImagesEnabled(): void {
        $this->assertTrue($this->site()->isUrlImagesEnabled());
    }

    public function testSetUrlImagesEnabled(): void {
        $site = $this->site();

        $site->setUrlImagesEnabled(false);

        $this->assertFalse($site->isUrlImagesEnabled());
    }

    public function testGetSubmissionLinkDestination(): void {
        $this->assertSame(
            SubmissionLinkDestination::URL,
            $this->site()->getSubmissionLinkDestination(),
        );
    }

    public function testSetSubmissionLinkDestination(): void {
        $site = $this->site();

        $site->setSubmissionLinkDestination(SubmissionLinkDestination::SUBMISSION);

        $this->assertSame(
            SubmissionLinkDestination::SUBMISSION,
            $site->getSubmissionLinkDestination()
        );
    }

    public function testThrowsOnInvalidSubmissionLinkDestination(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->site()->setSubmissionLinkDestination('invalid');
    }

    public function provideUserRoles(): \Generator {
        foreach (User::ROLES as $role) {
            yield $role => [$role];
        }
    }
}
