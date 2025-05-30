<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class InvoicePDFController extends Controller
{
    /**
     * Generate a PDF for the invoice.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function generatePDF(Invoice $invoice)
    {
        try {
            // Load relationships
            $invoice->load(['order.client.user', 'order.carpets.carpetType', 'order.carpets.addonServices']);

            // Generate PDF
            $pdf = PDF::loadView('admin.invoices.pdf', [
                'invoice' => $invoice,
                'order' => $invoice->order,
                'client' => $invoice->order->client ?? null,
                'user' => $invoice->order->client->user ?? null,
                'carpets' => $invoice->order->carpets ?? [],
            ]);

            // Return the PDF for download
            return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
        } catch (\Exception $e) {
            // Log the error
            Log::error('PDF Generation Error: ' . $e->getMessage());

            // Return a more user-friendly error page
            return response()->view('errors.pdf-error', [
                'message' => 'Unable to generate PDF. Error: ' . $e->getMessage(),
                'invoice' => $invoice
            ], 500);
        }
    }
}
