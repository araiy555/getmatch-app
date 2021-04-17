<?php

namespace App\DataTransfer;

use App\DataObject\ForumData;
use App\Entity\Forum;
use App\Entity\ForumTag;
use App\Entity\User;
use App\Repository\ForumTagRepository;
use Doctrine\ORM\EntityManagerInterface;

class ForumManager {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ForumTagRepository
     */
    private $forumTagRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ForumTagRepository $forumTagRepository
    ) {
        $this->entityManager = $entityManager;
        $this->forumTagRepository = $forumTagRepository;
    }

    public function createForum(ForumData $data, User $user = null): Forum {
        $forum = new Forum(
            $data->getName(),
            $data->getTitle(),
            $data->getDescription(),
            $data->getSidebar(),
            $user
        );

        $forum->setFeatured($data->isFeatured());
        $forum->setLightBackgroundImage($data->getLightBackgroundImage());
        $forum->setDarkBackgroundImage($data->getDarkBackgroundImage());
        $forum->setBackgroundImageMode($data->getBackgroundImageMode());
        $forum->setSuggestedTheme($data->getSuggestedTheme());

        $this->updateTags($forum, $data);

        $this->entityManager->persist($forum);

        return $forum;
    }

    public function updateForum(Forum $forum, ForumData $data): void {
        $forum->setName($data->getName());
        $forum->setTitle($data->getTitle());
        $forum->setSidebar($data->getSidebar());
        $forum->setDescription($data->getDescription());
        $forum->setFeatured($data->isFeatured());
        $forum->setLightBackgroundImage($data->getLightBackgroundImage());
        $forum->setDarkBackgroundImage($data->getDarkBackgroundImage());
        $forum->setBackgroundImageMode($data->getBackgroundImageMode());
        $forum->setSuggestedTheme($data->getSuggestedTheme());
        $this->updateTags($forum, $data);
    }

    private function updateTags(Forum $forum, ForumData $data): void {
        $tagNames = [];
        foreach ($data->getTags() as $tagData) {
            $tagNames[$tagData->getNormalizedName()] = $tagData->getName();
        }

        $forumTags = [];
        foreach ($forum->getTags() as $tag) {
            $forumTags[$tag->getNormalizedName()] = $tag;
        }

        foreach (array_diff_key($forumTags, $tagNames) as $tag) {
            \assert($tag instanceof ForumTag);

            $forum->removeTags($tag);

            if ($tag->getForumCount() === 0) {
                $this->entityManager->remove($tag);
            }
        }

        $storedTags = [];
        foreach ($this->forumTagRepository->findByNormalizedName(array_keys($tagNames)) as $tag) {
            $storedTags[$tag->getNormalizedName()] = $tag;
        }

        foreach (array_diff_key($tagNames, $forumTags) as $key => $tagName) {
            $forum->addTags($storedTags[$key] ?? new ForumTag($tagName));
        }
    }
}
