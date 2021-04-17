<?php

namespace App\Tests\Validator;

use App\Validator\IpWithCidr;
use App\Validator\IpWithCidrValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\IpWithCidrValidator
 */
class IpWithCidrValidatorTest extends ConstraintValidatorTestCase {
    protected function createValidator(): ConstraintValidatorInterface {
        return new IpWithCidrValidator();
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testValidValues(string $value): void {
        $this->validator->validate($value, new IpWithCidr());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider cidrLessProvider
     */
    public function testRaisesErrorWithoutCidr(string $value): void {
        $constraint = new IpWithCidr([
            'cidrOptional' => false,
            'missingCidrMessage' => 'missingCidr',
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('missingCidr')
            ->setCode(IpWithCidr::MISSING_CIDR)
            ->assertRaised();
    }

    /**
     * @dataProvider invalidIpProvider
     */
    public function testRaisesErrorOnInvalidIp(string $value): void {
        $constraint = new IpWithCidr([
            'invalidIpMessage' => 'invalidIp',
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('invalidIp')
            ->setCode(IpWithCidr::INVALID_IP)
            ->assertRaised();
    }

    /**
     * @dataProvider invalidCidrProvider
     */
    public function testRaisesErrorOnInvalidCidr(string $value): void {
        $constraint = new IpWithCidr([
            'invalidCidrMessage' => 'invalidCidr',
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('invalidCidr')
            ->setCode(IpWithCidr::INVALID_CIDR)
            ->assertRaised();
    }

    public function validValuesProvider(): iterable {
        yield ['127.0.0.1/32'];
        yield ['254.253.252.251/31'];
        yield ['192.168.4.20'];
        yield ['1312::1917/24'];
        yield ['420::69/15'];
        yield ['4:3:2::1'];
    }

    public function cidrLessProvider(): iterable {
        yield ['::1'];
        yield ['192.168.4.20'];
    }

    public function invalidIpProvider(): iterable {
        yield ['256.256.256.256/32'];
        yield ['goop::crap'];
    }

    public function invalidCidrProvider(): iterable {
        yield ['::1/129'];
        yield ['::/-128'];
        yield ['127.6.5.4/33'];
        yield ['127.0.0.1/'.PHP_INT_MAX.PHP_INT_MAX];
        yield ['127.0.0.1/crap'];
    }
}
