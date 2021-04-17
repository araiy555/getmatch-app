<?php

namespace App\Storage;

use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;

final class FlysystemStorage implements StorageInterface {
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem) {
        $this->filesystem = $filesystem;
    }

    /**
     * Store file using Flysystem instance.
     *
     * @throws \RuntimeException if file couldn't be stored
     */
    public function store(string $localPath, string $remotePath): void {
        $fh = fopen($localPath, 'rb');

        try {
            $success = $this->filesystem->writeStream($remotePath, $fh);

            if (!$success) {
                throw new \RuntimeException("Couldn't store file");
            }
        } catch (FileExistsException $e) {
            // do nothing
        } finally {
            \is_resource($fh) and fclose($fh);
        }
    }

    public function prune(string $remotePath): void {
        try {
            $this->filesystem->delete($remotePath);
        } catch (FileNotFoundException $e) {
            // do nothing
        }
    }
}
