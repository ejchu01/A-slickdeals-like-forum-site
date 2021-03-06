<?php

namespace App\Http\Controllers;

use \Exception;
use App\Thread;
use App\Rules\Recaptcha;
use App\Channel;
use App\Trending;
use App\Filters\ThreadFilter;
use Illuminate\Http\Request;
use Illuminate\Foundation\Console\Presets\React;

class ThreadController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Channel $channel, Request $request, Trending $thrending)
    {
        $threads = $this->getThreads($channel, new ThreadFilter($request));

        if ($request->expectsJson()) {
            return $threads;
        }

        return view('threads.index', [
            'threads' => $threads,
            'channel' => $channel->exists ? $channel : null,
            'trending' => $thrending->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (\Gate::denies('create', new Thread)) {
            return redirect(route('threads'))
                ->with('flash', [
                    'message' => 'Your email address is not confirmed',
                    'type' => 'warning',
                ]);
        }

        return view('threads.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Recaptcha $recaptcha)
    {
        $request->validate([
            'title' => 'required|spamfree',
            'body' => 'required|spamfree',
            'channel_id' => 'required|exists:channels,id',
            'g-recaptcha-response' => ['required', $recaptcha],
        ]);

        $thread = Thread::create([
            'user_id' => auth()->id(),
            'channel_id' => request('channel_id'),
            'title' => request('title'),
            'body' => request('body'),
            'slug' => str_slug(request('title')),
        ]);

        if ($request->expectsJson()) {
            return response($thread, 201);
        }

        return redirect($thread->path())
            ->with('flash', [
                'message' => 'Your deal has been posted!',
            ]);
    }

    /**
     * Display the specified resource.
     * @param int $channelId
     * @param  \App\Thread  $thread
     * @param  \App\Trending $trending
     * @return \Illuminate\Http\Response
     */
    public function show(Channel $channel, Thread $thread, Trending $trending)
    {
        $trending->push($thread);

        $thread->visits()->record();

        return view('threads.show', compact('thread'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function edit(Thread $thread)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Channel $channel
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function update(Channel $channel, Thread $thread)
    {

        $this->authorize('update', $thread);

        $data = request()->validate([
            'title' => 'required|spamfree',
            'body' => 'required|spamfree',
        ]);

        $thread->update($data);

        return $thread;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Channel $channel
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function destroy(Channel $channel, Thread $thread)
    {
        $this->authorize('delete', $thread);

        $thread->delete();
        return redirect('/threads');
    }


    /**
     * Perform lock operations on threads
     *
     * @param $channel
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function lock($channel, Thread $thread)
    {
        if (request()->has('lock')) {
            if (request('lock') == true) {
                $thread->lock();
            } else {
                $thread->unlock();
            }
        }
    }

    public function getThreads(Channel $channel, ThreadFilter $filter)
    {
        $threads = Thread::filterWith($filter);
        if ($channel->exists) {
            $threads = $threads->where('channel_id', $channel->id);
        }

        return $threads->latest()->paginate(15);
    }
}
