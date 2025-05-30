<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice #{{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background-color: #f7f9fc;
        }
        .payment-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .payment-header {
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .invoice-details {
            background-color: #f8fafc;
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 2rem;
        }
        .payment-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background-color: #4f46e5;
            color: white;
            text-align: center;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s;
            text-decoration: none;
        }
        .payment-btn:hover {
            background-color: #4338ca;
        }
        .payment-footer {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 2rem;
        }
        table.invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        table.invoice-items th {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #4b5563;
        }
        table.invoice-items td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="logo">
            <h1 class="text-3xl font-bold text-gray-900">Carpet Cleaning Service</h1>
        </div>
        
        <div class="payment-header">
            <h2 class="text-2xl font-semibold text-gray-800">Invoice Payment</h2>
            <p class="text-gray-600">Please review your invoice details before proceeding to payment.</p>
        </div>
        
        <div class="invoice-details">
            <div class="flex justify-between mb-4">
                <div>
                    <p class="font-semibold text-gray-700">Invoice Number:</p>
                    <p class="text-gray-900">{{ $invoice->invoice_number }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">Issue Date:</p>
                    <p class="text-gray-900">{{ $invoice->issued_at->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">Due Date:</p>
                    <p class="text-gray-900">{{ $invoice->due_date->format('d M Y') }}</p>
                </div>
            </div>
            
            <div class="mb-6">
                <p class="font-semibold text-gray-700">Bill To:</p>
                <p class="text-gray-900">{{ $invoice->order->client->user->name }}</p>
                <p class="text-gray-900">{{ $invoice->order->client->phone }}</p>
                <p class="text-gray-900">{{ $invoice->order->pickup_address ?? $invoice->order->pickupAddress->formatted ?? 'N/A' }}</p>
            </div>
            
            <table class="invoice-items">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th class="text-right">Price (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @if($invoice->discount > 0)
                    <tr>
                        <td colspan="2" class="text-right font-semibold">Subtotal:</td>
                        <td class="text-right">RM {{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-right font-semibold">
                            Discount ({{ $invoice->discount_type === 'percentage' ? $invoice->discount . '%' : 'RM ' . number_format($invoice->discount, 2) }}):
                        </td>
                        <td class="text-right">- RM {{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount / 100) : $invoice->discount, 2) }}</td>
                    </tr>
                    @endif
                    
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td colspan="2" class="text-right font-semibold">Tax:</td>
                        <td class="text-right">RM {{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @endif
                    
                    <tr>
                        <td colspan="2" class="text-right font-bold text-lg">Total:</td>
                        <td class="text-right font-bold text-lg">RM {{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <a href="{{ $paymentUrl }}" class="payment-btn">
            <i class="fas fa-credit-card mr-2"></i> Pay Now RM {{ number_format($invoice->total_amount, 2) }}
        </a>
        
        <div class="payment-footer">
            <p>You will be redirected to ToyyibPay secure payment page to complete your payment.</p>
            <p class="mt-2">© {{ date('Y') }} Carpet Cleaning Service. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
