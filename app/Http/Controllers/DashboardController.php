<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'contacts' => Contact::count(),
            'conversations' => Conversation::count(),
            'messages' => Message::count(),
            'unread' => Conversation::sum('unread_count'),
        ];

        $recentConversations = Conversation::with('contact', 'latestMessage')
            ->orderByDesc('last_message_at')
            ->take(8)
            ->get();

        return view('dashboard', compact('stats', 'recentConversations'));
    }
}
