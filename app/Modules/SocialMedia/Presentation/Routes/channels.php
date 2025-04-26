<?php

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Allow authenticated users to listen to post channels
Broadcast::channel('post.{postId}', function ($user, $postId) {
    // Check if post exists
    $post = PostModel::find($postId);
    if (!$post) {
        return false;
    }

    // Allow access to the post owner
    if ($post->user_id === $user->Id) {
        return true;
    }

    // Allow access based on post visibility
    // You can add more complex logic here based on your requirements
    return true;
});

// Allow authenticated users to listen to comment channels
Broadcast::channel('comment.{commentId}', function ($user, $commentId) {
    // Check if comment exists
    $comment = CommentModel::find($commentId);
    if (!$comment) {
        return false;
    }

    // Allow access to the comment owner
    if ($comment->user_id === $user->Id) {
        return true;
    }

    // Allow access to the post owner
    $post = PostModel::find($comment->post_id);
    if ($post && $post->user_id === $user->Id) {
        return true;
    }

    // Allow access to other users
    // You can add more complex logic here based on your requirements
    return true;
});

// Allow authenticated users to listen to poll channels
Broadcast::channel('poll.{pollId}', function ($user, $pollId) {
    // Check if poll exists
    $poll = PollModel::find($pollId);
    if (!$poll) {
        return false;
    }

    // Allow access to the post owner
    $post = PostModel::find($poll->post_id);
    if ($post && $post->user_id === $user->Id) {
        return true;
    }

    // Allow access to other users
    // You can add more complex logic here based on your requirements
    return true;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    // Users can only listen to their own channels
    return $user->Id === $userId;
});

// Allow authenticated users to listen to connection request channels
Broadcast::channel('connection.{receiverId}', function ($user, $receiverId) {
    // Check if the user is the receiver of the connection request
    if ($user->Id === $receiverId) {
        return true;
    }

    // You can add more checks based on your requirements, such as:
    // - Allowing the requester to listen to their own connection requests.
    // - Allowing both users to listen to the connection channel if needed.

    return false;
});
