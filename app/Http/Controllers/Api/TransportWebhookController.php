<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logistics\Transporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransportWebhookController extends Controller
{
    public function __invoke(Request $request, Transporter $transporter): JsonResponse
    {
        Log::channel('daily')->info('Transporter webhook', [
            'transporter' => $transporter->code,
            'payload' => $request->all(),
        ]);

        return response()->json(['status' => 'ack']);
    }
}
