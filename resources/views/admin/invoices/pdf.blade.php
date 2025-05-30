<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-info {
            margin-bottom: 20px;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            padding: 5px;
            vertical-align: top;
        }
        .client-info {
            margin-bottom: 20px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th, table.items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table.items th {
            background-color: #f2f2f2;
        }
        .total {
            text-align: right;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>INVOICE</h1>
        </div>
        
        <div class="invoice-info">
            <table>
                <tr>
                    <td width="50%">
                        <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                        <strong>Order Reference:</strong> {{ $order->reference_number }}<br>
                        <strong>Issue Date:</strong> {{ $invoice->issued_at->format('d M Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }}
                    </td>
                    <td width="50%" style="text-align: right;">
                        <strong>Carpet Cleaning Service</strong><br>
                        123 Main Street<br>
                        Kuala Lumpur, 50000<br>
                        Malaysia<br>
                        info@carpetcleaning.com
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="client-info">
            <strong>Bill To:</strong><br>
            {{ $user->name ?? 'N/A' }}<br>
            {{ $client->phone ?? 'N/A' }}<br>
            {{ $order->pickup_address ?? $order->pickupAddress->formatted ?? 'N/A' }}
        </div>
        
        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th style="text-align: right;">Price (RM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($carpets as $carpet)
                <tr>
                    <td>Carpet #{{ $loop->iteration }}</td>
                    <td>
                        {{ $carpet->carpetType->name ?? 'Standard Carpet' }} ({{ $carpet->width }} ft × {{ $carpet->length }} ft)
                        @if($carpet->status === 'canceled')
                            <strong style="color: #F44336;"> [CANCELED]</strong>
                        @endif
                        @if($carpet->addonServices && $carpet->addonServices->count() > 0)
                            <br><small>Add-ons: 
                            @foreach($carpet->addonServices as $addon)
                                {{ $addon->name }}{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                            </small>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        @if($carpet->status === 'canceled')
                            0.00
                        @else
                            {{ number_format($carpet->calculatePrice(), 2) }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right;">Subtotal</td>
                    <td style="text-align: right;">RM {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount > 0)
                <tr>
                    <td colspan="2" style="text-align: right;">Discount
                        @if($invoice->discount_type === 'percentage')
                            ({{ $invoice->discount }}%)
                        @endif
                    </td>
                    <td style="text-align: right;">- RM {{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount / 100) : $invoice->discount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->tax_amount > 0)
                <tr>
                    <td colspan="2" style="text-align: right;">Tax</td>
                    <td style="text-align: right;">RM {{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="2" style="text-align: right;"><strong>Total</strong></td>
                    <td style="text-align: right;"><strong>RM {{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <div>
            <strong>Payment Status:</strong> 
            <span style="text-transform: uppercase; 
                color: @if($invoice->status == 'paid') #4CAF50 
                       @elseif($invoice->status == 'pending') #FF9800
                       @else #F44336 @endif;">
                {{ $invoice->status }}
            </span>
        </div>
        
        @if($invoice->notes)
        <div style="margin-top: 20px;">
            <strong>Notes:</strong>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This invoice was generated on {{ now()->format('d M Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
