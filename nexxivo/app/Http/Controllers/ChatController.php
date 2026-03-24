<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\BotInstance;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $instanceNames = BotInstance::forPanelUser()->pluck('instance_name');
        $conversations = Conversation::with('latestMessage')
            ->whereIn('instance_name', $instanceNames)
            ->where('is_archived', false)
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        $instances = BotInstance::forPanelUser()->get();

        return view('chat.index', compact('conversations', 'instances'));
    }

    public function show($id)
    {
        $instanceNames = BotInstance::forPanelUser()->pluck('instance_name');
        $conversation = Conversation::with(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->whereIn('instance_name', $instanceNames)->findOrFail($id);

        $instance = BotInstance::forPanelUser()
            ->where('instance_name', $conversation->instance_name)
            ->first();

        return view('chat.show', compact('conversation', 'instance'));
    }
}

