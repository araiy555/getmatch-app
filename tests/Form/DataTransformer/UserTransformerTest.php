<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\UserTransformer;
use App\Repository\UserRepository;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @covers \App\Form\DataTransformer\UserTransformer
 */
class UserTransformerTest extends TestCase {
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserRepository
     */
    private $userRepository;

    /**
     * @var UserTransformer
     */
    private $transformer;

    protected function setUp(): void {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->transformer = new UserTransformer($this->userRepository);
    }

    public function testReturnsNullOnTransformOfNullValue(): void {
        $this->assertNull($this->transformer->transform(null));
    }

    public function testReturnsUsernameOnTransformOfUser(): void {
        $user = EntityFactory::makeUser();
        $user->setUsername('emma');
        $this->assertSame('emma', $this->transformer->transform($user));
    }

    public function testThrowsOnBadTransformValue(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->transformer->transform([]);
    }

    public function testReturnsNullOnReverseTransformOfEmptyValues(): void {
        $this->userRepository
            ->expects($this->never())
            ->method('loadUserByUsername');

        $this->assertNull($this->transformer->reverseTransform(null));
        $this->assertNull($this->transformer->reverseTransform(''));
    }

    public function testReturnsUserOnReverseTransformerOfExistentUser(): void {
        $user = EntityFactory::makeUser();

        $this->userRepository
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('emma')
            ->willReturn($user);

        $this->assertSame($user, $this->transformer->reverseTransform('emma'));
    }

    public function testRaisesErrorOnReverseTransformOfNonExistentUser(): void {
        $this->userRepository
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('emma')
            ->willReturn(null);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('No such user');

        $this->transformer->reverseTransform('emma');
    }
}
