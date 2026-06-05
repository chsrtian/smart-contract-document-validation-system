<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StaffReportsController extends Controller
{
    private const DEFAULT_STATUSES = ['pending', 'completed', 'rejected'];

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $reportData = $this->buildReportData($filters, true);

        return view('staff.reports.index', [
            'filters' => $filters,
            'reportData' => $reportData,
            'documentTypes' => Scan::query()
                ->select('document_type')
                ->distinct()
                ->orderBy('document_type')
                ->pluck('document_type')
                ->toArray(),
            'headerMeta' => $this->headerMeta(),
            'statusLabels' => $this->statusLabels(),
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $reportData = $this->buildReportData($filters, false);

        $pdf = Pdf::loadView('staff.reports.pdf', [
            'filters' => $filters,
            'reportData' => $reportData,
            'headerMeta' => $this->headerMeta(),
            'statusLabels' => $this->statusLabels(),
            'typeLabels' => $this->typeLabels(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('lcro_staff_report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $reportData = $this->buildReportData($filters, false);

        return view('staff.reports.print', [
            'filters' => $filters,
            'reportData' => $reportData,
            'headerMeta' => $this->headerMeta(),
            'statusLabels' => $this->statusLabels(),
            'typeLabels' => $this->typeLabels(),
            'generatedAt' => now(),
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'document_type' => 'nullable|string|max:100',
            'verification_status' => 'nullable|in:pending,completed,rejected',
        ]);

        return [
            'date_from' => $validated['date_from'] ?? now()->startOfMonth()->toDateString(),
            'date_to' => $validated['date_to'] ?? now()->toDateString(),
            'document_type' => $validated['document_type'] ?? null,
            'verification_status' => $validated['verification_status'] ?? null,
        ];
    }

    private function buildReportData(array $filters, bool $paginate): array
    {
        $base = $this->buildBaseQuery($filters);

        $pivotRows = (clone $base)
            ->selectRaw('document_type, verification_status, COUNT(*) as total')
            ->groupBy('document_type', 'verification_status')
            ->get();

        $summaryTable = [];
        foreach ($pivotRows as $row) {
            $type = $row->document_type;
            if (!isset($summaryTable[$type])) {
                $summaryTable[$type] = [
                    'pending' => 0,
                    'completed' => 0,
                    'rejected' => 0,
                    'total' => 0,
                ];
            }

            if (isset($summaryTable[$type][$row->verification_status])) {
                $summaryTable[$type][$row->verification_status] = (int) $row->total;
                $summaryTable[$type]['total'] += (int) $row->total;
            }
        }

        ksort($summaryTable);

        $grandTotal = [
            'pending' => 0,
            'completed' => 0,
            'rejected' => 0,
            'total' => 0,
        ];

        foreach ($summaryTable as $row) {
            $grandTotal['pending'] += $row['pending'];
            $grandTotal['completed'] += $row['completed'];
            $grandTotal['rejected'] += $row['rejected'];
            $grandTotal['total'] += $row['total'];
        }

        $detailBase = (clone $base)
            ->with('processedBy:id,name')
            ->select([
                'id',
                'document_id',
                'document_type',
                'verification_status',
                'created_at',
                'processed_at',
                'processed_by',
                'extracted_fields',
                'notes',
            ])
            ->orderByDesc('created_at');

        $records = $paginate
            ? $detailBase->paginate(25)->withQueryString()
            : $detailBase->get();

        $records = $this->mapReportRows($records);

        return [
            'summary_table' => $summaryTable,
            'grand_total' => $grandTotal,
            'records' => $records,
        ];
    }

    private function buildBaseQuery(array $filters): Builder
    {
        return Scan::query()
            ->when($filters['date_from'], function (Builder $query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'], function (Builder $query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($filters['document_type'], function (Builder $query, $documentType) {
                $query->where('document_type', $documentType);
            })
            ->when($filters['verification_status'], function (Builder $query, $status) {
                $query->where('verification_status', $status);
            }, function (Builder $query) {
                $query->whereIn('verification_status', self::DEFAULT_STATUSES);
            });
    }

    private function mapReportRows($records)
    {
        if ($records instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $records->getCollection()->transform(function ($scan) {
                return $this->mapSingleRow($scan);
            });

            return $records;
        }

        return $records->map(function ($scan) {
            return $this->mapSingleRow($scan);
        });
    }

    private function mapSingleRow($scan): array
    {
        $fields = $scan->extracted_fields;
        if (is_string($fields)) {
            $fields = json_decode($fields, true) ?? [];
        }
        if (!is_array($fields)) {
            $fields = [];
        }

        return [
            'registry_no' => $scan->document_id ?: ('SCAN-' . $scan->id),
            'document_type' => $scan->document_type,
            'name_or_parties' => $this->extractNameOrParties($scan->document_type, $fields),
            'date_received' => optional($scan->created_at)->format('M d, Y h:i A'),
            'date_processed' => optional($scan->processed_at)->format('M d, Y h:i A') ?? 'Not processed',
            'status' => $scan->verification_status,
            'remarks' => $scan->notes ?: 'N/A',
            'processed_by' => optional($scan->processedBy)->name ?: 'System',
        ];
    }

    private function extractNameOrParties(string $documentType, array $fields): string
    {
        if ($documentType === 'marriage_certificate') {
            $groom = $this->buildName($fields, ['groom_first_name', 'groom_middle_name', 'groom_last_name']);
            $bride = $this->buildName($fields, ['bride_first_name', 'bride_middle_name', 'bride_last_name']);

            if (!$groom) {
                $groom = $this->buildName($fields, ['husband_first_name', 'husband_middle_name', 'husband_last_name']);
            }
            if (!$bride) {
                $bride = $this->buildName($fields, ['wife_first_name', 'wife_middle_name', 'wife_last_name']);
            }

            if ($groom && $bride) {
                return $groom . ' & ' . $bride;
            }
            if ($groom) {
                return $groom;
            }
            if ($bride) {
                return $bride;
            }
        }

        $candidates = [
            ['name_first', 'name_middle', 'name_last'],
            ['child_first_name', 'child_middle_name', 'child_last_name'],
            ['deceased_first_name', 'deceased_middle_name', 'deceased_last_name'],
            ['person_first_name', 'person_middle_name', 'person_last_name'],
        ];

        foreach ($candidates as $keys) {
            $full = $this->buildName($fields, $keys);
            if ($full) {
                return $full;
            }
        }

        foreach (['full_name', 'name', 'owner_name', 'child_name', 'deceased_name'] as $key) {
            if (!empty($fields[$key])) {
                return (string) $fields[$key];
            }
        }

        return 'Name not available';
    }

    private function buildName(array $fields, array $keys): ?string
    {
        $parts = [];
        foreach ($keys as $key) {
            $value = trim((string) ($fields[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        if (empty($parts)) {
            return null;
        }

        return implode(' ', $parts);
    }

    private function typeLabels(): array
    {
        return [
            'birth_certificate' => 'Birth Certificate',
            'death_certificate' => 'Death Certificate',
            'marriage_certificate' => 'Marriage Certificate',
            'cenomar' => 'CENOMAR',
            'affidavit' => 'Affidavit',
            'court_document' => 'Court Document',
            'contract' => 'Contract',
            'other' => 'Other',
        ];
    }

    private function statusLabels(): array
    {
        return [
            'pending' => 'Pending Review',
            'completed' => 'Validated',
            'rejected' => 'Rejected',
        ];
    }

    private function headerMeta(): array
    {
        return [
            // These lines are intentionally centralized for easy wording updates.
            'office_line_1' => 'Municipality of Magallanes',
            'office_line_2' => 'Local Civil Registrar Office',
            'office_line_3' => 'Agusan del Norte',
            'report_title' => 'LCRO Staff Analytics and Operational Report',
            'logo_url' => asset('images/mags.jpg'),
            'logo_pdf_path' => public_path('images/mags.jpg'),
        ];
    }
}
