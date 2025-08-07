<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        return UserSession::where('user_id', $request->user()->id)->get(['id', 'device_name', 'browser_name', 'ip_address', 'country', 'last_used_at', 'expires_at']);
    }

    public function destroy(Request $request, $id)
    {
        $session = UserSession::where('user_id', $request->user()->id)->findOrFail($id);
        $session->delete();
        return response()->json(['message' => 'Session terminated']);
    }
}
