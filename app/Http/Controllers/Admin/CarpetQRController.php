<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carpet;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CarpetQRController extends Controller
{
    /**
     * Generate a QR code for a carpet
     *
     * @param Carpet $carpet
     * @return \Illuminate\Http\Response
     */
    public function generateQR(Carpet $carpet)
    {
        // Get carpet data to include in QR code
        $carpetData = [
            'id' => $carpet->id,
            'qr_code' => $carpet->qr_code,
            'pack_number' => $carpet->pack_number,
            'type' => $carpet->carpetType->name ?? $carpet->type ?? 'Unknown',
            'width' => $carpet->width,
            'length' => $carpet->length,
            'color' => $carpet->color,
            'order_id' => $carpet->order_id,
            'order_reference' => $carpet->order->reference_number ?? null,
            'client_name' => $carpet->order->client->user->name ?? 'Unknown Client',
        ];
        
        // Generate QR code content - JSON encoded data
        $qrContent = json_encode($carpetData);
        
        // Generate QR code
        $qrCode = QrCode::size(250)
            ->margin(1)
            ->format('png')
            ->generate($qrContent);
        
        // Return the QR code as an image
        return response($qrCode)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="carpet-qr-' . $carpet->qr_code . '.png"');
    }
    
    /**
     * Show a page with the QR code and carpet information
     *
     * @param Carpet $carpet
     * @return \Illuminate\Http\Response
     */
    public function showQR(Carpet $carpet)
    {
        // Generate the QR code content
        $carpetData = [
            'id' => $carpet->id,
            'qr_code' => $carpet->qr_code,
            'pack_number' => $carpet->pack_number,
            'type' => $carpet->carpetType->name ?? $carpet->type ?? 'Unknown',
            'width' => $carpet->width,
            'length' => $carpet->length,
            'color' => $carpet->color,
            'order_id' => $carpet->order_id,
            'order_reference' => $carpet->order->reference_number ?? null,
            'client_name' => $carpet->order->client->user->name ?? 'Unknown Client',
        ];
        
        $qrContent = json_encode($carpetData);
        
        // Generate QR code as SVG for better printing
        $qrCodeSvg = QrCode::size(200)
            ->margin(1)
            ->generate($qrContent);
            
        // Pass the carpet data and QR code to the view
        return view('admin.carpets.qr-code', [
            'carpet' => $carpet,
            'qrCode' => $qrCodeSvg,
        ]);
    }
}
