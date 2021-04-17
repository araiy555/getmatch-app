<?php

namespace App\Tests\Form\Extension;

use App\Form\Extension\UserTimezoneExtension;
use App\Security\Authentication;
use App\Tests\Fixtures\Factory\EntityFactory;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Extension\UserTimezoneExtension
 */
class UserTimezoneExtensionTest extends TypeTestCase {
    /**
     * @var Authentication|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authentication;

    protected function setUp(): void {
        $this->authentication = $this->createMock(Authentication::class);

        parent::setUp();
    }

    protected function getTypeExtensions(): array {
        return [
            new UserTimezoneExtension($this->authentication),
        ];
    }

    /**
     * @dataProvider provideExtendedFormTypes
     */
    public function testSetsOptionForAuthenticatedUser(string $formType): void {
        $this->setLoggedIn();

        $form = $this->factory->create($formType);

        $this->assertSame(
            'Europe/Oslo',
            $form->getConfig()->getOption('view_timezone')
        );
    }

    /**
     * @dataProvider provideExtendedFormTypes
     */
    public function testDoesNotOverridePreSetOption(string $type): void {
        $this->setLoggedIn();

        $form = $this->factory->create($type, null, [
            'view_timezone' => 'Europe/Moscow',
        ]);

        $this->assertSame(
            'Europe/Moscow',
            $form->getConfig()->getOption('view_timezone')
        );
    }

    /**
     * @dataProvider provideExtendedFormTypes
     */
    public function testDoesNotSetOptionWhenNotAuthenticated(string $type): void {
        $this->authentication
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn(null);

        $form = $this->factory->create($type);

        $this->assertNull($form->getConfig()->getOption('view_timezone'));
    }

    public function provideExtendedFormTypes(): \Generator {
        yield [DateTimeType::class];
        yield [DateType::class];
    }

    private function setLoggedIn(): void {
        $this->authentication
            ->method('getUser')
            ->willReturnCallback(function () {
                $user = EntityFactory::makeUser();
                $user->setTimezone(new \DateTimeZone('Europe/Oslo'));

                return $user;
            });
    }
}
