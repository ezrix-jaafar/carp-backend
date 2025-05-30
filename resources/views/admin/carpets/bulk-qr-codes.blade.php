<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Carpet QR Codes - Order #{{ $order->reference_number ?? 'N/A' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 10px;
            color: #2c3e50;
        }
        .labels-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }
        .carpet-label {
            width: 300px;
            border: 1px solid #ddd;
            margin: 10px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            page-break-inside: avoid;
            background-color: white;
        }
        .qr-code {
            text-align: center;
            margin-bottom: 15px;
        }
        .qr-code img {
            max-width: 150px;
            height: auto;
        }
        .carpet-info {
            margin-top: 15px;
            font-size: 14px;
        }
        .carpet-info h3 {
            margin: 0 0 10px;
            font-size: 16px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            flex: 0 0 40%;
            font-weight: 600;
            color: #555;
        }
        .info-value {
            flex: 0 0 60%;
        }
        .pack-number {
            font-size: 16px;
            color: #e74c3c;
            margin: 10px 0;
            padding: 4px;
            border: 2px dashed #e74c3c;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
        .print-instructions {
            margin-top: 20px;
            background-color: #e7f5ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .actions {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 0 10px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        .btn-secondary {
            background-color: #7f8c8d;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                padding: 0;
                background: none;
            }
            .container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
            .actions, .print-instructions {
                display: none;
            }
            .header {
                margin-bottom: 10px;
                padding-bottom: 10px;
            }
            .carpet-label {
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Carpet Labels</h1>
            <p>Order #{{ $order->reference_number ?? 'N/A' }} - {{ count($carpets) }} Carpets</p>
            <p>Client: {{ $order->client->user->name ?? 'Unknown Client' }}</p>
        </div>
        
        <div class="labels-container">
            @foreach($carpets as $carpetData)
                <div class="carpet-label">
                    <div class="qr-code">
                        <img src="data:image/png;base64,{{ $carpetData['qr_code'] }}" alt="QR Code">
                        <p>{{ $carpetData['carpet']->qr_code }}</p>
                    </div>
                    
                    <div class="pack-number">
                        Pack #: {{ $carpetData['carpet']->pack_number }}
                    </div>
                    
                    <div class="carpet-info">
                        <h3>Carpet Details</h3>
                        
                        <div class="info-row">
                            <div class="info-label">Type:</div>
                            <div class="info-value">{{ $carpetData['data']['type'] }}</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Order #:</div>
                            <div class="info-value">{{ $carpetData['data']['order_reference'] ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Client:</div>
                            <div class="info-value">{{ $carpetData['data']['client_name'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="print-instructions">
            <h3>Printing Instructions</h3>
            <p>For best results, print this page on A4 paper. The QR codes contain all the information needed to identify and track these carpets.</p>
            <p>Each label will be automatically placed on a new page when printing.</p>
        </div>
        
        <div class="actions">
            <button onclick="window.print()" class="btn">Print QR Codes</button>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Back to Order</a>
        </div>
    </div>
</body>
</html>
