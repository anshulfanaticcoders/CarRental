<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TwilioWhatsappService;

class WhatsAppController extends Controller
{
    protected $twilioWhatsapp;

    public function __construct(TwilioWhatsappService $twilioWhatsapp)
    {
        $this->twilioWhatsapp = $twilioWhatsapp;
    }

    public function sendTestMessage()
    {
        $to = '+918278825392'; // User’s phone number in international format
        $message = 'Hi! This is a test WhatsApp message from your Laravel app.';

        $this->twilioWhatsapp->sendMessage($to, $message);

        return 'Message sent!';
    }

    public function sendMessageFromUser(Request $request)
{
    $request->validate([
        'message' => 'required|string',
    ]);

    $customerNumber = '+918278825392';
    $message = $request->input('message');

    $this->twilioWhatsapp->sendMessage($customerNumber, $message);

    return response()->json(['status' => 'success']);
}

}
