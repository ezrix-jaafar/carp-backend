<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Generation Error</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .error-header {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 5px solid #f5c6cb;
        }
        h1 {
            margin-top: 0;
            font-size: 24px;
        }
        .error-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #eee;
            margin-bottom: 20px;
        }
        .actions {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin-right: 10px;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-header">
            <h1>PDF Generation Error</h1>
            <p>We encountered an issue while trying to generate the PDF for invoice #{{ $invoice->invoice_number }}.</p>
        </div>
        
        <div class="error-details">
            <strong>Error Details:</strong>
            <p>{{ $message }}</p>
        </div>
        
        <div class="invoice-info">
            <h2>Invoice Information</h2>
            <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Order Reference:</strong> {{ $invoice->order->reference_number ?? 'N/A' }}</p>
            <p><strong>Issue Date:</strong> {{ $invoice->issued_at->format('d M Y') }}</p>
            <p><strong>Amount:</strong> RM {{ number_format($invoice->total_amount, 2) }}</p>
        </div>
        
        <div class="actions">
            <a href="{{ url('/admin/invoices') }}" class="btn">Return to Invoices</a>
            <a href="{{ url('/admin/invoices/' . $invoice->id . '/edit') }}" class="btn btn-secondary">Edit Invoice</a>
        </div>
    </div>
</body>
</html>
