<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background-color: #f7f9fc;
        }
        .error-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .error-icon {
            color: #ef4444;
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }
        .error-message {
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
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        
        <div class="error-message">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Error</h1>
            <p class="text-gray-600">{{ $message ?? 'An error occurred while processing your payment.' }}</p>
        </div>
        
        <div class="actions">
            <a href="/" class="btn">
                <i class="fas fa-home mr-2"></i> Return Home
            </a>
        </div>
        
        <div class="footer">
            <p>If you continue to experience issues, please contact support.</p>
            <p class="mt-2">© {{ date('Y') }} Carpet Cleaning Service. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
