<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Device;

class DeviceController extends Controller
{
    /**
     * Store or update a device token for the authenticated user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'platform' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $device = Device::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $user->id,
                'platform' => $request->platform,
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $device,
        ]);
    }

    /**
     * Delete a device token (logout or uninstall scenario).
     */
    public function destroy(Request $request, $token)
    {
        $user = $request->user();
        Device::where('token', $token)->where('user_id', $user->id)->delete();

        return response()->json(['status' => 'success']);
    }
}
