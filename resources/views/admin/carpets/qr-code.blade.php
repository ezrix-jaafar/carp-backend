<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carpet QR Code - {{ $carpet->qr_code }}</title>
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
            max-width: 800px;
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
        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 10px;
            color: #2c3e50;
        }
        .qr-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        .qr-code {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            flex: 0 0 45%;
        }
        .qr-code img {
            max-width: 100%;
            height: auto;
        }
        .carpet-details {
            flex: 0 0 45%;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .carpet-details h2 {
            margin-top: 0;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .detail-row {
            display: flex;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        .detail-label {
            flex: 0 0 40%;
            font-weight: 600;
            color: #555;
        }
        .detail-value {
            flex: 0 0 60%;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            background-color: #3498db;
        }
        .pack-number {
            font-size: 18px;
            color: #e74c3c;
            margin: 10px 0;
            padding: 5px;
            border: 2px dashed #e74c3c;
            border-radius: 4px;
            display: inline-block;
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
        .btn-success {
            background-color: #2ecc71;
        }
        @media print {
            body {
                padding: 0;
                background: none;
            }
            .container {
                box-shadow: none;
                max-width: 100%;
            }
            .actions, .print-instructions {
                display: none;
            }
            .header {
                margin-bottom: 10px;
                padding-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Carpet QR Code</h1>
            <p>Order #{{ $carpet->order->reference_number ?? 'N/A' }}</p>
        </div>
        
        <div class="qr-container">
            <div class="qr-code">
                <h2>QR Code</h2>
                {!! $qrCode !!}
                <p>{{ $carpet->qr_code }}</p>
                <p class="pack-number"><strong>Pack #: {{ $carpet->pack_number ?: 'N/A' }}</strong></p>
            </div>
            
            <div class="carpet-details">
                <h2>Carpet Details</h2>
                
                <div class="detail-row">
                    <div class="detail-label">ID:</div>
                    <div class="detail-value">{{ $carpet->id }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Type:</div>
                    <div class="detail-value">{{ $carpet->carpetType->name ?? ucfirst($carpet->type) ?? 'Unknown' }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Pack Number:</div>
                    <div class="detail-value"><strong>{{ $carpet->pack_number ?: 'N/A' }}</strong></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Dimensions:</div>
                    <div class="detail-value">
                        {{ number_format($carpet->width, 2) }}ft × {{ number_format($carpet->length, 2) }}ft
                        ({{ number_format($carpet->width * $carpet->length, 2) }} sq.ft)
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Color:</div>
                    <div class="detail-value">{{ $carpet->color }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="badge" style="background-color: 
                            @if ($carpet->status == 'pending') #7f8c8d
                            @elseif (in_array($carpet->status, ['picked_up', 'in_cleaning'])) #3498db
                            @elseif (in_array($carpet->status, ['cleaned', 'delivered'])) #2ecc71
                            @endif
                        ">
                            {{ ucfirst(str_replace('_', ' ', $carpet->status)) }}
                        </span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Client:</div>
                    <div class="detail-value">{{ $carpet->order->client->user->name ?? 'Unknown' }}</div>
                </div>
            </div>
        </div>
        
        <div class="print-instructions">
            <h3>Printing Instructions</h3>
            <p>For best results, print this page on A4 paper. The QR code contains all the information needed to identify and track this carpet.</p>
        </div>
        
        <div class="actions">
            <button onclick="window.print()" class="btn">Print QR Code</button>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</body>
</html>
