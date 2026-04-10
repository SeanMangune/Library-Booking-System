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
        'Booking Date',
        'Start Time',
        'End Time',
        'Room',
        'Purpose',
        'Requester',
        'Requester Email',
        'Attendees',
        'Status',
        'Needs Capacity Approval',
    ];

    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $baseQuery = $this->buildBaseQuery($filters);

        $exportFormat = strtolower(trim((string) $request->input('export', '')));
        if (in_array($exportFormat, ['csv', 'xlsx'], true)) {
            return $this->exportReport(clone $baseQuery, $exportFormat);
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

    private function exportReport(Builder $query, string $format): StreamedResponse
    {
        $bookings = $query
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->get();

        $rows = $this->buildExportRows($bookings);

        if ($format === 'xlsx') {
            return $this->exportXlsx($rows);
        }

        return $this->exportCsv($rows);
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

    private function exportCsv(array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::EXPORT_HEADERS);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'booking-report.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportXlsx(array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
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
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxWorksheetXml($rows));
            $zip->close();

            $stream = fopen($tempFile, 'rb');
            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }

            @unlink($tempFile);
        }, 'booking-report.xlsx', [
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
        <sheet name="Detailed Reports" sheetId="1" r:id="rId1"/>
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
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function xlsxStylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
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
        </font>
    </fonts>
    <fills count="2">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    </cellXfs>
    <cellStyles count="1">
        <cellStyle name="Normal" xfId="0" builtinId="0"/>
    </cellStyles>
</styleSheet>
XML;
    }

    private function xlsxWorksheetXml(array $rows): string
    {
        $allRows = array_merge([self::EXPORT_HEADERS], $rows);

        $columnWidths = [
            16.5,
            12,
            12,
            24,
            34,
            24,
            30,
            10,
            14,
            22,
        ];

        $colsXml = '';
        foreach ($columnWidths as $index => $width) {
            $column = $index + 1;
            $colsXml .= sprintf('<col min="%d" max="%d" width="%s" customWidth="1"/>', $column, $column, $width);
        }

        $sheetRowsXml = '';
        foreach ($allRows as $rowIndex => $rowValues) {
            $excelRow = $rowIndex + 1;
            $sheetRowsXml .= sprintf('<row r="%d">', $excelRow);

            foreach ($rowValues as $columnIndex => $value) {
                $cellRef = $this->xlsxColumnLabel($columnIndex + 1) . $excelRow;
                $safeValue = $this->xlsxEscape((string) $value);
                $headerStyle = $excelRow === 1 ? ' s="1"' : '';

                $sheetRowsXml .= sprintf(
                    '<c r="%s" t="inlineStr"%s><is><t xml:space="preserve">%s</t></is></c>',
                    $cellRef,
                    $headerStyle,
                    $safeValue,
                );
            }

            $sheetRowsXml .= '</row>';
        }

        $lastColumn = $this->xlsxColumnLabel(count(self::EXPORT_HEADERS));

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>%s</cols>'
            . '<sheetData>%s</sheetData>'
            . '<autoFilter ref="A1:%s1"/>'
            . '</worksheet>',
            $colsXml,
            $sheetRowsXml,
            $lastColumn,
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