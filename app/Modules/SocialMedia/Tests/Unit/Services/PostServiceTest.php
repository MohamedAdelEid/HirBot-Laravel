<?php

namespace App\Modules\SocialMedia\Tests\Unit\Services;

use App\Modules\SocialMedia\Application\DTOs\Post\CreatePostDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\MediaDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\PollDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\PollOptionDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\UpdatePostDTO;
use App\Modules\SocialMedia\Application\Services\PostService;
use App\Modules\SocialMedia\Domain\Enums\Post\PrivacyCommentsEnum;
use App\Modules\SocialMedia\Domain\Enums\Post\PostVisibilityEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollOptionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostMediaModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Shared\Facades\FileUploader;
use App\Shared\Facades\Video;
use App\Shared\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class PostServiceTest extends TestCase
{

    use RefreshDatabase;

    protected $postService;
    protected $repository;
    protected $userId = 'user123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(BaseRepository::class);

        $this->postService = new PostService($this->repository);

        FileUploader::shouldReceive('upload')
            ->andReturn('path/to/uploaded/file.jpg')
            ->byDefault();

        FileUploader::shouldReceive('delete')
            ->andReturn(true)
            ->byDefault();

        Video::shouldReceive('extractPoster')
            ->andReturn('path/to/poster.jpg')
            ->byDefault();

        Video::shouldReceive('extractPosterFallback')
            ->andReturn('path/to/fallback/poster.jpg')
            ->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_all_posts_with_pagination()
    {
        $user = User::factory()->create();

        PostModel::factory()->count(5)->create([
            'user_id' => $user->Id,
        ]);

        $result = $this->postService->getAllPosts();

        $this->assertEquals(5, $result->total());
    }

    #[Test]
    public function it_can_create_a_post_with_content_only()
    {
        // Create a mock post model
        $mockPostModel = Mockery::mock(PostModel::class)->makePartial();

        $mockPostModel->id = 1;
        $mockPostModel->user_id = $this->userId;
        $mockPostModel->content = 'Test post content';
        $mockPostModel->visibility = PostVisibilityEnum::PUBLIC->value;
        $mockPostModel->privacy_comments = PrivacyCommentsEnum::PUBLIC->value;

        $mockPostModel->shouldReceive('fresh')
        ->once()
        ->andReturnSelf();

        $mockPostModel->shouldReceive('load')
        ->once()
        ->with(['media', 'poll.options'])
        ->andReturnSelf();

        $mockPostModel->shouldReceive('toArray')
            ->once()
            ->andReturn([
                'id' => 1,
                'user_id' => $this->userId,
                'content' => 'Test post content',
                'visibility' => PostVisibilityEnum::PUBLIC->value,
                'privacy_comments' => PrivacyCommentsEnum::PUBLIC->value,
                'media' => [],
                'poll' => null,
            ]);

        // Set up the repository mock expectations
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PostModel::class));

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($mockPostModel);

        // Create the DTO
        $dto = new CreatePostDTO(
            $this->userId,
            'Test post content',
            PrivacyCommentsEnum::PUBLIC,
            PostVisibilityEnum::PUBLIC,
            null,
            null
        );

        // Call the method
        $result = $this->postService->createPost($dto);

        // Assert the result
        $this->assertEquals($this->userId, $result['user_id']);
        $this->assertEquals('Test post content', $result['content']);
        $this->assertEquals(PostVisibilityEnum::PUBLIC->value, $result['visibility']);
        $this->assertEquals(PrivacyCommentsEnum::PUBLIC->value, $result['privacy_comments']);
    }

    #[Test]
    public function it_can_create_a_post_with_media()
    {
        // Create a mocked PostModel with ID set
        $postModel = Mockery::mock(PostModel::class)->makePartial();
        $postModel->id = 1;
        $postModel->user_id = $this->userId;
        $postModel->content = 'Test post with media';
        $postModel->visibility = PostVisibilityEnum::PUBLIC->value;
        $postModel->privacy_comments = PrivacyCommentsEnum::PUBLIC->value;

        $postModel->shouldReceive('fresh')->once()->andReturnSelf();
        $postModel->shouldReceive('load')->once()->andReturnSelf();
        $postModel->shouldReceive('toArray')->once()->andReturn([
            'id' => 1,
            'user_id' => $this->userId,
            'content' => 'Test post with media',
            'visibility' => PostVisibilityEnum::PUBLIC->value,
            'privacy_comments' => PrivacyCommentsEnum::PUBLIC->value,
            'media' => [
                [
                    'id' => 1,
                    'post_id' => 1,
                    'type' => 'image',
                    'media_url' => 'path/to/uploaded/file.jpg',
                    'poster_url' => null,
                ]
            ],
            'poll' => null,
        ]);

        // Set up repository expectations for creating post
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PostModel::class));

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($postModel);

        // Expect setModel to be called again for media
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PostMediaModel::class));

        // Expect media to be created
        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn(new PostMediaModel([
                'id' => 1,
                'post_id' => 1,
                'type' => 'image',
                'media_url' => 'path/to/uploaded/file.jpg',
            ]));

        // Fake file
        $file = UploadedFile::fake()->image('test-image.jpg');

        // Media DTO
        $mediaDTO = new MediaDTO('image', $file);

        // CreatePostDTO with media
        $dto = new CreatePostDTO(
            $this->userId,
            'Test post with media',
            PrivacyCommentsEnum::PUBLIC,
            PostVisibilityEnum::PUBLIC,
            [$mediaDTO],
            null
        );

        // Call service
        $result = $this->postService->createPost($dto);

        // Assertions
        $this->assertEquals($this->userId, $result['user_id']);
        $this->assertEquals('Test post with media', $result['content']);
        $this->assertEquals(PrivacyCommentsEnum::PUBLIC->value, $result['privacy_comments']);
        $this->assertCount(1, $result['media']);
        $this->assertEquals('image', $result['media'][0]['type']);
        $this->assertEquals('path/to/uploaded/file.jpg', $result['media'][0]['media_url']);
    }

    #[Test]
    public function it_can_create_a_post_with_poll()
    {
        // Create a mocked PostModel
        $postModel = Mockery::mock(PostModel::class)->makePartial();
        $postModel->id = 1;
        $postModel->user_id = $this->userId;
        $postModel->content = 'Test post with poll';
        $postModel->visibility = PostVisibilityEnum::PUBLIC->value;
        $postModel->privacy_comments = PrivacyCommentsEnum::PUBLIC->value;

        // Mock fresh, load and toArray methods
        $postModel->shouldReceive('fresh')
            ->once()
            ->andReturn($postModel);

        $postModel->shouldReceive('load')
            ->once()
            ->with(['media', 'poll.options'])
            ->andReturn($postModel);

        $postModel->shouldReceive('toArray')
            ->once()
            ->andReturn([
                'user_id' => $this->userId,
                'content' => 'Test post with poll',
                'poll' => [
                    'question' => 'Test poll question',
                    'options' => [
                        ['content' => 'Option 1', 'vote_count' => 0],
                        ['content' => 'Option 2', 'vote_count' => 0],
                    ],
                ],
            ]);

        // Create a mocked PollModel
        $pollModel = Mockery::mock(PollModel::class)->makePartial();
        $pollModel->id = 1;
        $pollModel->post_id = 1;
        $pollModel->question = 'Test poll question';

        // Set up the repository mock expectations for post creation
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PostModel::class));

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($postModel);

        // Set up the repository mock expectations for poll creation
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PollModel::class));

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($pollModel);

        // Set up the poll options relationship
        $pollModel->shouldReceive('options->create')
            ->twice()
            ->andReturn(new PollOptionModel());

        // Create the poll option DTOs
        $pollOptionDTO1 = new PollOptionDTO('Option 1', 0);
        $pollOptionDTO2 = new PollOptionDTO('Option 2', 0);

        // Create the poll DTO
        $pollDTO = new PollDTO('Test poll question', [$pollOptionDTO1, $pollOptionDTO2]);

        // Create the post DTO with poll
        $dto = new CreatePostDTO(
            $this->userId,
            'Test post with poll',
            PrivacyCommentsEnum::PUBLIC,
            PostVisibilityEnum::PUBLIC,
            null,
            [$pollDTO]
        );

        // Call the method
        $result = $this->postService->createPost($dto);

        // Assert the result
        $this->assertEquals($this->userId, $result['user_id']);
        $this->assertEquals('Test post with poll', $result['content']);
        $this->assertEquals('Test poll question', $result['poll']['question']);
        $this->assertCount(2, $result['poll']['options']);

        $this->assertEquals('Option 1', $result['poll']['options'][0]['content']);
        $this->assertEquals(0, $result['poll']['options'][0]['vote_count']);

        $this->assertEquals('Option 2', $result['poll']['options'][1]['content']);
        $this->assertEquals(0, $result['poll']['options'][1]['vote_count']);
    }

    #[Test]
    public function it_can_get_a_post_by_id()
    {
        // Create a mock user
        $user = User::factory()->create();

        // Create a test post
        $post = PostModel::factory()->create([
            'user_id' => $user->Id,
            'content' => 'Test post content',
        ]);

        // Set up the repository mock expectations
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PostModel::class));

        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with($post->id)
            ->andReturn($post);

        // Call the method
        $result = $this->postService->getPost($post->id);

        // Assert the result
        $this->assertEquals($post->id, $result->id);
        $this->assertEquals($user->Id, $result->user_id);
        $this->assertEquals('Test post content', $result->content);
    }

    #[Test]
    public function it_can_update_a_post()
    {
        // Create a mock user
        $user = User::factory()->create();

        // Create a test post
        $post = PostModel::factory()->create([
            'user_id' => $user->Id,
            'content' => 'Original content',
        ]);

        // Set up the repository mock expectations
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PostModel::class));

        $this->repository->shouldReceive('update')
            ->once()
            ->with($post->id, ['content' => 'Updated content'])
            ->andReturn($post);

        // Create the DTO
        $dto = new UpdatePostDTO(
            $user->Id,
            'Updated content',
            null,
            null,
            null,
            null,
            null
        );

        // Call the method
        $result = $this->postService->updatePost($post, $dto);

        // Assert the result
        $this->assertEquals($post->id, $result['id']);
        $this->assertEquals($user->Id, $result['user_id']);
    }

    #[Test]
    public function it_can_delete_a_post()
    {
        // Create a mock user
        $user = User::factory()->create();

        // Create a test post
        $post = PostModel::factory()->create([
            'user_id' => $user->Id,
            'content' => 'Test post content',
        ]);

        Auth::shouldReceive('id')->andReturn($user->Id);

        // Set up the repository mock expectations
        $this->repository->shouldReceive('setModel')
            ->once()
            ->with(Mockery::type(PostModel::class));

        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with($post->id)
            ->andReturn($post);

        $this->repository->shouldReceive('delete')
            ->once()
            ->with($post->id)
            ->andReturn(true);

        // Call the method
        $result = $this->postService->deletePost($post->id);

        // Assert the result
        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_force_delete_a_post()
    {
        // Create a mock user
        $user = User::factory()->create();

        // Mock Auth::id()
        Auth::shouldReceive('id')->andReturn($user->Id);

        // Create a post mock
        $post = Mockery::mock(PostModel::class);
        $post->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $post->shouldReceive('getAttribute')->with('user_id')->andReturn($user->Id);

        // Create a media mock
        $media = Mockery::mock(PostMediaModel::class);
        $media->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $media->shouldReceive('getAttribute')->with('media_url')->andReturn('path/to/file.jpg');
        $media->shouldReceive('getAttribute')->with('poster_url')->andReturn(null);

        // Create poll and poll option mock
        $poll = Mockery::mock(PollModel::class);
        $option = Mockery::mock(PollOptionModel::class);
        $option->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $poll->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $poll->shouldReceive('getAttribute')->with('options')->andReturn(collect([$option]));

        // Setup post relationships correctly
        $post->shouldReceive('getAttribute')->with('media')->andReturn(collect([$media]));
        $post->shouldReceive('getAttribute')->with('poll')->andReturn($poll);
        $post->shouldReceive('forceDelete')->once()->andReturn(true);

        // Setup repository expectations
        $this->repository->shouldReceive('setModel')
            ->times(5) // Update to 5 times because of the 5 calls to setModel
            ->withArgs(function ($model) {
                return $model instanceof PostModel ||
                       $model instanceof PostMediaModel ||
                       $model instanceof PollModel ||
                       $model instanceof PollOptionModel;
            });

        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($post);

        $this->repository->shouldReceive('delete')
            ->times(3)
            ->andReturn(true);

        // Call the method
        $result = $this->postService->forceDeletePost(1);

        // Assert the result
        $this->assertTrue($result);
    }
}
