<?php

namespace App\Http\Controllers;

use App\Jobs\SendPlainEmail;
use App\Models\Configurations\Configuration;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send_text_email(Request $request)
    {
        $message = [
            'to' => $request->input('email'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'company' => Configuration::where('key', 'company_name')->value('value'),
        ];

        if (SendPlainEmail::dispatch($message)) {
            return response()->json([
                'status' => true,
                'message' => 'message.email_submitted',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'message.email_failed',

            ], 200);
        }
    }
}
