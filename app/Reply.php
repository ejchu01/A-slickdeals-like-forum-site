<?php

namespace App;

use App\Utils\Votable;
use Illuminate\Database\Eloquent\Model;
use App\Utils\RecordActivity;

class Reply extends Model
{
    use RecordActivity;

    use Votable;

    CONST MENTIONED_USER_NAME_PATTERN = '/(?<=^|(?<=[^a-zA-Z0-9-_\.]))@([A-Za-z]+[A-Za-z0-9-_]+)/';

    protected $guarded = [];

    protected $with = ['owner', 'votes', 'thread'];

    protected $appends = ['upVotesCount', 'downVotesCount', 'currentVote'];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($reply) {
            $reply->votes->each->delete();
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class, 'thread_id');
    }

    public function path()
    {
        return $this->thread->path() . "#reply-{$this->id}";
    }

    public function wasJustCreated()
    {
        return $this->created_at->gt(now()->subMinute());
    }

    public function mentionedUsers()
    {
        preg_match_all(self::MENTIONED_USER_NAME_PATTERN, $this->body, $matches);

        return $matches[1];
    }

    public function setBodyAttribute($body)
    {
        $this->attributes['body'] = preg_replace(self::MENTIONED_USER_NAME_PATTERN, '<a href="/profiles/$1">$0</a>', $body);
    }
}
