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
        /* Setting page size to exact dimensions needed */
        @page {
            size: 50mm 80mm;
            margin: 0;
            overflow: hidden;
        }
        .label-container {
            width: 38mm; /* reduced width */
            max-height: 70mm; /* reduced height */
            margin: 4mm;
            border: 2px solid #000;
            display: flex;
            flex-direction: column;
            padding: 1.5mm;
            position: relative;
            box-sizing: border-box;
            page-break-inside: avoid;
        }
        /* Remove unused company header styles */
        /* .company-info { text-align: center; margin-bottom: 2mm; } */

        .company-name {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        .company-contact {
            font-size: 6pt;
            margin-bottom: 1mm;
        }
        .carpet-info {
            margin-bottom: 2mm;
        }
        .info-row {
            display: flex;
            margin-bottom: 1mm;
            font-size: 6pt;
        }
        .info-label {
            font-weight: bold;
            width: 30%;
        }
        .info-value {
            width: 70%;
            word-break: break-word;
        }
        .qr-container {
            text-align: center;
            margin-top: 0;
            margin-bottom: 2mm;
        }
        .qr-code {
            width: 16mm;
            height: 16mm;
            margin: 0 auto;
        }

/* .carpet-id { font-size: 10pt; font-weight: bold; text-align: center; margin-top: 2mm; } */
        .footer {
            font-size: 5pt;
            margin-top: 2mm;
            text-align: center;
            position: absolute;
            bottom: 2mm;
            width: 100%;
            left: 0;
        }
        /* New section styles */
        .section {
            padding: 1.5mm;
            border-bottom: 2px solid #000;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 1mm;
        }
        .section:last-child { border-bottom: none; }

        .top-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-text { display: flex; flex-direction: column; gap: 0.5mm; }
        .carpet-number, .pickup-date { font-size: 8pt; font-weight: 700; }
        .pack-number { font-size: 10pt; font-weight: 700; }

        .field-label { font-size: 8pt; font-weight: 400; }
        .field-value { font-size: 10pt; font-weight: 700; }
    </style>
</head>
<body>


    @foreach($carpets as $index => $carpet)
        @if($index > 0)
        <div style="page-break-after: always;"></div>
        @endif
        <div class="label-container">
            <!-- Top & QR section -->
            <div class="section top-section">
                <div class="top-header">
                    <div class="top-text">
                        <div class="carpet-number">{{ $carpet->qr_code }}</div>
                        <div class="pickup-date">{{ optional($carpet->order->pickup_date) ? \Carbon\Carbon::parse($carpet->order->pickup_date)->format('d/m/Y') : '-' }}</div>
                    </div>
                    <div class="pack-number">{{ $carpet->pack_number }}</div>
                </div>
                <div class="qr-container" style="margin-top:2mm; margin-bottom:2mm;">
                    <img class="qr-code" src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(200)->errorCorrection('H')->generate($carpet->qr_code)) }}" alt="QR Code" />
                </div>
            </div>

            <!-- Customer & Agent section -->
            <div class="section mid-section">
                <div class="field-label">Customer Name</div>
                <div class="field-value">{{ $clientName }}</div>
                <div class="field-label">Agent Name</div>
                <div class="field-value">{{ optional($carpet->order->agent->user)->name ?? 'Unassigned' }}</div>
            </div>

            <!-- Note section - directly following mid-section -->
            <div class="section bottom-section" style="flex:1 1 0; border-bottom: none;">
                <div class="field-label">Note</div>
            </div>








        </div>

    @endforeach
</body>
</html>
