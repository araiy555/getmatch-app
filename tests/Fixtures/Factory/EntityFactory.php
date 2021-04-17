<?php

namespace App\Tests\Fixtures\Factory;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;

class EntityFactory {
    public static function makeUser(): User {
        return new User('u', 'p');
    }

    public static function makeForum(): Forum {
        return new Forum('a', 'a', 'a', 'a');
    }

    public static function makeSubmission(
        Forum $forum = null,
        User $user = null,
        string $ip = null
    ): Submission {
        return new Submission(
            'a',
            null,
            null,
            $forum ?? self::makeForum(),
            $user ?? self::makeUser(),
            $ip
        );
    }

    /**
     * @param Submission|Comment|null $parent
     *
     * @return Comment
     */
    public static function makeComment(
        User $user = null,
        $parent = null,
        string $ip = null
    ): Comment {
        return new Comment(
            'a',
            $user ?? self::makeUser(),
            $parent ?? self::makeSubmission(),
            $ip
        );
    }
}
