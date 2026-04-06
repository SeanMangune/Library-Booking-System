<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 16px; overflow: hidden; margin-top: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%); padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: -0.5px; }
        .content { padding: 40px; }
        .content p { font-size: 16px; line-height: 24px; color: #475569; margin: 0 0 20px 0; }
        .room-card { background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 24px; margin: 32px 0; }
        .room-title { font-size: 20px; font-weight: 700; color: #0369a1; margin: 0 0 8px 0; }
        .room-location { font-size: 14px; font-weight: 600; color: #0284c7; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 16px 0; }
        .room-details { font-size: 14px; color: #0c4a6e; margin: 0; line-height: 22px; }
        .btn-container { text-align: center; margin-top: 32px; }
        .btn { display: inline-block; background-color: #0ea5e9; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; font-size: 16px; }
        .footer { padding: 32px 40px; text-align: center; border-top: 1px solid #e2e8f0; background-color: #f8fafc; }
        .footer p { font-size: 14px; color: #64748b; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header">
                <h1>Exciting News! 🎉</h1>
            </div>
            <div class="content">
                <p>Hello SmartSpace User,</p>
                <p>We are thrilled to announce that a brand new room has been added to the SmartSpace library and is now available for bookings!</p>
                
                <div class="room-card">
                    <h2 class="room-title">{{ $room->name }}</h2>
                    <p class="room-location">📍 {{ $room->location ?? '2F Library' }}</p>
                    <p class="room-details">
                        <strong>Capacity:</strong> Up to {{ $room->capacity }} people<br>
                        <strong>Type:</strong> {{ $room->isCollaborative() ? 'Collaborative' : 'Standard' }} Room<br>
                        @if($room->requires_approval)
                        <strong>Note:</strong> This room requires librarian approval for bookings.
                        @endif
                    </p>
                </div>
                
                <p>Be among the first to experience this new space. Head over to your dashboard to check its availability and make a reservation today.</p>
                
                <div class="btn-container">
                    <a href="{{ config('app.url') }}/dashboard" class="btn">Book This Room</a>
                </div>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} SmartSpace Library. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
