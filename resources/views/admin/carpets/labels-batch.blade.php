<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carpet Labels - Order {{ $orderRef }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .label-container {
            width: 95mm;
            min-height: 85mm;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 5mm;
            position: relative;
            margin-bottom: 10mm;
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
        .batch-header {
            text-align: center;
            margin-bottom: 10mm;
        }
        .batch-title {
            font-size: 18pt;
            font-weight: bold;
        }
        .batch-subtitle {
            font-size: 14pt;
            margin-top: 3mm;
        }
    </style>
</head>
<body>
    <div class="batch-header">
        <div class="batch-title">CARPET LABELS - ORDER {{ $orderRef }}</div>
        <div class="batch-subtitle">Client: {{ $clientName }} | Total Carpets: {{ $carpets->count() }}</div>
    </div>
    
    @foreach($carpets as $index => $carpet)
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
                    <div class="info-value">
                        @php
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
                        @endphp
                        {{ $carpetType }}
                    </div>
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
                    {!! QrCode::size(150)->errorCorrection('H')->generate($carpet->qr_code) !!}
                </div>
                <div class="qr-text">{{ $carpet->qr_code }}</div>
            </div>
            
            <div class="carpet-id">
                #{{ $carpet->id }}
            </div>
            
            <div class="footer">
                Printed on {{ now()->format('d/m/Y H:i') }} | Page {{ $index + 1 }} of {{ $carpets->count() }}
            </div>
        </div>
        
        @if($index < $carpets->count() - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
