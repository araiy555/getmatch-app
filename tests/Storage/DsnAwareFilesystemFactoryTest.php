<?php

namespace App\Tests\Storage;

use App\Storage\DsnAwareFilesystemFactory;
use Aws\Credentials\CredentialsInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Storage\DsnAwareFilesystemFactory
 */
class DsnAwareFilesystemFactoryTest extends TestCase {
    public function testCreateLocalFilesystem(): void {
        $filesystem = DsnAwareFilesystemFactory::createFilesystem('file:///tmp');

        $this->assertInstanceOf(Local::class, $filesystem->getAdapter());
    }

    public function testCreateNullFilesystem(): void {
        $filesystem = DsnAwareFilesystemFactory::createFilesystem('null://');

        $this->assertInstanceOf(NullAdapter::class, $filesystem->getAdapter());
    }

    public function testCreateS3Filesystem(): void {
        $filesystem = DsnAwareFilesystemFactory::createFilesystem('s3://your-key:your-secret@your-region/bucket-name');

        /** @var AwsS3Adapter $adapter */
        $adapter = $filesystem->getAdapter();

        $this->assertInstanceOf(AwsS3Adapter::class, $adapter);
        $this->assertSame('bucket-name', $adapter->getBucket());
        $this->assertSame('your-region', $adapter->getClient()->getRegion());
    }

    /**
     * @see https://gitlab.com/postmill/Postmill/-/issues/64
     */
    public function testCreateS3FilesystemWithEncodedSecrets(): void {
        $filesystem = DsnAwareFilesystemFactory::createFilesystem('s3://%2f:%2b@foo/bar');

        /** @var AwsS3Adapter $adapter */
        $adapter = $filesystem->getAdapter();
        /** @var CredentialsInterface $credentials */
        $credentials = $adapter->getClient()->getCredentials()->wait();

        $this->assertSame('/', $credentials->getAccessKeyId());
        $this->assertSame('+', $credentials->getSecretKey());
    }

    public function testThrowsOnUnrecognizedAdapter(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown filesystem 'poop'");

        DsnAwareFilesystemFactory::createFilesystem('poop://crap');
    }
}
