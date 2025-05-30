<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Paid - Invoice #{{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background-color: #f7f9fc;
        }
        .info-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .info-icon {
            color: #0ea5e9;
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }
        .info-message {
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #4f46e5;
            color: white;
            text-align: center;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s;
            text-decoration: none;
            margin: 0.5rem;
        }
        .btn:hover {
            background-color: #4338ca;
        }
        .footer {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="info-container">
        <div class="info-icon">
            <i class="fas fa-info-circle"></i>
        </div>
        
        <div class="info-message">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Invoice Already Paid</h1>
            <p class="text-gray-600">This invoice has already been paid. No further payment is required.</p>
        </div>
        
        <div class="invoice-details mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="font-semibold text-gray-800">Invoice Number: {{ $invoice->invoice_number }}</p>
                <p class="text-gray-700">Amount: RM {{ number_format($invoice->total_amount, 2) }}</p>
                <p class="text-gray-700">Status: <span class="text-green-600 font-semibold">PAID</span></p>
            </div>
        </div>
        
        <div class="actions">
            <a href="/" class="btn">
                <i class="fas fa-home mr-2"></i> Return Home
            </a>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p class="mt-2">© {{ date('Y') }} Carpet Cleaning Service. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
