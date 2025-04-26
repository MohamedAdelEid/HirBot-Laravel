<?php

namespace App\Modules\SocialMedia\Tests\Unit\Entities;

use App\Modules\SocialMedia\Domain\Entities\Post;
use App\Modules\SocialMedia\Domain\Enums\Post\PrivacyCommentsEnum;
use App\Modules\SocialMedia\Domain\Enums\Post\PostVisibilityEnum;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;


class PostTest extends TestCase
{
    #[Test]
    public function it_can_create_a_post_entity()
    {
        $userId = 'user123';
        $content = 'Test post content';
        $privacyComments = PrivacyCommentsEnum::PUBLIC;
        $visibility = PostVisibilityEnum::PUBLIC;

        $post = new Post($userId, $content, $privacyComments, $visibility);

        $this->assertEquals($userId, $post->getUserId());
        $this->assertEquals($content, $post->getContent());
        $this->assertEquals($privacyComments, $post->getPrivacyComments());
        $this->assertEquals($visibility, $post->getVisibility());
    }

    #[Test]
    public function it_can_convert_to_array()
    {
        $userId = 'user123';
        $content = 'Test post content';
        $privacyComments = PrivacyCommentsEnum::PUBLIC;
        $visibility = PostVisibilityEnum::PUBLIC;

        $post = new Post($userId, $content, $privacyComments, $visibility);
        $array = $post->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals($content, $array['content']);
        $this->assertEquals($privacyComments->value, $array['privacy_comments']);
        $this->assertEquals($visibility->value, $array['visibility']);
    }
}
