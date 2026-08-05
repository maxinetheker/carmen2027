<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
        ]);

        $device = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? 'android',
                'last_used_at' => now(),
            ]
        );

        return response()->json(['id' => $device->id], 201);
    }

    public function destroy(Request $request)
    {
        $request->validate(['token' => ['required', 'string']]);

        $request->user()->deviceTokens()
            ->where('token', $request->string('token'))
            ->delete();

        return response()->json(['message' => 'Token eliminado.']);
    }
}
