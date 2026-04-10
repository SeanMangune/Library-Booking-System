<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Reports Print View</title>
    <style>
        :root {
            --text: #111827;
            --muted: #6b7280;
            --border: #d1d5db;
            --panel: #f8fafc;
            --brand: #4338ca;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background: #fff;
            font-size: 12px;
            line-height: 1.4;
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px;
        }

        .header {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            background: linear-gradient(120deg, #eef2ff, #f8fafc);
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            color: #1e1b4b;
        }

        .header p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 16px;
        }

        .filter-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            background: var(--panel);
        }

        .filter-item .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 2px;
        }

        .filter-item .value {
            font-weight: 700;
            color: #1f2937;
        }

        h2 {
            margin: 18px 0 8px;
            font-size: 14px;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 8px;
        }

        .stat-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            background: #fff;
        }

        .stat-card .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        .stat-card .value {
            margin-top: 4px;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th,
        td {
            border: 1px solid var(--border);
            padding: 7px 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #eef2ff;
            color: #312e81;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        td {
            color: #111827;
            font-size: 11px;
        }

        .muted {
            color: var(--muted);
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .page {
                max-width: none;
                padding: 8mm;
            }

            h2,
            table,
            .stats,
            .filter-grid,
            .header {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="header">
            <h1>Detailed Reports</h1>
            <p>Generated {{ $generatedAt->format('M d, Y g:i A') }}</p>
        </header>

        <section class="filter-grid">
            @foreach($filterSummary as $label => $value)
                <div class="filter-item">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </div>
            @endforeach
        </section>

        <section>
            <h2>Summary</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="label">Total Bookings</div>
                    <div class="value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Approved</div>
                    <div class="value">{{ number_format($stats['approved']) }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Pending</div>
                    <div class="value">{{ number_format($stats['pending']) }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Rejected</div>
                    <div class="value">{{ number_format($stats['rejected']) }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Cancelled</div>
                    <div class="value">{{ number_format($stats['cancelled']) }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Capacity Requests</div>
                    <div class="value">{{ number_format($stats['capacity_exceptions']) }}</div>
                </div>
            </div>
        </section>

        <section>
            <h2>Room Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Total</th>
                        <th>Approved</th>
                        <th>Pending</th>
                        <th>Exception</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roomBreakdown as $row)
                        <tr>
                            <td>{{ $row['room_name'] }}</td>
                            <td>{{ number_format($row['bookings']) }}</td>
                            <td>{{ number_format($row['approved']) }}</td>
                            <td>{{ number_format($row['pending']) }}</td>
                            <td>{{ number_format($row['capacity_exceptions']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No room data found for current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Top Requesters</h2>
            <table>
                <thead>
                    <tr>
                        <th>Requester</th>
                        <th>Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topRequesters as $requester)
                        <tr>
                            <td>{{ $requester['user_name'] }}</td>
                            <td>{{ number_format($requester['bookings']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="muted">No requester activity found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Daily Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Approved</th>
                        <th>Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyBreakdown as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                            <td>{{ number_format($row['bookings']) }}</td>
                            <td>{{ number_format($row['approved']) }}</td>
                            <td>{{ number_format($row['pending']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">No daily activity found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Detailed Booking Data ({{ number_format($bookings->count()) }} records)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Room</th>
                        <th>Purpose</th>
                        <th>Requester</th>
                        <th>Email</th>
                        <th>Attendees</th>
                        <th>Status</th>
                        <th>Capacity Approval</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->formatted_date }}</td>
                            <td>{{ $booking->start_time ? \Carbon\Carbon::parse($booking->start_time)->format('g:i A') : '' }}</td>
                            <td>{{ $booking->end_time ? \Carbon\Carbon::parse($booking->end_time)->format('g:i A') : '' }}</td>
                            <td>{{ $booking->room?->name }}</td>
                            <td>{{ $booking->title ?: 'N/A' }}</td>
                            <td>{{ $booking->user_name }}</td>
                            <td>{{ $booking->user_email }}</td>
                            <td>{{ number_format($booking->attendees) }}</td>
                            <td>{{ ucfirst($booking->status) }}</td>
                            <td>{{ $booking->requiresCapacityPermission() ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="muted">No detailed booking data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
