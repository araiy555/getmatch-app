<?php

namespace App\DataTransfer;

use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Storage\StorageInterface;
use App\Utils\ImageNameGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class ImageManager {
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ImageNameGeneratorInterface
     */
    private $imageNameGenerator;

    /**
     * @var ImageRepository
     */
    private $images;

    /**
     * @var StorageInterface
     */
    private $storage;

    public function __construct(
        CacheManager $cacheManager,
        EntityManagerInterface $entityManager,
        ImageNameGeneratorInterface $imageNameGenerator,
        ImageRepository $repository,
        StorageInterface $imageStorage
    ) {
        $this->cacheManager = $cacheManager;
        $this->entityManager = $entityManager;
        $this->imageNameGenerator = $imageNameGenerator;
        $this->images = $repository;
        $this->storage = $imageStorage;
    }

    public function findOrCreateFromFile(string $path): Image {
        $filename = $this->imageNameGenerator->generateName($path);
        $sha256 = hash_file('sha256', $path, true);
        $image = $this->images->findOneBySha256($sha256);

        if (!$image) {
            [$width, $height] = @getimagesize($path);
            $image = new Image($filename, $sha256, $width, $height);
        } elseif (!$image->getWidth() || !$image->getHeight()) {
            [$width, $height] = @getimagesize($path);
            $image->setDimensions($width, $height);
        }

        $this->storage->store($path, $filename);

        return $image;
    }

    /**
     * @param string[] $fileNames
     */
    public function deleteOrphanedByFileName(array $fileNames): void {
        $images = $this->images->filterOrphaned(
            $this->images->findByFileName($fileNames),
        );

        $this->entityManager->beginTransaction();

        try {
            foreach ($images as $image) {
                $this->entityManager->remove($image);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();

            throw $e;
        }

        foreach ($images as $image) {
            $this->storage->prune($image->getFileName());
            $this->cacheManager->remove($image->getFileName());
        }
    }
}
