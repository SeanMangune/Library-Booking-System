<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ReportsController extends Controller
{
    private const EXPORT_HEADERS = [
        'Date',
        'Start',
        'End',
        'Room',
        'Purpose',
        'Requester',
        'Email',
        'Attendees',
        'Status',
        'Capacity Approval',
    ];

    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $baseQuery = $this->buildBaseQuery($filters);

        $exportFormat = strtolower(trim((string) $request->input('export', '')));
        if (in_array($exportFormat, ['csv', 'xlsx'], true)) {
            return $this->exportReport(clone $baseQuery, $exportFormat, $filters);
        }

        $reportBookings = (clone $baseQuery)
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->get();

        $reportData = $this->buildReportData($reportBookings);

        if ($request->boolean('print')) {
            return view('reports.print', array_merge($reportData, [
                'bookings' => $reportBookings,
                'filters' => $filters,
                'filterSummary' => $this->buildFilterSummary($filters),
                'generatedAt' => now(),
            ]));
        }

        $bookings = (clone $baseQuery)
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $rooms = Room::query()->visible()->orderBy('name')->get();

        return view('reports.index', array_merge($reportData, [
            'bookings' => $bookings,
            'filters' => $filters,
            'rooms' => $rooms,
        ]));
    }

    private function resolveFilters(Request $request): array
    {
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'room_id' => (string) $request->input('room_id', ''),
            'status' => (string) $request->input('status', ''),
        ];
    }

    private function buildBaseQuery(array $filters): Builder
    {
        $baseQuery = Booking::query()
            ->with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible());

        if ($filters['date_from'] !== '') {
            $baseQuery->whereDate('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $baseQuery->whereDate('date', '<=', $filters['date_to']);
        }

        if ($filters['room_id'] !== '') {
            $baseQuery->where('room_id', $filters['room_id']);
        }

        if ($filters['status'] !== '') {
            $baseQuery->where('status', $filters['status']);
        }

        return $baseQuery;
    }

    private function buildReportData(Collection $reportBookings): array
    {
        $stats = [
            'total' => $reportBookings->count(),
            'approved' => $reportBookings->where('status', 'approved')->count(),
            'pending' => $reportBookings->where('status', 'pending')->count(),
            'rejected' => $reportBookings->where('status', 'rejected')->count(),
            'cancelled' => $reportBookings->where('status', 'cancelled')->count(),
            'capacity_exceptions' => $reportBookings->filter(fn (Booking $booking) => $booking->requiresCapacityPermission())->count(),
        ];

        $roomBreakdown = $reportBookings
            ->groupBy(fn (Booking $booking) => $booking->room?->name ?? 'Unknown room')
            ->map(function ($group, $roomName) {
                return [
                    'room_name' => $roomName,
                    'bookings' => $group->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'pending' => $group->where('status', 'pending')->count(),
                    'capacity_exceptions' => $group->filter(fn (Booking $booking) => $booking->requiresCapacityPermission())->count(),
                ];
            })
            ->sortByDesc('bookings')
            ->values();

        $dailyBreakdown = $reportBookings
            ->groupBy(fn (Booking $booking) => $booking->date?->format('Y-m-d') ?? (string) $booking->date)
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'bookings' => $group->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'pending' => $group->where('status', 'pending')->count(),
                ];
            })
            ->sortBy('date')
            ->values();

        $topRequesters = $reportBookings
            ->groupBy('user_name')
            ->map(fn ($group, $userName) => [
                'user_name' => $userName ?: 'Unknown user',
                'bookings' => $group->count(),
            ])
            ->sortByDesc('bookings')
            ->take(5)
            ->values();

        return [
            'stats' => $stats,
            'roomBreakdown' => $roomBreakdown,
            'dailyBreakdown' => $dailyBreakdown,
            'topRequesters' => $topRequesters,
        ];
    }

    private function buildFilterSummary(array $filters): array
    {
        $roomLabel = 'All rooms';
        if ($filters['room_id'] !== '') {
            $roomLabel = (string) (Room::query()->visible()->whereKey($filters['room_id'])->value('name') ?? 'Selected room');
        }

        return [
            'Date From' => $this->formatFilterDate($filters['date_from']),
            'Date To' => $this->formatFilterDate($filters['date_to']),
            'Room' => $roomLabel,
            'Status' => $filters['status'] !== '' ? ucfirst($filters['status']) : 'All statuses',
        ];
    }

    private function formatFilterDate(string $value): string
    {
        if ($value === '') {
            return 'Any';
        }

        try {
            return Carbon::parse($value)->format('M d, Y');
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    private function exportReport(Builder $query, string $format, array $filters): StreamedResponse
    {
        $bookings = $query
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->get();

        $rows = $this->buildExportRows($bookings);
        $summaryStats = $this->buildExportStats($bookings);
        $reportData = $this->buildReportData($bookings);
        $roomBreakdownRows = $this->buildRoomBreakdownRows($reportData['roomBreakdown']);
        $topRequesterRows = $this->buildTopRequesterRows($reportData['topRequesters']);
        $dailyActivityRows = $this->buildDailyActivityRows($reportData['dailyBreakdown']);
        $filterSummary = $this->buildFilterSummary($filters);
        $generatedAt = now();
        $fileName = $this->buildExportFileName($format, $filters, $generatedAt);

        if ($format === 'xlsx') {
            return $this->exportXlsx(
                $rows,
                $filterSummary,
                $summaryStats,
                $roomBreakdownRows,
                $topRequesterRows,
                $dailyActivityRows,
                $generatedAt,
                $fileName,
            );
        }

        return $this->exportCsv(
            $rows,
            $filterSummary,
            $summaryStats,
            $roomBreakdownRows,
            $topRequesterRows,
            $dailyActivityRows,
            $generatedAt,
            $fileName,
        );
    }

    private function buildExportRows(Collection $bookings): array
    {
        return $bookings
            ->map(function (Booking $booking): array {
                return [
                    $booking->formatted_date,
                    $this->formatExportTime($booking->start_time),
                    $this->formatExportTime($booking->end_time),
                    (string) ($booking->room?->name ?? ''),
                    (string) ($booking->title ?? ''),
                    (string) ($booking->user_name ?? ''),
                    (string) ($booking->user_email ?? ''),
                    (string) $booking->attendees,
                    ucfirst((string) ($booking->status ?? '')),
                    $booking->requiresCapacityPermission() ? 'Yes' : 'No',
                ];
            })
            ->all();
    }

    private function buildExportStats(Collection $bookings): array
    {
        return [
            'Total Bookings' => $bookings->count(),
            'Approved' => $bookings->where('status', 'approved')->count(),
            'Pending' => $bookings->where('status', 'pending')->count(),
            'Rejected' => $bookings->where('status', 'rejected')->count(),
            'Cancelled' => $bookings->where('status', 'cancelled')->count(),
            'Needs Capacity Approval' => $bookings->filter(fn (Booking $booking) => $booking->requiresCapacityPermission())->count(),
        ];
    }

    private function buildRoomBreakdownRows(Collection $roomBreakdown): array
    {
        return $roomBreakdown
            ->map(fn (array $row): array => [
                (string) ($row['room_name'] ?? 'Unknown room'),
                (string) ($row['bookings'] ?? 0),
                (string) ($row['approved'] ?? 0),
                (string) ($row['pending'] ?? 0),
                (string) ($row['capacity_exceptions'] ?? 0),
            ])
            ->all();
    }

    private function buildTopRequesterRows(Collection $topRequesters): array
    {
        return $topRequesters
            ->map(fn (array $row): array => [
                (string) ($row['user_name'] ?? 'Unknown user'),
                (string) ($row['bookings'] ?? 0),
            ])
            ->all();
    }

    private function buildDailyActivityRows(Collection $dailyBreakdown): array
    {
        return $dailyBreakdown
            ->map(function (array $row): array {
                $rawDate = (string) ($row['date'] ?? '');
                $formattedDate = $rawDate;

                if ($rawDate !== '') {
                    try {
                        $formattedDate = Carbon::parse($rawDate)->format('M d, Y');
                    } catch (\Throwable $exception) {
                        $formattedDate = $rawDate;
                    }
                }

                return [
                    $formattedDate,
                    (string) ($row['bookings'] ?? 0),
                    (string) ($row['approved'] ?? 0),
                    (string) ($row['pending'] ?? 0),
                ];
            })
            ->all();
    }

    private function buildExportFileName(string $format, array $filters, Carbon $generatedAt): string
    {
        $scopeParts = [];

        if (($filters['date_from'] ?? '') !== '') {
            $scopeParts[] = 'from-' . $this->sanitizeExportFileFragment((string) $filters['date_from']);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $scopeParts[] = 'to-' . $this->sanitizeExportFileFragment((string) $filters['date_to']);
        }

        if (($filters['status'] ?? '') !== '') {
            $scopeParts[] = 'status-' . $this->sanitizeExportFileFragment((string) $filters['status']);
        }

        if (($filters['room_id'] ?? '') !== '') {
            $roomName = (string) (Room::query()->visible()->whereKey((string) $filters['room_id'])->value('name') ?? 'selected-room');
            $scopeParts[] = 'room-' . $this->sanitizeExportFileFragment($roomName);
        }

        if ($scopeParts === []) {
            $scopeParts[] = 'all-dates';
        }

        return sprintf(
            'detailed-booking-report_%s_%s.%s',
            implode('_', $scopeParts),
            $generatedAt->format('Ymd_His'),
            $format,
        );
    }

    private function sanitizeExportFileFragment(string $value): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9-]+/', '-', trim($value)) ?? '';
        $sanitized = trim($sanitized, '-');

        return $sanitized !== '' ? strtolower($sanitized) : 'custom';
    }

    private function formatExportTime(?string $timeValue): string
    {
        if (! $timeValue) {
            return '';
        }

        try {
            return Carbon::parse($timeValue)->format('g:i A');
        } catch (\Throwable $exception) {
            return (string) $timeValue;
        }
    }

    private function exportCsv(
        array $rows,
        array $filterSummary,
        array $summaryStats,
        array $roomBreakdownRows,
        array $topRequesterRows,
        array $dailyActivityRows,
        Carbon $generatedAt,
        string $fileName,
    ): StreamedResponse
    {
        return response()->streamDownload(function () use (
            $rows,
            $filterSummary,
            $summaryStats,
            $roomBreakdownRows,
            $topRequesterRows,
            $dailyActivityRows,
            $generatedAt,
        ) {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                throw new \RuntimeException('Unable to stream CSV export.');
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['SmartSpace Detailed Reports']);
            fputcsv($handle, ['Generated At', $generatedAt->copy()->format('M d, Y g:i A')]);
            fputcsv($handle, []);

            fputcsv($handle, ['Applied Filters']);
            foreach ($filterSummary as $label => $value) {
                fputcsv($handle, [(string) $label, (string) $value]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Booking Summary']);
            foreach ($summaryStats as $label => $value) {
                fputcsv($handle, [(string) $label, (string) $value]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Room Breakdown']);
            fputcsv($handle, ['Room', 'Total Bookings', 'Approved', 'Pending', 'Capacity Overrides']);
            if ($roomBreakdownRows === []) {
                fputcsv($handle, ['No room breakdown data found for current filters.']);
            } else {
                foreach ($roomBreakdownRows as $row) {
                    fputcsv($handle, $row);
                }
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Top Requesters']);
            fputcsv($handle, ['Requester', 'Total Bookings']);
            if ($topRequesterRows === []) {
                fputcsv($handle, ['No requester data found for current filters.']);
            } else {
                foreach ($topRequesterRows as $row) {
                    fputcsv($handle, $row);
                }
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Daily Activity']);
            fputcsv($handle, ['Date', 'Total Bookings', 'Approved', 'Pending']);
            if ($dailyActivityRows === []) {
                fputcsv($handle, ['No daily activity data found for current filters.']);
            } else {
                foreach ($dailyActivityRows as $row) {
                    fputcsv($handle, $row);
                }
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Detailed Booking Records']);
            fputcsv($handle, self::EXPORT_HEADERS);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportXlsx(
        array $rows,
        array $filterSummary,
        array $summaryStats,
        array $roomBreakdownRows,
        array $topRequesterRows,
        array $dailyActivityRows,
        Carbon $generatedAt,
        string $fileName,
    ): StreamedResponse
    {
        return response()->streamDownload(function () use (
            $rows,
            $filterSummary,
            $summaryStats,
            $roomBreakdownRows,
            $topRequesterRows,
            $dailyActivityRows,
            $generatedAt,
        ) {
            $tempFile = tempnam(sys_get_temp_dir(), 'report-xlsx-');
            if ($tempFile === false) {
                throw new \RuntimeException('Unable to prepare XLSX export file.');
            }

            $zip = new ZipArchive();
            if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                @unlink($tempFile);
                throw new \RuntimeException('Unable to create XLSX archive.');
            }

            $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
            $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
            $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
            $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSummaryWorksheetXml($filterSummary, $summaryStats, $generatedAt));
            $zip->addFromString('xl/worksheets/sheet2.xml', $this->xlsxRoomBreakdownWorksheetXml($roomBreakdownRows, $generatedAt));
            $zip->addFromString('xl/worksheets/sheet3.xml', $this->xlsxTopRequestersWorksheetXml($topRequesterRows, $generatedAt));
            $zip->addFromString('xl/worksheets/sheet4.xml', $this->xlsxDailyActivityWorksheetXml($dailyActivityRows, $generatedAt));
            $zip->addFromString('xl/worksheets/sheet5.xml', $this->xlsxBookingsWorksheetXml($rows, $generatedAt));
            $zip->close();

            $stream = fopen($tempFile, 'rb');
            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }

            @unlink($tempFile);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function xlsxContentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet5.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function xlsxRootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function xlsxWorkbookXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Summary" sheetId="1" r:id="rId1"/>
        <sheet name="Room Breakdown" sheetId="2" r:id="rId2"/>
        <sheet name="Top Requesters" sheetId="3" r:id="rId3"/>
        <sheet name="Daily Activity" sheetId="4" r:id="rId4"/>
        <sheet name="Detailed Bookings" sheetId="5" r:id="rId5"/>
    </sheets>
</workbook>
XML;
    }

    private function xlsxWorkbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>
    <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/>
    <Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet5.xml"/>
    <Relationship Id="rId6" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function xlsxStylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="5">
        <font>
            <sz val="11"/>
            <name val="Calibri"/>
            <family val="2"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <name val="Calibri"/>
            <family val="2"/>
            <color rgb="FFFFFFFF"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <name val="Calibri"/>
            <family val="2"/>
            <color rgb="FF0F172A"/>
        </font>
        <font>
            <b/>
            <sz val="14"/>
            <name val="Calibri"/>
            <family val="2"/>
            <color rgb="FF0F172A"/>
        </font>
        <font>
            <b/>
            <sz val="10"/>
            <name val="Calibri"/>
            <family val="2"/>
            <color rgb="FF0F172A"/>
        </font>
    </fonts>
    <fills count="9">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A8A"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFE0E7FF"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFD1D5DB"/></left>
            <right style="thin"><color rgb="FFD1D5DB"/></right>
            <top style="thin"><color rgb="FFD1D5DB"/></top>
            <bottom style="thin"><color rgb="FFD1D5DB"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="16">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center" wrapText="1"/>
        </xf>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment vertical="top"/>
        </xf>
        <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">
            <alignment vertical="top"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment vertical="top" wrapText="1"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">
            <alignment vertical="top" wrapText="1"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="4" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="4" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
    <cellStyles count="1">
        <cellStyle name="Normal" xfId="0" builtinId="0"/>
    </cellStyles>
</styleSheet>
XML;
    }

    private function xlsxSummaryWorksheetXml(array $filterSummary, array $summaryStats, Carbon $generatedAt): string
    {
        $columnWidths = [
            30,
            44,
        ];

        $colsXml = '';
        foreach ($columnWidths as $index => $width) {
            $column = $index + 1;
            $colsXml .= sprintf('<col min="%d" max="%d" width="%s" customWidth="1"/>', $column, $column, $width);
        }

        $sheetRowsXml = '';
        $currentRow = 1;

        $sheetRowsXml .= sprintf('<row r="%d" ht="24" customHeight="1">', $currentRow);
        $sheetRowsXml .= $this->xlsxCellXml('A1', 'SmartSpace Detailed Reports', 5);
        $sheetRowsXml .= '</row>';
        $currentRow++;

        $sheetRowsXml .= sprintf('<row r="%d">', $currentRow);
        $sheetRowsXml .= $this->xlsxCellXml('A2', 'Generated At', 3);
        $sheetRowsXml .= $this->xlsxCellXml('B2', $generatedAt->copy()->format('M d, Y g:i A'), 6);
        $sheetRowsXml .= '</row>';

        $currentRow += 2;
        $filterHeaderRow = $currentRow;

        $sheetRowsXml .= sprintf('<row r="%d">', $filterHeaderRow);
        $sheetRowsXml .= $this->xlsxCellXml('A' . $filterHeaderRow, 'Applied Filters', 2);
        $sheetRowsXml .= '</row>';
        $currentRow++;

        foreach ($filterSummary as $label => $value) {
            $sheetRowsXml .= sprintf('<row r="%d">', $currentRow);
            $sheetRowsXml .= $this->xlsxCellXml('A' . $currentRow, (string) $label, 3);
            $sheetRowsXml .= $this->xlsxCellXml('B' . $currentRow, (string) $value, 6);
            $sheetRowsXml .= '</row>';
            $currentRow++;
        }

        $currentRow++;
        $summaryHeaderRow = $currentRow;

        $sheetRowsXml .= sprintf('<row r="%d">', $summaryHeaderRow);
        $sheetRowsXml .= $this->xlsxCellXml('A' . $summaryHeaderRow, 'Booking Summary', 2);
        $sheetRowsXml .= '</row>';
        $currentRow++;

        foreach ($summaryStats as $label => $value) {
            $sheetRowsXml .= sprintf('<row r="%d">', $currentRow);
            $sheetRowsXml .= $this->xlsxCellXml('A' . $currentRow, (string) $label, 3);
            $sheetRowsXml .= $this->xlsxCellXml('B' . $currentRow, (string) $value, 6);
            $sheetRowsXml .= '</row>';
            $currentRow++;
        }

        $currentRow++;
        $sectionsHeaderRow = $currentRow;

        $sheetRowsXml .= sprintf('<row r="%d">', $sectionsHeaderRow);
        $sheetRowsXml .= $this->xlsxCellXml('A' . $sectionsHeaderRow, 'Included Sections', 2);
        $sheetRowsXml .= '</row>';
        $currentRow++;

        $sections = [
            ['Room Breakdown', 'Sheet: Room Breakdown'],
            ['Top Requesters', 'Sheet: Top Requesters'],
            ['Daily Activity', 'Sheet: Daily Activity'],
            ['Detailed List', 'Sheet: Detailed Bookings'],
        ];

        foreach ($sections as [$label, $value]) {
            $sheetRowsXml .= sprintf('<row r="%d">', $currentRow);
            $sheetRowsXml .= $this->xlsxCellXml('A' . $currentRow, (string) $label, 3);
            $sheetRowsXml .= $this->xlsxCellXml('B' . $currentRow, (string) $value, 6);
            $sheetRowsXml .= '</row>';
            $currentRow++;
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>%s</cols>'
            . '<sheetData>%s</sheetData>'
            . '<mergeCells count="4">'
            . '<mergeCell ref="A1:B1"/>'
            . '<mergeCell ref="A%d:B%d"/>'
            . '<mergeCell ref="A%d:B%d"/>'
            . '<mergeCell ref="A%d:B%d"/>'
            . '</mergeCells>'
            . '</worksheet>',
            $colsXml,
            $sheetRowsXml,
            $filterHeaderRow,
            $filterHeaderRow,
            $summaryHeaderRow,
            $summaryHeaderRow,
            $sectionsHeaderRow,
            $sectionsHeaderRow,
        );
    }

    private function xlsxRoomBreakdownWorksheetXml(array $rows, Carbon $generatedAt): string
    {
        return $this->xlsxTableWorksheetXml(
            'Room Breakdown',
            ['Room', 'Total Bookings', 'Approved', 'Pending', 'Capacity Overrides'],
            $rows,
            $generatedAt,
            [32, 16, 12, 12, 20],
            [1],
            [2, 3, 4, 5],
        );
    }

    private function xlsxTopRequestersWorksheetXml(array $rows, Carbon $generatedAt): string
    {
        return $this->xlsxTableWorksheetXml(
            'Top Requesters',
            ['Requester', 'Total Bookings'],
            $rows,
            $generatedAt,
            [40, 18],
            [1],
            [2],
        );
    }

    private function xlsxDailyActivityWorksheetXml(array $rows, Carbon $generatedAt): string
    {
        return $this->xlsxTableWorksheetXml(
            'Daily Activity',
            ['Date', 'Total Bookings', 'Approved', 'Pending'],
            $rows,
            $generatedAt,
            [22, 16, 12, 12],
            [1],
            [2, 3, 4],
        );
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string>> $rows
     * @param array<int, float|int> $columnWidths
     * @param array<int, int> $wrapColumns
     * @param array<int, int> $centerColumns
     */
    private function xlsxTableWorksheetXml(
        string $title,
        array $headers,
        array $rows,
        Carbon $generatedAt,
        array $columnWidths,
        array $wrapColumns = [],
        array $centerColumns = [],
    ): string {
        $colsXml = '';
        foreach ($columnWidths as $index => $width) {
            $column = $index + 1;
            $colsXml .= sprintf('<col min="%d" max="%d" width="%s" customWidth="1"/>', $column, $column, $width);
        }

        $sheetRowsXml = '';
        $headerRow = 4;
        $dataStartRow = 5;
        $lastColumn = $this->xlsxColumnLabel(count($headers));

        $sheetRowsXml .= '<row r="1" ht="26" customHeight="1">';
        $sheetRowsXml .= $this->xlsxCellXml('A1', $title, 5);
        $sheetRowsXml .= '</row>';

        $sheetRowsXml .= '<row r="2" ht="20" customHeight="1">';
        $sheetRowsXml .= $this->xlsxCellXml('A2', 'Generated At', 3);
        $sheetRowsXml .= $this->xlsxCellXml('B2', $generatedAt->copy()->format('M d, Y g:i A'), 6);
        $sheetRowsXml .= '</row>';

        $sheetRowsXml .= '<row r="3"/>';

        $sheetRowsXml .= sprintf('<row r="%d" ht="24" customHeight="1">', $headerRow);
        foreach ($headers as $columnIndex => $header) {
            $cellRef = $this->xlsxColumnLabel($columnIndex + 1) . $headerRow;
            $sheetRowsXml .= $this->xlsxCellXml($cellRef, (string) $header, 1);
        }
        $sheetRowsXml .= '</row>';

        if ($rows === []) {
            $rows = [[
                'No data available for selected filters.',
            ]];
        }

        foreach ($rows as $rowIndex => $rowValues) {
            $excelRow = $dataStartRow + $rowIndex;
            $isAltRow = $rowIndex % 2 === 0;
            $sheetRowsXml .= sprintf('<row r="%d" ht="22" customHeight="1">', $excelRow);

            foreach ($headers as $columnIndex => $_header) {
                $columnNumber = $columnIndex + 1;
                $cellRef = $this->xlsxColumnLabel($columnNumber) . $excelRow;
                $value = (string) ($rowValues[$columnIndex] ?? '');
                $styleId = $isAltRow ? 4 : 6;

                if (in_array($columnNumber, $wrapColumns, true)) {
                    $styleId = $isAltRow ? 7 : 8;
                } elseif (in_array($columnNumber, $centerColumns, true)) {
                    $styleId = $isAltRow ? 9 : 10;
                }

                $sheetRowsXml .= $this->xlsxCellXml($cellRef, $value, $styleId);
            }

            $sheetRowsXml .= '</row>';
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="%d" topLeftCell="A%d" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>'
            . '<cols>%s</cols>'
            . '<sheetData>%s</sheetData>'
            . '<mergeCells count="1"><mergeCell ref="A1:%s1"/></mergeCells>'
            . '<autoFilter ref="A%d:%s%d"/>'
            . '</worksheet>',
            $headerRow,
            $dataStartRow,
            $colsXml,
            $sheetRowsXml,
            $lastColumn,
            $headerRow,
            $lastColumn,
            $headerRow,
        );
    }

    private function xlsxBookingsWorksheetXml(array $rows, Carbon $generatedAt): string
    {
        $columnWidths = [
            15,
            10,
            10,
            24,
            36,
            23,
            34,
            11,
            15,
            22,
        ];

        $colsXml = '';
        foreach ($columnWidths as $index => $width) {
            $column = $index + 1;
            $colsXml .= sprintf('<col min="%d" max="%d" width="%s" customWidth="1"/>', $column, $column, $width);
        }

        $sheetRowsXml = '';

        $headerRow = 4;
        $dataStartRow = 5;
        $lastColumn = $this->xlsxColumnLabel(count(self::EXPORT_HEADERS));

        $sheetRowsXml .= '<row r="1" ht="26" customHeight="1">';
        $sheetRowsXml .= $this->xlsxCellXml('A1', 'Detailed Booking Records', 5);
        $sheetRowsXml .= '</row>';

        $sheetRowsXml .= '<row r="2" ht="20" customHeight="1">';
        $sheetRowsXml .= $this->xlsxCellXml('A2', 'Generated At', 3);
        $sheetRowsXml .= $this->xlsxCellXml('B2', $generatedAt->copy()->format('M d, Y g:i A'), 6);
        $sheetRowsXml .= '</row>';

        $sheetRowsXml .= '<row r="3"/>';

        $sheetRowsXml .= sprintf('<row r="%d" ht="24" customHeight="1">', $headerRow);
        foreach (self::EXPORT_HEADERS as $columnIndex => $header) {
            $cellRef = $this->xlsxColumnLabel($columnIndex + 1) . $headerRow;
            $sheetRowsXml .= $this->xlsxCellXml($cellRef, (string) $header, 1);
        }
        $sheetRowsXml .= '</row>';

        foreach ($rows as $rowIndex => $rowValues) {
            $excelRow = $dataStartRow + $rowIndex;
            $isAltRow = $rowIndex % 2 === 0;
            $sheetRowsXml .= sprintf('<row r="%d" ht="22" customHeight="1">', $excelRow);

            foreach ($rowValues as $columnIndex => $value) {
                $columnNumber = $columnIndex + 1;
                $cellRef = $this->xlsxColumnLabel($columnNumber) . $excelRow;
                $styleId = $isAltRow ? 4 : 6;

                if (in_array($columnNumber, [4, 5, 6, 7, 10], true)) {
                    $styleId = $isAltRow ? 7 : 8;
                } elseif ($columnNumber === 8) {
                    $styleId = $isAltRow ? 9 : 10;
                } elseif ($columnNumber === 9) {
                    $normalizedStatus = strtolower(trim((string) $value));
                    $styleId = match ($normalizedStatus) {
                        'approved' => 11,
                        'pending' => 12,
                        'rejected' => 13,
                        'cancelled' => 14,
                        default => 15,
                    };
                }

                $sheetRowsXml .= $this->xlsxCellXml($cellRef, (string) $value, $styleId);
            }

            $sheetRowsXml .= '</row>';
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="%d" topLeftCell="A%d" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>'
            . '<cols>%s</cols>'
            . '<sheetData>%s</sheetData>'
            . '<mergeCells count="1"><mergeCell ref="A1:%s1"/></mergeCells>'
            . '<autoFilter ref="A%d:%s%d"/>'
            . '</worksheet>',
            $headerRow,
            $dataStartRow,
            $colsXml,
            $sheetRowsXml,
            $lastColumn,
            $headerRow,
            $lastColumn,
            $headerRow,
        );
    }

    private function xlsxCellXml(string $cellRef, string $value, int $styleId = 0): string
    {
        $styleAttribute = $styleId > 0 ? sprintf(' s="%d"', $styleId) : '';

        return sprintf(
            '<c r="%s" t="inlineStr"%s><is><t xml:space="preserve">%s</t></is></c>',
            $cellRef,
            $styleAttribute,
            $this->xlsxEscape($value),
        );
    }

    private function xlsxColumnLabel(int $column): string
    {
        $label = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $label = chr(65 + $remainder) . $label;
            $column = (int) floor(($column - $remainder - 1) / 26);
        }

        return $label;
    }

    private function xlsxEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}