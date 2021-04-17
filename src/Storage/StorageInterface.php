<?php

namespace App\Storage;

interface StorageInterface {
    public function store(string $localPath, string $remotePath): void;

    public function prune(string $remotePath): void;
}
