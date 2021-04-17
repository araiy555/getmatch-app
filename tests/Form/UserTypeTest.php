<?php

namespace App\Tests\Form;

use App\DataObject\UserData;
use App\Entity\Site;
use App\Form\Type\HoneypotType;
use App\Form\UserType;
use App\Repository\SiteRepository;
use Gregwar\CaptchaBundle\Generator\CaptchaGenerator;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Form\UserType
 */
class UserTypeTest extends TypeTestCase {
    /**
     * @var SiteRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $siteRepository;

    protected function setUp(): void {
        $this->siteRepository = $this->createMock(SiteRepository::class);

        parent::setUp();
    }

    /**
     * @dataProvider provideCaptchaEnabled
     */
    public function testCaptchaToggle(bool $captchaEnabled): void {
        $site = new Site();
        $site->setRegistrationCaptchaEnabled($captchaEnabled);

        $this->siteRepository
            ->expects($this->atLeastOnce())
            ->method('findCurrentSite')
            ->willReturn($site);

        $form = $this->factory->create(UserType::class, null);

        $this->assertSame($captchaEnabled, $form->has('verification'));
    }

    public function testCaptchaNotToggledWhenEditing(): void {
        $site = new Site();
        $site->setRegistrationCaptchaEnabled(true);

        $this->siteRepository
            ->method('findCurrentSite')
            ->willReturn($site);

        $data = new UserData();
        $data->setId(420);

        $form = $this->factory->create(UserType::class, $data);

        $this->assertFalse($form->has('verification'));
    }

    protected function getExtensions(): array {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]));

        return [
            new PreloadedExtension([
                new HoneypotType($requestStack),
                new UserType(
                    $this->createMock(UserPasswordEncoderInterface::class),
                    $this->siteRepository
                ),
                new CaptchaType(
                    new Session(new MockArraySessionStorage()),
                    $this->createMock(CaptchaGenerator::class),
                    $this->createMock(TranslatorInterface::class),
                    [
                        'as_url' => null,
                        'bypass_code' => 'bypass',
                        'humanity' => 1,
                        'reload' => null,
                        'invalid_message' => 'whatever',
                        'session_key' => 'whatever',
                    ]
                ),
            ], []),
        ];
    }

    public function provideCaptchaEnabled(): \Generator {
        yield [false];
        yield [true];
    }
}
