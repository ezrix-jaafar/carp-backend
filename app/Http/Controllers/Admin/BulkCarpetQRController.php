<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carpet;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BulkCarpetQRController extends Controller
{
    /**
     * Generate QR codes for multiple carpets in bulk
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function generateBulkQrCodes(Request $request)
    {
        // Extract comma-separated carpet IDs from the request
        $carpetIds = explode(',', $request->input('carpets', ''));
        
        // Fetch carpets by IDs
        $carpets = Carpet::with(['order.client.user', 'carpetType'])
            ->findMany($carpetIds);
            
        if ($carpets->isEmpty()) {
            return redirect()->back()->with('error', 'No valid carpets found for QR code generation');
        }
        
        // Get first order for reference
        $order = $carpets->first()->order;
        
        // Prepare data for each carpet
        $carpetsData = [];
        foreach ($carpets as $carpet) {
            // Generate QR code as base64 data URI
            $qrCodeData = [
                'id' => $carpet->id,
                'qr_code' => $carpet->qr_code,
                'pack_number' => $carpet->pack_number,
                'type' => $carpet->carpetType->name ?? 'Unknown',
                'order_id' => $carpet->order_id,
                'order_reference' => $carpet->order->reference_number ?? null,
                'client_name' => $carpet->order->client->user->name ?? 'Unknown Client',
            ];
            
            // Convert to JSON for QR code
            $qrCodeJson = json_encode($qrCodeData);
            
            // Generate QR code as base64 data URI
            $qrCode = base64_encode(QrCode::format('png')
                ->size(200)
                ->errorCorrection('H')
                ->generate($qrCodeJson));
            
            $carpetsData[] = [
                'carpet' => $carpet,
                'qr_code' => $qrCode,
                'data' => $qrCodeData,
            ];
        }
        
        return view('admin.carpets.bulk-qr-codes', [
            'carpets' => $carpetsData,
            'order' => $order,
        ]);
    }
}
