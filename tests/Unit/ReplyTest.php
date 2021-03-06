<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class ReplyTest extends TestCase
{
    /** @test */
    function a_reply_has_an_owner()
    {
        $reply = create('App\Reply');
        $this->assertInstanceOf('App\User', $reply->owner);
    }

    /** @test */
    public function path_method_works_as_expected()
    {
        $thread = create('App\Thread');
        $reply = create('App\Reply', ['thread_id' => $thread->id]);

        $replyPath = "/threads/{$thread->channel->slug}/{$thread->slug}#reply-{$reply->id}";
        $this->assertEquals($replyPath, $reply->path());
    }

    /** @test */
    public function it_can_tell_if_it_was_just_posted()
    {
        $reply = create('App\Reply');

        $this->assertTrue($reply->wasJustCreated());

        $oldReply = create('App\Reply', ['created_at' => now()->subMinutes(30)]);

        $this->assertFalse($oldReply->wasJustCreated());
    }

    /** @test */
    public function it_can_detect_all_mentioned_users_in_its_body()
    {
        $reply = create('App\Reply', ['body' => 'some text, @user_1 and  @user_2']);

        $this->assertEquals(['user_1', 'user_2'], $reply->mentionedUsers());
    }

    /** @test */
    public function it_wraps_metioned_users_into_anchor_tags()
    {
        $reply = create('App\Reply', ['body' => 'some text, @user_1.']);

        $this->assertEquals(
            'some text, <a href="/profiles/user_1">@user_1</a>.',
            $reply->body
        );
    }

    /** @test */
    public function it_knows_if_it_is_marked_as_best()
    {
        $reply = create('App\Reply');

        $this->assertFalse($reply->isMarked());
        
        $reply->thread->update(['best_reply_id' => $reply->id]);
        
        $this->assertTrue($reply->isMarked('best'));
    }

    /** @test */
    public function it_knows_if_it_is_marked_as_helpful()
    {
        $reply = create('App\Reply');

        $this->assertFalse($reply->isMarked());
        
        $reply->update(['is_helpful' => true]);
        
        $this->assertTrue($reply->isMarked('helpful'));
    }

}
