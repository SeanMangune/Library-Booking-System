<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 16px; overflow: hidden; margin-top: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { padding: 32px 40px; text-align: center; }
        .header-active { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .header-inactive { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: -0.5px; }
        .content { padding: 40px; }
        .content p { font-size: 16px; line-height: 24px; color: #475569; margin: 0 0 20px 0; }
        .status-box { border-radius: 12px; padding: 24px; margin: 32px 0; text-align: center; border: 1px solid;}
        .status-active { background-color: #ecfdf5; border-color: #a7f3d0; color: #047857; }
        .status-inactive { background-color: #fffbeb; border-color: #fde68a; color: #b45309; }
        .status-title { font-size: 20px; font-weight: 700; margin: 0 0 8px 0; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 9999px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .badge-active { background-color: #d1fae5; color: #047857; }
        .badge-inactive { background-color: #fef3c7; color: #b45309; }
        .btn-container { text-align: center; margin-top: 32px; }
        .btn { display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; font-size: 16px; }
        .footer { padding: 32px 40px; text-align: center; border-top: 1px solid #e2e8f0; background-color: #f8fafc; }
        .footer p { font-size: 14px; color: #64748b; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header {{ $status === 'active' ? 'header-active' : 'header-inactive' }}">
                <h1>Room Status Update</h1>
            </div>
            <div class="content">
                <p>Hello SmartSpace User,</p>
                
                @if($status === 'active')
                <p>Good news! A room that was previously unavailable has just been re-opened for bookings.</p>
                @else
                <p>Please be advised that one of our rooms has been temporarily closed and is currently unavailable for new bookings.</p>
                @endif
                
                <div class="status-box {{ $status === 'active' ? 'status-active' : 'status-inactive' }}">
                    <h2 class="status-title">{{ $room->name }}</h2>
                    <span class="status-badge {{ $status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                        {{ $status === 'active' ? 'Now Open' : 'Temporarily Closed' }}
                    </span>
                </div>
                
                @if($status === 'active')
                <p>You can now check availability and start reserving this space again from your dashboard.</p>
                <div class="btn-container">
                    <a href="{{ config('app.url') }}/rooms" class="btn">View Bookings</a>
                </div>
                @else
                <p>We apologize for any inconvenience. Existing bookings may be affected, and our administrative team will reach out directly if your reservation needs to be rescheduled.</p>
                @endif
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} SmartSpace. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
