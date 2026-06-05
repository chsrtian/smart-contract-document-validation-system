<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffAnalyticsController extends Controller
{
    /**
     * Document type → human label mapping.
     */
    private const TYPE_LABELS = [
        'birth_certificate'    => 'Birth Certificate',
        'death_certificate'    => 'Death Certificate',
        'marriage_certificate' => 'Marriage Certificate',
        'admission_of_paternity' => 'Admission of Paternity',
        'ausf' => 'AUSF',
        'legitimation' => 'Legitimation',
        'affidavit_of_reappearance' => 'Affidavit of Reappearance',
        'marriage_settlement' => 'Marriage Settlement',
        'parental_authorization_ai' => 'Parental Authorization / AI Ratification',
        'late_registration' => 'Late Registration',
        'supplemental_report' => 'Supplemental Report',
        'certificate_of_foundling' => 'Certificate of Foundling',
        'adoption_document' => 'Adoption Document',
        'judicial_correction_rule_108' => 'Judicial Correction (Rule 108)',
        'annulment_or_nullity' => 'Annulment / Nullity',
        'recognition_of_foreign_divorce' => 'Recognition of Foreign Divorce',
        'marriage_license' => 'Marriage License',
        'certificate_legal_capacity_to_marry' => 'Certificate of Legal Capacity to Marry',
        'cenomar'              => 'CENOMAR',
        'affidavit'            => 'Affidavit',
        'court_document'       => 'Court Document',
        'contract'             => 'Contract',
        'other'                => 'Other',
    ];

    /**
     * Show the analytics page.
     */
    public function index()
    {
        // Provide initial summary counts so the page can render server-side cards.
        $totals = Scan::select('document_type', DB::raw('COUNT(*) as total'))
            ->groupBy('document_type')
            ->pluck('total', 'document_type')
            ->toArray();

        $summary = [
            'birth'    => $totals['birth_certificate'] ?? 0,
            'death'    => $totals['death_certificate'] ?? 0,
            'marriage' => $totals['marriage_certificate'] ?? 0,
            'others'   => collect($totals)->except([
                'birth_certificate',
                'death_certificate',
                'marriage_certificate',
            ])->sum(),
            'total'    => array_sum($totals),
        ];

        return view('staff.analytics', compact('summary'));
    }

    /**
     * Return chart data as JSON.
     *
     * Query params:
     *   section  = overall | birth | death | marriage | others
     *   period   = day | week | month | year
     */
    public function chartData(Request $request)
    {
        $request->validate([
            'section' => 'required|in:overall,birth,death,marriage,others',
            'period'  => 'required|in:day,week,month,year',
        ]);

        $section = $request->input('section');
        $period  = $request->input('period');

        try {
            $data = $this->buildChartPayload($section, $period);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('Staff analytics chart-data error', [
                'section' => $section,
                'period'  => $period,
                'error'   => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Unable to fetch analytics data.'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Private helpers                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Build the full JSON payload for a given section + period.
     */
    private function buildChartPayload(string $section, string $period): array
    {
        // Determine which document types to include.
        $typeFilter = $this->typesForSection($section);

        // Date boundaries for the chosen period.
        [$start, $end, $groupFormat, $labelFormat] = $this->periodBoundaries($period);

        // Base query – only processed documents (not drafts).
        $query = Scan::whereBetween('created_at', [$start, $end])
            ->whereIn('document_type', $typeFilter);

        // ----- Time-series (bar / line / area) -----
        $timeSeries = $this->buildTimeSeries(clone $query, $period, $groupFormat, $labelFormat, $section);

        // ----- Distribution (pie / donut) -----
        $distribution = $this->buildDistribution(clone $query);

        // ----- Summary numbers -----
        $totalCount  = (clone $query)->count();
        $avgConfidence = round((clone $query)->avg('ocr_confidence') ?? 0, 1);

        return [
            'timeSeries'   => $timeSeries,
            'distribution' => $distribution,
            'totalCount'   => $totalCount,
            'avgConfidence' => $avgConfidence,
            'period'       => $period,
            'section'      => $section,
        ];
    }

    /**
     * Return an array of document_type values that belong to $section.
     */
    private function typesForSection(string $section): array
    {
        $coreTypes = ['birth_certificate', 'death_certificate', 'marriage_certificate'];

        return match ($section) {
            'birth'    => ['birth_certificate'],
            'death'    => ['death_certificate'],
            'marriage' => ['marriage_certificate'],
            'others'   => array_values(array_diff(array_keys(self::TYPE_LABELS), $coreTypes)),
            default    => array_keys(self::TYPE_LABELS),    // overall
        };
    }

    /**
     * Calculate start, end, SQL group-format, and label format strings.
     *
     * @return array [Carbon $start, Carbon $end, string $sqlFormat, string $phpLabel]
     */
    private function periodBoundaries(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'day' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                '%Y-%m-%d',
                'M d',
            ],
            'week' => [
                $now->copy()->subWeeks(11)->startOfWeek(),
                $now->copy()->endOfWeek(),
                '%x-W%v',           // ISO year + ISO week
                'W',                // we post-process this
            ],
            'month' => [
                $now->copy()->subMonths(11)->startOfMonth(),
                $now->copy()->endOfMonth(),
                '%Y-%m',
                'M Y',
            ],
            'year' => [
                $now->copy()->subYears(4)->startOfYear(),
                $now->copy()->endOfYear(),
                '%Y',
                'Y',
            ],
        };
    }

    /**
     * Build time-series datasets suitable for Chart.js (bar / line / area).
     */
    private function buildTimeSeries($query, string $period, string $groupFormat, string $labelFormat, string $section): array
    {
        // When section = overall we want one dataset per main type.
        // Otherwise a single dataset for the section.

        $typesInSection = $this->typesForSection($section);
        $isOverall = $section === 'overall';

        // Fetch raw grouped rows.
        $rows = (clone $query)
            ->select('document_type', DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as period_key"), DB::raw('COUNT(*) as cnt'))
            ->groupBy('document_type', 'period_key')
            ->orderBy('period_key')
            ->get();

        // Group types into "datasets". For overall: each main type is a dataset.
        // For a single category: treat all included types as one dataset.
        if ($isOverall) {
            $datasetDefs = [
                'birth_certificate'    => ['Birth Certificate', '#3b82f6'],
                'death_certificate'    => ['Death Certificate', '#6b7280'],
                'marriage_certificate' => ['Marriage Certificate', '#ec4899'],
                '_others'              => ['Others', '#f97316'],
            ];
        } elseif ($section === 'others') {
            // For Other Documents, render one series per actual type present in the queried period.
            // This keeps bar/area behavior consistent with the donut distribution source.
            $datasetDefs = [];
            $rowTypes = $rows->pluck('document_type')
                ->unique()
                ->sort(function ($left, $right) {
                    $leftLabel = self::TYPE_LABELS[$left] ?? ucfirst(str_replace('_', ' ', $left));
                    $rightLabel = self::TYPE_LABELS[$right] ?? ucfirst(str_replace('_', ' ', $right));

                    return strcmp($leftLabel, $rightLabel);
                })
                ->values();

            foreach ($rowTypes as $type) {
                $datasetDefs[$type] = [
                    self::TYPE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type)),
                    $this->colorForType($type),
                ];
            }

            if (empty($datasetDefs)) {
                $datasetDefs = [
                    'others' => ['Other Documents', $this->colorForSection($section)],
                ];
            }
        } else {
            $datasetDefs = [
                $section => [self::TYPE_LABELS[$typesInSection[0]] ?? ucfirst($section), $this->colorForSection($section)],
            ];
        }

        // Build an ordered set of labels.
        $labels = $this->generateLabels($period, $groupFormat, $labelFormat);

        // Pivot rows into datasets.
        $datasets = [];
        foreach ($datasetDefs as $defKey => [$label, $color]) {
            $map = [];
            foreach ($rows as $row) {
                $belongs = false;
                if ($defKey === '_others') {
                    $belongs = !in_array($row->document_type, ['birth_certificate', 'death_certificate', 'marriage_certificate']);
                } elseif ($isOverall || $section === 'others') {
                    $belongs = $row->document_type === $defKey;
                } else {
                    $belongs = in_array($row->document_type, $typesInSection, true);
                }

                if ($belongs) {
                    $map[$row->period_key] = ($map[$row->period_key] ?? 0) + $row->cnt;
                }
            }

            // Align with $labels keys.
            $values = [];
            foreach ($labels as $key => $displayLabel) {
                $values[] = $map[$key] ?? 0;
            }

            $datasets[] = [
                'label'           => $label,
                'data'            => $values,
                'backgroundColor' => $color . '33',   // 20% alpha for area/bar
                'borderColor'     => $color,
                'borderWidth'     => 2,
                'tension'         => 0.3,
                'fill'            => true,
            ];
        }

        return [
            'labels'   => array_values($labels),
            'datasets' => $datasets,
        ];
    }

    /**
     * Build distribution data for pie/donut.
     */
    private function buildDistribution($query): array
    {
        $rows = (clone $query)
            ->select('document_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('document_type')
            ->orderByDesc('cnt')
            ->get();

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($rows as $row) {
            $labels[] = self::TYPE_LABELS[$row->document_type] ?? ucfirst($row->document_type);
            $values[] = $row->cnt;
            $colors[] = $this->colorForType($row->document_type);
        }

        return [
            'labels'           => $labels,
            'data'             => $values,
            'backgroundColor'  => $colors,
        ];
    }

    /**
     * Generate ordered label map  [periodKey => displayLabel]
     */
    private function generateLabels(string $period, string $groupFormat, string $labelFormat): array
    {
        $labels = [];
        $now = Carbon::now();

        switch ($period) {
            case 'day':
                for ($i = 29; $i >= 0; $i--) {
                    $d = $now->copy()->subDays($i);
                    $labels[$d->format('Y-m-d')] = $d->format('M d');
                }
                break;

            case 'week':
                for ($i = 11; $i >= 0; $i--) {
                    $d = $now->copy()->subWeeks($i)->startOfWeek();
                    $key = $d->format('o') . '-W' . str_pad($d->isoWeek(), 2, '0', STR_PAD_LEFT);
                    $labels[$key] = 'W' . $d->isoWeek();
                }
                break;

            case 'month':
                for ($i = 11; $i >= 0; $i--) {
                    $d = $now->copy()->subMonths($i)->startOfMonth();
                    $labels[$d->format('Y-m')] = $d->format('M Y');
                }
                break;

            case 'year':
                for ($i = 4; $i >= 0; $i--) {
                    $d = $now->copy()->subYears($i)->startOfYear();
                    $labels[$d->format('Y')] = $d->format('Y');
                }
                break;
        }

        return $labels;
    }

    /**
     * Main color for a document type.
     */
    private function colorForType(string $type): string
    {
        return match ($type) {
            'birth_certificate'    => '#3b82f6',
            'death_certificate'    => '#6b7280',
            'marriage_certificate' => '#ec4899',
            'admission_of_paternity' => '#0ea5e9',
            'ausf' => '#4f46e5',
            'legitimation' => '#6366f1',
            'affidavit_of_reappearance' => '#06b6d4',
            'marriage_settlement' => '#8b5cf6',
            'parental_authorization_ai' => '#14b8a6',
            'late_registration' => '#f59e0b',
            'supplemental_report' => '#22c55e',
            'certificate_of_foundling' => '#10b981',
            'adoption_document' => '#84cc16',
            'judicial_correction_rule_108' => '#f97316',
            'annulment_or_nullity' => '#ef4444',
            'recognition_of_foreign_divorce' => '#dc2626',
            'marriage_license' => '#f43f5e',
            'certificate_legal_capacity_to_marry' => '#e11d48',
            'cenomar'              => '#f97316',
            'affidavit'            => '#8b5cf6',
            'court_document'       => '#14b8a6',
            'contract'             => '#eab308',
            default                => '#a3a3a3',
        };
    }

    /**
     * Color for a section name.
     */
    private function colorForSection(string $section): string
    {
        return match ($section) {
            'birth'    => '#3b82f6',
            'death'    => '#6b7280',
            'marriage' => '#ec4899',
            'others'   => '#f97316',
            default    => '#3b82f6',
        };
    }
}
