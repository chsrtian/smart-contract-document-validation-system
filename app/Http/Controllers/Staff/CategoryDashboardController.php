<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CategoryDashboardController
 * 
 * Handles the category-specific dashboard sub-pages for the Hybrid Layout.
 * Each category (Birth, Death, Marriage, CENOMAR) has its own dedicated page
 * with status cards, charts, and a master document list.
 */
class CategoryDashboardController extends Controller
{
    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Birth Certificate Dashboard
     */
    public function birth(Request $request)
    {
        return $this->renderCategoryDashboard('birth_certificate', 'Birth Certificate', $request);
    }

    /**
     * Death Certificate Dashboard
     */
    public function death(Request $request)
    {
        return $this->renderCategoryDashboard('death_certificate', 'Death Certificate', $request);
    }

    /**
     * Marriage Certificate Dashboard
     */
    public function marriage(Request $request)
    {
        return $this->renderCategoryDashboard('marriage_certificate', 'Marriage Certificate', $request);
    }

    /**
     * other docs
     */
    public function others(Request $request)
    {
        return $this->renderCategoryDashboard('others', 'Other Documents', $request);
    }

    /**
     * Render a category dashboard with all required data
     * 
     * @param string $category - The document category key
     * @param string $categoryName - Human-readable category name
     * @param Request $request - HTTP request for filters
     * @return \Illuminate\View\View
     */
    private function renderCategoryDashboard($category, $categoryName, Request $request)
    {
        try {
            Log::info('CategoryDashboardController: Rendering category dashboard', [
                'category' => $category,
                'filters' => $request->all()
            ]);

            // Get category-specific statistics (Pending, Validated, Released)
            $stats = $this->documentService->getCategoryStatistics($category);

            // Get chart data (default: This Week)
            $period = $request->get('period', 'this_week');
            $chartData = $this->documentService->getCategoryChartData($category, $period);

            // Get documents for master list table with filters
            $filters = [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];
            $documents = $this->documentService->getCategoryDocuments($category, $filters, 15);

            // Category-specific styling configuration
            $categoryConfig = $this->getCategoryConfig($category);

            return view('staff.categories.index', [
                'category' => $category,
                'categoryName' => $categoryName,
                'categoryConfig' => $categoryConfig,
                'stats' => $stats,
                'chartData' => $chartData,
                'documents' => $documents,
                'currentPeriod' => $period,
                'filters' => $filters,
            ]);

        } catch (\Exception $e) {
            Log::error('CategoryDashboardController: Failed to render dashboard', [
                'category' => $category,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to load category dashboard. Please try again.');
        }
    }

    /**
     * API endpoint: Get chart data for a category (AJAX)
     * Called when user changes the period filter dropdown
     */
    public function getChartData(Request $request, $category)
    {
        try {
            $categoryKey = $this->mapCategorySlugToKey($category);
            $period = $request->get('period', 'this_week');

            $chartData = $this->documentService->getCategoryChartData($categoryKey, $period);

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            Log::error('CategoryDashboardController: Chart data API failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load chart data'
            ], 500);
        }
    }

    /**
     * API endpoint: Get filtered documents for a category (AJAX)
     * Called when user searches or filters the master list table
     */
    public function getDocuments(Request $request, $category)
    {
        try {
            $categoryKey = $this->mapCategorySlugToKey($category);
            
            $filters = [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $documents = $this->documentService->getCategoryDocuments($categoryKey, $filters, 15);

            // If AJAX request, return JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $documents->items(),
                    'pagination' => [
                        'current_page' => $documents->currentPage(),
                        'last_page' => $documents->lastPage(),
                        'per_page' => $documents->perPage(),
                        'total' => $documents->total(),
                    ]
                ]);
            }

            return $documents;

        } catch (\Exception $e) {
            Log::error('CategoryDashboardController: Documents API failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load documents'
            ], 500);
        }
    }

    /**
     * Map URL slug to database category key
     */
    private function mapCategorySlugToKey($slug)
    {
        $map = [
            'birth' => 'birth_certificate',
            'death' => 'death_certificate',
            'marriage' => 'marriage_certificate',
            'cenomar' => 'cenomar',
        ];

        return $map[$slug] ?? $slug;
    }

    /**
     * Get category-specific styling configuration
     */
    private function getCategoryConfig($category)
    {
        $configs = [
            'birth_certificate' => [
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'color' => 'blue',
                'gradient_from' => 'from-blue-500',
                'gradient_to' => 'to-blue-600',
                'bg_light' => 'bg-blue-50',
                'text_color' => 'text-blue-600',
                'border_color' => 'border-blue-200',
            ],
            'death_certificate' => [
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'color' => 'gray',
                'gradient_from' => 'from-gray-500',
                'gradient_to' => 'to-gray-600',
                'bg_light' => 'bg-gray-50',
                'text_color' => 'text-gray-600',
                'border_color' => 'border-gray-200',
            ],
            'marriage_certificate' => [
                'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                'color' => 'pink',
                'gradient_from' => 'from-pink-500',
                'gradient_to' => 'to-pink-600',
                'bg_light' => 'bg-pink-50',
                'text_color' => 'text-pink-600',
                'border_color' => 'border-pink-200',
            ],
            'cenomar' => [
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'color' => 'purple',
                'gradient_from' => 'from-purple-500',
                'gradient_to' => 'to-purple-600',
                'bg_light' => 'bg-purple-50',
                'text_color' => 'text-purple-600',
                'border_color' => 'border-purple-200',
            ],
        ];

        return $configs[$category] ?? $configs['birth_certificate'];
    }
}