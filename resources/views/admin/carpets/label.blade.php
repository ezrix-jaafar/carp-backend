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
        <div class="company-info">
            <div class="company-name">CARPET CLEANING SERVICE</div>
            <div class="company-contact">123 Clean Street, Kuala Lumpur • Tel: +60 12-345-6789</div>
        </div>
        
        <div class="carpet-info">
            <div class="info-row">
                <div class="info-label">Order:</div>
                <div class="info-value">{{ $orderRef }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Client:</div>
                <div class="info-value">{{ $clientName }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Type:</div>
                <div class="info-value">{{ $carpetType }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Dimensions:</div>
                <div class="info-value">{{ $carpet->dimensions }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Color:</div>
                <div class="info-value">{{ $carpet->color }}</div>
            </div>
            @if($carpet->notes)
            <div class="info-row">
                <div class="info-label">Notes:</div>
                <div class="info-value">{{ $carpet->notes }}</div>
            </div>
            @endif
        </div>
        
        <div class="qr-container">
            <div class="qr-code">
                {!! $qrCode !!}
            </div>
            <div class="qr-text">{{ $carpet->qr_code }}</div>
        </div>
        
        <div class="carpet-id">
            #{{ $carpet->id }}
        </div>
        
        <div class="footer">
            Printed on {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
