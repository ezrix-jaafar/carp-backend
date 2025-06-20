<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carpet;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class CarpetLabelController extends Controller
{
    /**
     * Generate and display a printable carpet label with QR code
     *
     * @param  int  $carpetId
     * @return \Illuminate\Http\Response
     */
    public function printLabel($carpetId)
    {
        $carpet = Carpet::with(['order.client.user'])->findOrFail($carpetId);
        
        // Generate QR code SVG
        $qrCode = QrCode::size(150)
            ->errorCorrection('H')
            ->generate($carpet->qr_code);
        
        // Get client name from the order
        $clientName = $carpet->order->client->user->name ?? 'Unknown Client';
        
        // Format carpet type and dimensions
        $carpetType = match ($carpet->type) {
            'wool' => 'Wool',
            'synthetic' => 'Synthetic',
            'silk' => 'Silk',
            'cotton' => 'Cotton',
            'jute' => 'Jute',
            'shag' => 'Shag',
            'persian' => 'Persian',
            'oriental' => 'Oriental',
            'modern' => 'Modern',
            'other' => 'Other',
            default => ucfirst($carpet->type),
        };
        
        // Prepare data for PDF
        $data = [
            'carpet' => $carpet,
            'qrCode' => $qrCode,
            'clientName' => $clientName,
            'carpetType' => $carpetType,
            'orderRef' => $carpet->order->reference_number,
        ];
        
        // Generate PDF and set custom paper size (50mm x 80mm)
        $pdf = PDF::loadView('admin.carpets.label', $data);
        // 1 mm ≈ 2.83465 pt, so 50 mm ≈ 141.73 pt, 80 mm ≈ 226.77 pt
        $pdf->setPaper([0, 0, 141.73, 226.77], 'portrait');
        
        // Return for download/print
        return $pdf->stream("carpet-label-{$carpet->id}.pdf");
    }
    
    /**
     * Generate a batch of carpet labels for an order
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\Response
     */
    public function printOrderLabels($orderId)
    {
        $carpets = Carpet::with(['order.client.user'])
            ->where('order_id', $orderId)
            ->get();
            
        if ($carpets->isEmpty()) {
            return back()->with('error', 'No carpets found for this order.');
        }
        
        // Prepare data for PDF
        $data = [
            'carpets' => $carpets,
            'orderRef' => $carpets->first()->order->reference_number,
            'clientName' => $carpets->first()->order->client->user->name ?? 'Unknown Client',
        ];
        
        // Generate PDF with multiple labels and set custom paper size (50mm x 80mm)
        $pdf = PDF::loadView('admin.carpets.labels-batch', $data);
        $pdf->setPaper([0, 0, 141.73, 226.77], 'portrait');
        
        // Return for download/print
        return $pdf->stream("carpet-labels-order-{$orderId}.pdf");
    }
}
