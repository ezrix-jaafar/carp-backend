<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carpet Label</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .label-container {
            width: 95mm;
            min-height: 85mm;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 5mm;
            position: relative;
        }
        .company-info {
            text-align: center;
            margin-bottom: 5mm;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }
        .company-contact {
            font-size: 8pt;
            margin-bottom: 1mm;
        }
        .carpet-info {
            margin-bottom: 5mm;
        }
        .info-row {
            display: flex;
            margin-bottom: 2mm;
        }
        .info-label {
            font-weight: bold;
            width: 30%;
        }
        .info-value {
            width: 70%;
        }
        .qr-container {
            text-align: center;
            margin-top: 5mm;
        }
        .qr-code {
            width: 35mm;
            height: 35mm;
            margin: 0 auto;
        }
        .qr-text {
            font-size: 7pt;
            margin-top: 1mm;
            word-wrap: break-word;
            text-align: center;
        }
        .carpet-id {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin-top: 3mm;
        }
        .footer {
            font-size: 7pt;
            margin-top: 5mm;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="label-container">
        <div class="qr-container">
            <div class="qr-code">
                {!! $qrCode !!}
            </div>
            <div class="qr-text">{{ $carpet->qr_code }}</div>
        </div>
        
        <div class="carpet-info">
            <div class="company-name">CARPET CLEANING SERVICE</div>
            <div class="company-contact">123 Clean Street, Kuala Lumpur • Tel: +60 12-345-6789</div>
        </div>
        
        <div class="carpet-info">
            <div class="info-row">
                <div class="info-label">Agent:</div>
                <div class="info-value">{{ optional($carpet->order->agent->user)->name ?? 'Unassigned' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Pickup:</div>
                <div class="info-value">{{ optional($carpet->order->pickup_date) ? \Carbon\Carbon::parse($carpet->order->pickup_date)->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Customer:</div>
                <div class="info-value">{{ $clientName }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Carpet #:</div>
                <div class="info-value">{{ $carpet->qr_code }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Pack #:</div>
                <div class="info-value">{{ $carpet->pack_number }}</div>
            </div>
            <!-- Removed notes and other unused fields -->
            <div class="info-row">
                <div class="info-label">Notes:</div>
                <div class="info-value">{{ $carpet->notes }}</div>
            </div>
            @endif
        </div>
            <div class="qr-code">
                {!! $qrCode !!}
            </div>
            <div class="qr-text">{{ $carpet->qr_code }}</div>
        </div>
        
        
            
        
        <div class="footer">
            Printed on {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
