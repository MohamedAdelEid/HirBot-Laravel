<?php

namespace App\Modules\SocialMedia\Tests\Unit\Entities;

use App\Modules\SocialMedia\Domain\Entities\PostMedia;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostMediaTest extends TestCase
{
    #[Test]
    public function it_can_create_a_post_media_entity()
    {
        $postId = 1;
        $type = 'image';
        $file = UploadedFile::fake()->image('test.jpg');
        $mediaUrl = 'path/to/media.jpg';
        $posterUrl = 'path/to/poster.jpg';

        $postMedia = new PostMedia($postId, $type, $file, $mediaUrl, $posterUrl);

        $this->assertEquals($postId, $postMedia->getPostId());
        $this->assertEquals($type, $postMedia->getType());
        $this->assertEquals($file, $postMedia->getFile());
        $this->assertEquals($mediaUrl, $postMedia->getMediaUrl());
        $this->assertEquals($posterUrl, $postMedia->getPosterUrl());
    }

    #[Test]
    public function it_can_set_media_url()
    {
        $postId = 1;
        $type = 'image';
        $file = UploadedFile::fake()->image('test.jpg');

        $postMedia = new PostMedia($postId, $type, $file);
        $this->assertNull($postMedia->getMediaUrl());

        $mediaUrl = 'path/to/media.jpg';
        $postMedia->setMediaUrl($mediaUrl);
        $this->assertEquals($mediaUrl, $postMedia->getMediaUrl());
    }

    #[Test]
    public function it_can_set_poster_url()
    {
        $postId = 1;
        $type = 'video';
        $file = UploadedFile::fake()->image('test.mp4');

        $postMedia = new PostMedia($postId, $type, $file);
        $this->assertNull($postMedia->getPosterUrl());

        $posterUrl = 'path/to/poster.jpg';
        $postMedia->setPosterUrl($posterUrl);
        $this->assertEquals($posterUrl, $postMedia->getPosterUrl());
    }

    #[Test]
    public function it_can_convert_to_array()
    {
        $postId = 1;
        $type = 'image';
        $file = UploadedFile::fake()->image('test.jpg');
        $mediaUrl = 'path/to/media.jpg';
        $posterUrl = 'path/to/poster.jpg';

        $postMedia = new PostMedia($postId, $type, $file, $mediaUrl, $posterUrl);
        $array = $postMedia->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($postId, $array['post_id']);
        $this->assertEquals($type, $array['type']);
        $this->assertEquals($mediaUrl, $array['media_url']);
        $this->assertEquals($posterUrl, $array['poster_url']);
    }
}
