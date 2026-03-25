<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\BotInstance;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $instanceNames = BotInstance::forPanelUser()->pluck('instance_name');
        $instances = BotInstance::forPanelUser()->orderBy('instance_name')->get();

        $instanceFilter = $request->query('instance');
        if ($instanceFilter !== null && $instanceFilter !== '' && ! $instanceNames->contains($instanceFilter)) {
            $instanceFilter = null;
        } elseif ($instanceFilter === '') {
            $instanceFilter = null;
        }

        $query = Conversation::with('latestMessage')
            ->whereIn('instance_name', $instanceNames)
            ->where('is_archived', false)
            ->when($instanceFilter, fn ($q) => $q->where('instance_name', $instanceFilter));

        if ($instanceFilter) {
            $query->orderBy('last_message_at', 'desc');
        } else {
            $query->orderBy('instance_name')->orderBy('last_message_at', 'desc');
        }

        $conversations = $query->paginate(20)->withQueryString();

        return view('chat.index', compact('conversations', 'instances', 'instanceFilter'));
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

