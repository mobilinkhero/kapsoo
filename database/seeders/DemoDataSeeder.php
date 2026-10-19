<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Local-only demo data for the inbox UI.
 *
 * Run with: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Each message: [direction, type, body, minutes ago, media url].
     *
     * @var array<int, array{name: string, phone: string, status: string, unread: int, messages: array<int, array{0: string, 1: string, 2: ?string, 3: int, 4?: ?string}>}>
     */
    private array $chats = [
        [
            'name' => 'Ahtisham Ali',
            'phone' => '923256982247',
            'status' => 'active',
            'unread' => 2,
            'messages' => [
                ['inbound', 'text', 'Assalam o Alaikum, is this the Kapso support?', 240],
                ['outbound', 'text', 'Walaikum Assalam! Yes, how can we help you today?', 238],
                ['inbound', 'text', 'I need help setting up the WhatsApp API for my store.', 236],
                ['outbound', 'text', 'Sure. Are you using a Meta Business account already?', 234],
                ['inbound', 'text', 'Yes, I created one last week.', 232],
                ['inbound', 'image', 'Here is the screenshot of my dashboard.', 30, 'https://picsum.photos/seed/kapso-dash/720/420'],
                ['outbound', 'text', 'Perfect, everything looks correctly configured on your side.', 28],
                ['outbound', 'text', 'I have enabled the test number for you. Try sending a message now.', 26],
                ['inbound', 'text', 'It works! Thank you so much 🎉', 4],
                ['inbound', 'text', 'One more thing — can I upload a catalogue?', 2],
            ],
        ],
        [
            'name' => 'Sara Khan',
            'phone' => '923001234567',
            'status' => 'active',
            'unread' => 0,
            'messages' => [
                ['outbound', 'text', 'Hi Sara! Your order #4821 has been dispatched.', 180],
                ['inbound', 'text', 'Great, when will it arrive?', 176],
                ['outbound', 'text', 'It should be delivered tomorrow before 6 PM.', 174],
                ['inbound', 'text', 'Perfect. Can you share the tracking link?', 172],
                ['outbound', 'text', 'Of course: https://track.example.com/4821', 170],
                ['inbound', 'text', 'Thanks!', 168],
                ['outbound', 'text', 'You are welcome 🙂', 60],
            ],
        ],
        [
            'name' => 'Bilal Ahmed',
            'phone' => '923217654321',
            'status' => 'active',
            'unread' => 1,
            'messages' => [
                ['inbound', 'text', 'Bro, did you receive the payment receipt?', 120],
                ['outbound', 'text', 'Not yet, please send it again.', 118],
                ['inbound', 'document', 'Invoice-October.pdf', 115, '/samples/sample.pdf'],
                ['outbound', 'text', 'Got it, thank you. Processing it now.', 113],
                ['inbound', 'text', 'Also here is our warehouse location.', 12],
                ['inbound', 'location', 'Kapso Warehouse, Karachi', 10],
            ],
        ],
        [
            'name' => 'Fatima Noor',
            'phone' => '923459876543',
            'status' => 'open',
            'unread' => 3,
            'messages' => [
                ['inbound', 'text', 'Hello, I want to know your pricing plans.', 90],
                ['outbound', 'text', 'Hi Fatima! Our plans start from $29/month for 1,000 conversations.', 88],
                ['inbound', 'text', 'Do you offer a free trial?', 86],
                ['outbound', 'text', 'Yes, 14 days free. No card required.', 84],
                ['inbound', 'text', 'Can you send me the comparison sheet?', 20],
                ['inbound', 'audio', null, 18, '/samples/sample.wav'],
                ['inbound', 'text', 'Sorry, sent a voice note by mistake 😅', 16],
            ],
        ],
        [
            'name' => 'Usman Tariq',
            'phone' => '923331112233',
            'status' => 'open',
            'unread' => 0,
            'messages' => [
                ['inbound', 'text', 'The product photos look great!', 1440],
                ['outbound', 'text', 'Glad you like them. We can also make a short video.', 1430],
                ['inbound', 'video', 'Here is a clip of the unboxing.', 1420, 'https://www.w3schools.com/html/mov_bbb.mp4'],
                ['outbound', 'text', 'Excellent, this will work perfectly for the campaign.', 1400],
                ['inbound', 'sticker', null, 1390, 'https://picsum.photos/seed/sticker/200/200'],
            ],
        ],
        [
            'name' => 'Hina Rizvi',
            'phone' => '923009998877',
            'status' => 'ended',
            'unread' => 0,
            'messages' => [
                ['inbound', 'text', 'My issue is resolved, thank you.', 2880],
                ['outbound', 'text', 'Happy to help, Hina! Have a great day.', 2870],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->chats as $chat) {
            $contact = Contact::updateOrCreate(
                ['phone' => $chat['phone']],
                ['name' => $chat['name'], 'last_seen_at' => now()]
            );

            $lastMessage = $chat['messages'][count($chat['messages']) - 1];

            $conversation = Conversation::updateOrCreate(
                ['contact_id' => $contact->id],
                [
                    'phone_number_id' => '1323547254184276',
                    'status' => $chat['status'],
                    'unread_count' => $chat['unread'],
                    'last_message_at' => now()->subMinutes($lastMessage[3]),
                ]
            );

            $conversation->messages()->delete();

            foreach ($chat['messages'] as $position => $message) {
                $direction = $message[0];
                $type = $message[1];
                $body = $message[2];
                $minutesAgo = $message[3];
                $mediaUrl = $message[4] ?? null;
                $sentAt = Carbon::now()->subMinutes($minutesAgo);

                Message::create([
                    'conversation_id' => $conversation->id,
                    'kapso_message_id' => 'demo-'.$conversation->id.'-'.$position,
                    'direction' => $direction,
                    'type' => $type,
                    'body' => $body,
                    'media_url' => $mediaUrl,
                    'status' => $direction === 'outbound' ? 'delivered' : 'received',
                    'sent_at' => $sentAt,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ]);
            }
        }
    }
}
