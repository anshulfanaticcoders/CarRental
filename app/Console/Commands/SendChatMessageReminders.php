<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendChatMessageReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for unread chat messages older than 30 minutes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to send chat message reminders...');

        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);

        // Fetch unread messages older than 30 minutes for which a reminder has not yet been sent.
        $messagesToRemind = Message::whereNull('read_at')
            ->whereNull('reminder_sent_at') // Check that a reminder hasn't been sent
            ->where('created_at', '<=', $thirtyMinutesAgo) // Message is older than 30 minutes
            ->with('receiver') // Eager load receiver
            ->get();

        if ($messagesToRemind->isEmpty()) {
            $this->info('No messages found requiring a reminder.');
            return 0;
        }

        $this->info("Found {$messagesToRemind->count()} messages to send reminders for.");

        foreach ($messagesToRemind as $message) {
            if ($message->receiver) {
                try {
                    $message->receiver->notify(new MessageReminderNotification($message));
                    $this->info("Reminder sent for message ID: {$message->id} to user ID: {$message->receiver_id}");

                    // Mark that a reminder has been sent for this message
                    $message->update(['reminder_sent_at' => Carbon::now()]);

                } catch (\Exception $e) {
                    Log::error("Failed to send reminder for message ID: {$message->id}. Error: " . $e->getMessage());
                    $this->error("Failed to send reminder for message ID: {$message->id}. Check logs.");
                }
            } else {
                Log::warning("Message ID: {$message->id} has no receiver. Skipping reminder.");
                $this->warn("Message ID: {$message->id} has no receiver. Skipping reminder.");
            }
        }

        $this->info('Chat message reminders sent successfully.');
        return 0;
    }
}
