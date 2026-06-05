<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    /**
     * Display the main search page
     */
    public function index()
    {
        return view('staff.search');
    }

    /**
     * Load search form for specific document type
     */
    public function loadSearchForm($documentType)
    {
        try {
            $viewName = match($documentType) {
                'birth_certificate' => 'staff.search-forms.birth',
                'marriage_certificate' => 'staff.search-forms.marriage',
                'death_certificate' => 'staff.search-forms.death',
                'other' => 'staff.search-forms.other',
                default => 'staff.search-forms.birth'
            };

            // Check if view exists
            if (!view()->exists($viewName)) {
                return response('<div class="text-red-600">Form template not found for this document type.</div>', 404);
            }

            return view($viewName, compact('documentType'));
        } catch (\Exception $e) {
            Log::error('Error loading search form', [
                'document_type' => $documentType,
                'error' => $e->getMessage()
            ]);
            
            return response('<div class="text-red-600">Error loading search form. Please try again.</div>', 500);
        }
    }

    /**
     * Perform live search as user types
     */
    public function liveSearch(Request $request)
{
    try {
        $documentType = $request->get('document_type');
        $searchTerms = $request->except(['document_type', '_token']);
            $isOtherDocumentsSearch = ($documentType === 'other');
        
        // Remove empty search terms
        $searchTerms = array_filter($searchTerms, function($value) {
            return !empty(trim($value));
        });

        if (empty($searchTerms)) {
            return response()->json([]);
        }

        if ($isOtherDocumentsSearch && empty(trim((string) $request->get('document_title', '')))) {
            // Keep "Other Documents" live-search focused on title and avoid broad queries.
            return response()->json([]);
        }

        // Handle 'other' document type mapping
        if ($isOtherDocumentsSearch) {
            $specificType = trim((string) $request->get('specific_document_type', ''));
            if ($specificType !== '') {
                $query = Scan::where('document_type', $specificType)
                            ->where('verification_status', 'completed');
            } else {
                $query = Scan::whereNotIn('document_type', $this->coreDocumentTypes())
                            ->where('verification_status', 'completed');
            }
        }

        if (!isset($query)) {
            $query = Scan::where('document_type', $documentType)
                        ->where('verification_status', 'completed');
        }

        // Build search conditions with field mapping
        foreach ($searchTerms as $field => $value) {
            if ($field === 'specific_document_type') {
                continue;
            }

            if ($isOtherDocumentsSearch) {
                if ($field === 'document_title') {
                    $query->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$value}%"]);
                }
                continue;
            }

            // Map search field to actual database field
            $dbField = $this->mapSearchFieldToDbField($field, $documentType);
            
            // Skip unmapped fields (e.g. marriage_type)
            if ($dbField === '_skip') continue;

            // Handle composite fields that span multiple JSON keys
            if ($dbField === '_composite_death_date') {
                $this->addDeathDateSearchCondition($query, $value);
            } elseif ($dbField === '_composite_death_place') {
                $this->addDeathPlaceSearchCondition($query, $value);
            } else {
                $query->where(function($q) use ($dbField, $value) {
                    // Search in extracted_fields (handles both single & double-encoded JSON)
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), ?))) LIKE LOWER(?)", 
                        ['$.' . $dbField, "%{$value}%"])
                      // Fallback to ocr_data.extracted_fields
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(ocr_data), ?))) LIKE LOWER(?)", 
                        ['$.extracted_fields.' . $dbField, "%{$value}%"]);
                });
            }
        }

        $results = $query->limit(5)->get();

        Log::info('Live search performed', [
            'document_type' => $documentType,
            'search_terms' => $searchTerms,
            'mapped_fields' => array_map(fn($field) => $this->mapSearchFieldToDbField($field, $documentType), array_keys($searchTerms)),
            'results_count' => $results->count()
        ]);

        return response()->json($results->map(function($scan) {
            // 🔧 FIX: Parse extracted_fields before sending
            $extractedFields = $scan->extracted_fields;
            if (is_string($extractedFields)) {
                $extractedFields = json_decode($extractedFields, true) ?? [];
            }

            $mediaPayload = $this->buildDocumentMediaPayload($scan);
            
            return [
                'id' => $scan->id,
                'title' => $scan->title ?? 'Untitled Document',
                'document_id' => $scan->document_id ?? 'N/A',
                'document_type' => $scan->document_type,
                'status' => $this->getVerificationStatusName($scan->verification_status),
                'status_class' => $this->getStatusCssClass($scan->verification_status),
                'image_url' => $mediaPayload['image_url'],
                'preview_url' => $mediaPayload['preview_url'],
                'preview_raw_url' => $mediaPayload['preview_raw_url'],
                'download_url' => $mediaPayload['download_url'],
                'file_mime_type' => $mediaPayload['file_mime_type'],
                'is_pdf' => $mediaPayload['is_pdf'],
                'extracted_fields' => json_encode($extractedFields) // 🔧 Now we need to encode it back for JavaScript
            ];
        }));

    } catch (\Exception $e) {
        Log::error('Live search error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        
        return response()->json([]);
    }
}


// Map search field names to database field names 
private function mapSearchFieldToDbField($searchField, $documentType)
{
    $fieldMappings = [
        'birth_certificate' => [
            'child_first_name' => 'name_first',
            'child_middle_name' => 'name_middle',
            'child_last_name' => 'name_last',
            'date_of_birth' => 'birth_date', 
            'birth_date_day' => 'birth_date_day',
            'birth_date_month' => 'birth_date_month',
            'birth_date_year' => 'birth_date_year',
            'sex' => 'sex', 
            'place_of_birth' => 'birth_place_city', 
            'mother_first_name' => 'mother_first_name',
            'mother_middle_name' => 'mother_middle_name', 
            'mother_last_name' => 'mother_last_name',
            'father_first_name' => 'father_first_name',
            'father_middle_name' => 'father_middle_name', 
            'father_last_name' => 'father_last_name',
        ],
        'death_certificate' => [
            'deceased_first_name' => 'deceased_first_name',
            'deceased_middle_name' => 'deceased_middle_name',
            'deceased_last_name' => 'deceased_last_name',
            'date_of_death' => '_composite_death_date',
            'place_of_death' => '_composite_death_place',
            'cause_of_death' => 'cause_of_death',
            'sex' => 'sex',
            'civil_status' => 'civil_status',
            'age_at_death' => 'age_at_death',
        ],
        'marriage_certificate' => [
            'husband_first_name' => 'groom_first_name',
            'husband_middle_name' => 'groom_middle_name',
            'husband_last_name' => 'groom_last_name',
            'wife_first_name' => 'bride_first_name',
            'wife_middle_name' => 'bride_middle_name',
            'wife_last_name' => 'bride_last_name',
            'date_of_marriage' => 'marriage_date',
            'place_of_marriage' => 'marriage_place_city',
            'marriage_type' => '_skip',
        ],
    ];
    
    return $fieldMappings[$documentType][$searchField] ?? $searchField;
}

private function coreDocumentTypes(): array
{
    return [
        'birth_certificate',
        'death_certificate',
        'marriage_certificate',
    ];
}


    /**
     * Perform full search with exact matching
     */
    public function search(Request $request)
    {
        try {
            $documentType = $request->get('document_type');
            $searchTerms = $request->except(['document_type', '_token']);
            $isOtherDocumentsSearch = ($documentType === 'other');
            
            // Remove empty search terms
            $searchTerms = array_filter($searchTerms, function($value) {
                return !empty(trim($value));
            });

            if (empty($searchTerms)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide search criteria'
                ]);
            }

            // Validate search terms based on document type
            $validation = $this->validateSearchCriteria($documentType, $searchTerms);
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ]);
            }

            // Handle 'other' document type mapping
            if ($isOtherDocumentsSearch) {
                $specificType = trim((string) $request->get('specific_document_type', ''));
                if ($specificType !== '') {
                    $query = Scan::where('document_type', $specificType)
                                ->where('verification_status', 'completed');
                } else {
                    $query = Scan::whereNotIn('document_type', $this->coreDocumentTypes())
                                ->where('verification_status', 'completed');
                }
            }

            if (!isset($query)) {
                $query = Scan::where('document_type', $documentType)
                            ->where('verification_status', 'completed');
            }

            // Build search conditions - use both exact and partial matching
            foreach ($searchTerms as $field => $value) {
                if ($field === 'specific_document_type') {
                    continue;
                }

                if ($isOtherDocumentsSearch) {
                    if ($field === 'document_title') {
                        $query->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$value}%"]);
                    }
                    continue;
                }

                // Map search field to actual database field
                $dbField = $this->mapSearchFieldToDbField($field, $documentType);
                
                // Skip unmapped fields (e.g. marriage_type)
                if ($dbField === '_skip') continue;

                // Handle composite fields that span multiple JSON keys
                if ($dbField === '_composite_death_date') {
                    $this->addDeathDateSearchCondition($query, $value);
                } elseif ($dbField === '_composite_death_place') {
                    $this->addDeathPlaceSearchCondition($query, $value);
                } else {
                    $query->where(function($q) use ($dbField, $value) {
                        // Search in extracted_fields (handles both single & double-encoded JSON)
                        $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), ?))) LIKE LOWER(?)", 
                            ['$.' . $dbField, "%{$value}%"])
                          // Fallback to ocr_data (parameterized to prevent SQL injection)
                          ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(ocr_data), ?))) LIKE LOWER(?)", 
                            ['$.extracted_fields.' . $dbField, "%{$value}%"]);
                    });
                }
            }

            $results = $query->get();

            // Calculate match scores for ranking
            $rankedResults = $this->rankSearchResults($results, $searchTerms);

            Log::info('Document search performed', [
                'document_type' => $documentType,
                'search_terms' => array_keys($searchTerms),
                'results_count' => $results->count(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'results' => $rankedResults->map(function($scan) {
                    $mediaPayload = $this->buildDocumentMediaPayload($scan);

                    return [
                        'id' => $scan->id,
                        'document_id' => $scan->document_id ?? 'N/A',
                        'title' => $scan->title ?? 'Untitled Document',
                        'document_type' => $scan->document_type,
                        'status' => $this->getVerificationStatusName($scan->verification_status),
                        'verification_status' => $this->getVerificationStatusName($scan->verification_status),
                        'status_class' => $this->getStatusCssClass($scan->verification_status),
                        'image_url' => $mediaPayload['image_url'],
                        'preview_url' => $mediaPayload['preview_url'],
                        'preview_raw_url' => $mediaPayload['preview_raw_url'],
                        'download_url' => $mediaPayload['download_url'],
                        'file_mime_type' => $mediaPayload['file_mime_type'],
                        'is_pdf' => $mediaPayload['is_pdf'],
                        'extracted_fields' => $scan->extracted_fields,
                        'blockchain_status' => $scan->blockchain_status ?? 'not_submitted',
                        'created_at' => $scan->created_at ? $scan->created_at->format('M d, Y') : 'Unknown',
                        'can_print' => $this->canPrintDocument($scan),
                        'match_score' => $scan->match_score ?? 0
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Search error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Search failed. Please try again.'
            ]);
        }
    }

    /**
     * Get document details for printing/viewing
     */
    public function getDocument($scanId)
{
    try {
        $scan = Scan::findOrFail($scanId);
        
        // Check if user can view this document
        if (!$this->canViewDocument($scan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this document'
            ], 403);
        }

        // 🔧 FIX: Parse extracted_fields before sending
        $extractedFields = $scan->extracted_fields;
        if (is_string($extractedFields)) {
            $extractedFields = json_decode($extractedFields, true) ?? [];
        }

        return response()->json([
            'success' => true,
            'document' => [
                'id' => $scan->id,
                'document_id' => $scan->document_id,
                'document_type' => $scan->document_type,
                'title' => $scan->title,
                'extracted_fields' => $extractedFields, // 🔧 Already parsed array
                'verification_status' => $scan->verification_status,
                'status_class' => $this->getStatusCssClass($scan->verification_status),
                'image_url' => $this->getDocumentImageUrl($scan),
                'preview_url' => $this->getDocumentImageUrl($scan),
                'preview_raw_url' => $this->getDocumentRawUrl($scan),
                'download_url' => $this->getDocumentDownloadUrl($scan),
                'file_mime_type' => $scan->file_mime_type,
                'is_pdf' => $this->isPdfDocument($scan),
                'created_at' => $scan->created_at->format('Y-m-d H:i:s'),
                'can_print' => $this->canPrintDocument($scan)
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('Document retrieval error', [
            'scan_id' => $scanId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Document not found'
        ], 404);
    }
}
    

    /**
     * Print document
     */
    public function printDocument($scanId)
    {
        try {
            $scan = Scan::findOrFail($scanId);
            
            // Verify document is printable
            if (!$this->canPrintDocument($scan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document is not ready for printing. Please ensure payment is verified and document is blockchain-confirmed.'
                ]);
            }

            // Log print action
            Log::info('Document printed', [
                'scan_id' => $scanId,
                'document_id' => $scan->document_id,
                'document_type' => $scan->document_type,
                'staff_id' => auth()->id(),
                'printed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'print_data' => [
                    'document_id' => $scan->document_id ?? 'N/A',
                    'title' => $scan->title ?? 'Untitled Document',
                    'document_type' => $scan->document_type,
                    'extracted_fields' => $scan->extracted_fields,
                    'blockchain_hash' => $scan->blockchain_hash,
                    'verification_date' => $scan->verified_at,
                    'staff_name' => auth()->user()->name,
                    'print_timestamp' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Print document error', [
                'scan_id' => $scanId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Print failed. Please try again.'
            ]);
        }
    }

    /**
     * Log document release for printing
     */
    public function logDocumentRelease(Request $request, $scanId)
    {
        try {
            $scan = Scan::findOrFail($scanId);
            
            // Validate authorization
            if (!$this->canPrintDocument($scan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document cannot be released'
                ], 422);
            }
            
            $validated = $request->validate([
                'copies' => 'required|integer|min:1|max:10',
                'client_name' => 'required|string|max:255',
                'reason' => 'required|string|max:500',
                'released_at' => 'required|date'
            ]);
            
            // Log to audit trail
            Log::info('Document released', [
                'scan_id' => $scanId,
                'document_id' => $scan->document_id,
                'staff_user_id' => auth()->id(),
                'staff_name' => auth()->user()->name,
                'client_name' => $validated['client_name'],
                'reason' => $validated['reason'],
                'copies' => $validated['copies'],
                'released_at' => $validated['released_at'],
                'ip_address' => $request->ip()
            ]);
            
            // Update scan record
            $scan->update([
                'status' => 'released',
                'released' => true,
                'released_at' => now(),
                'released_by' => auth()->id(),
                'released_to' => $validated['client_name'],
                'copies_printed' => ($scan->copies_printed ?? 0) + $validated['copies']
            ]);
            
        
            /*
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'document_released',
                'subject_type' => 'App\\Models\\Scan',
                'subject_id' => $scan->id,
                'details' => json_encode([
                    'client_name' => $validated['client_name'],
                    'copies' => $validated['copies'],
                    'document_id' => $scan->document_id
                ])
            ]);
            */
            
            return response()->json([
                'success' => true,
                'message' => 'Document release logged successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Document release logging error', [
                'scan_id' => $scanId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to log document release'
            ], 500);
        }
    }

    /**
     * Validate search criteria based on document type
     */
    private function validateSearchCriteria($documentType, $searchTerms)
    {
        $rules = [];
        $messages = [];

        switch($documentType) {
            case 'birth_certificate':
                $rules = [
                    'child_last_name' => 'required|string|min:2',
                    'child_first_name' => 'required|string|min:2',
                ];
                $messages = [
                    'child_last_name.required' => 'Child\'s last name is required',
                    'child_first_name.required' => 'Child\'s first name is required',
                ];
                break;

            case 'marriage_certificate':
                $rules = [
                    'husband_last_name' => 'required|string|min:2',
                    'husband_first_name' => 'required|string|min:2',
                    'wife_last_name' => 'required|string|min:2',
                    'wife_first_name' => 'required|string|min:2',
                ];
                $messages = [
                    'husband_last_name.required' => 'Husband\'s last name is required',
                    'husband_first_name.required' => 'Husband\'s first name is required',
                    'wife_last_name.required' => 'Wife\'s last name is required',
                    'wife_first_name.required' => 'Wife\'s first name is required',
                ];
                break;

            case 'death_certificate':
                $rules = [
                    'deceased_last_name' => 'required|string|min:2',
                    'deceased_first_name' => 'required|string|min:2',
                ];
                $messages = [
                    'deceased_last_name.required' => 'Deceased\'s last name is required',
                    'deceased_first_name.required' => 'Deceased\'s first name is required',
                ];
                break;

            case 'other':
                $rules = [
                    'specific_document_type' => 'required|string',
                    'document_title' => 'required|string|min:2',
                ];
                $messages = [
                    'specific_document_type.required' => 'Document type is required',
                    'document_title.required' => 'Document title is required',
                ];
                break;
        }

        $validator = Validator::make($searchTerms, $rules, $messages);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'message' => $validator->errors()->first()
            ];
        }

        return ['valid' => true];
    }

    /**
     * Add search condition for death date across day/month/year component fields.
     * The form sends a date string (e.g. "2024-03-15") but extracted_fields stores
     * death_date_day, death_date_month, death_date_year separately.
     */
    private function addDeathDateSearchCondition($query, $value)
    {
        // Parse the date input — could be "YYYY-MM-DD" from date picker or partial text
        $parts = [];
        if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $value, $m)) {
            $parts['year'] = $m[1];
            $parts['month'] = ltrim($m[2], '0');
            $parts['day'] = ltrim($m[3], '0');

            // Convert numeric month to name for matching (extraction stores month names)
            $monthNames = [
                '1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April',
                '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August',
                '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
            ];
            $monthName = $monthNames[$parts['month']] ?? $parts['month'];

            $query->where(function($q) use ($parts, $monthName) {
                $q->where(function($inner) use ($parts, $monthName) {
                    $inner->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_date_year'))) LIKE LOWER(?)",
                        ["%{$parts['year']}%"])
                    ->where(function($mq) use ($parts, $monthName) {
                        $mq->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_date_month'))) LIKE LOWER(?)",
                            ["%{$monthName}%"])
                        ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_date_month'))) LIKE LOWER(?)",
                            ["%{$parts['month']}%"]);
                    })
                    ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_date_day'))) LIKE LOWER(?)",
                        ["%{$parts['day']}%"]);
                });
            });
        } else {
            // Freetext fallback — search all date component fields
            $query->where(function($q) use ($value) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_date_year'))) LIKE LOWER(?)",
                    ["%{$value}%"])
                ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_date_month'))) LIKE LOWER(?)",
                    ["%{$value}%"])
                ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_date_day'))) LIKE LOWER(?)",
                    ["%{$value}%"]);
            });
        }
    }

    /**
     * Add search condition for death place across city/province/institution fields.
     * Form sends single "place_of_death" text but extraction stores death_place_city,
     * death_place_province, death_place_institution separately.
     */
    private function addDeathPlaceSearchCondition($query, $value)
    {
        $query->where(function($q) use ($value) {
            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_place_city'))) LIKE LOWER(?)",
                ["%{$value}%"])
            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_place_province'))) LIKE LOWER(?)",
                ["%{$value}%"])
            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(extracted_fields), '$.death_place_institution'))) LIKE LOWER(?)",
                ["%{$value}%"])
            // Fallback: ocr_data paths
            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(ocr_data), '$.extracted_fields.death_place_city'))) LIKE LOWER(?)",
                ["%{$value}%"])
            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(ocr_data), '$.extracted_fields.death_place_province'))) LIKE LOWER(?)",
                ["%{$value}%"]);
        });
    }

    /**
     * Rank search results by relevance
     */
    private function rankSearchResults($results, $searchTerms)
    {
        return $results->map(function($scan) use ($searchTerms) {
            // Handle both array (from model cast/accessor) and raw string
            $extractedFields = is_array($scan->extracted_fields) 
                ? $scan->extracted_fields 
                : (json_decode($scan->extracted_fields, true) ?? []);
            $ocrData = is_array($scan->ocr_data)
                ? $scan->ocr_data
                : (json_decode($scan->ocr_data, true) ?? []);
            $matchScore = 0;
            $totalFields = count($searchTerms);

            foreach ($searchTerms as $field => $value) {
                $searchValue = strtolower(trim($value));

                if ($field === 'specific_document_type') {
                    if (strtolower((string) $scan->document_type) === $searchValue) {
                        $matchScore += 100;
                    }
                    continue;
                }

                if ($field === 'document_title') {
                    $titleValue = strtolower(trim((string) $scan->title));
                    if ($titleValue === $searchValue) {
                        $matchScore += 100;
                    } elseif ($searchValue !== '' && strpos($titleValue, $searchValue) !== false) {
                        $matchScore += 75;
                    }
                    continue;
                }

                // Use mapped DB field name for lookup (e.g. 'deceased_first_name' stays, 'child_first_name' → 'name_first')
                $dbField = $this->mapSearchFieldToDbField($field, $scan->document_type);
                
                // Skip unmapped and composite fields for simple scoring
                if ($dbField === '_skip') continue;

                // Handle composite death date/place scoring
                if ($dbField === '_composite_death_date') {
                    $yearVal = strtolower(trim($extractedFields['death_date_year'] ?? ''));
                    $monthVal = strtolower(trim($extractedFields['death_date_month'] ?? ''));
                    $dayVal = strtolower(trim($extractedFields['death_date_day'] ?? ''));
                    $composite = "$dayVal $monthVal $yearVal";
                    if (strpos($composite, $searchValue) !== false) {
                        $matchScore += 75;
                    } elseif (strpos($yearVal, $searchValue) !== false || strpos($monthVal, $searchValue) !== false) {
                        $matchScore += 50;
                    }
                    continue;
                }
                if ($dbField === '_composite_death_place') {
                    $cityVal = strtolower(trim($extractedFields['death_place_city'] ?? ''));
                    $provVal = strtolower(trim($extractedFields['death_place_province'] ?? ''));
                    $instVal = strtolower(trim($extractedFields['death_place_institution'] ?? ''));
                    $composite = "$instVal $cityVal $provVal";
                    if (strpos($composite, $searchValue) !== false) {
                        $matchScore += 75;
                    }
                    continue;
                }

                // Check extracted fields using both mapped DB field and original form field
                $fieldValue = null;
                if (isset($extractedFields[$dbField])) {
                    $fieldValue = $extractedFields[$dbField];
                } elseif ($dbField !== $field && isset($extractedFields[$field])) {
                    $fieldValue = $extractedFields[$field];
                }
                
                if ($fieldValue !== null) {
                    $fieldValue = strtolower(trim($fieldValue));
                    if ($fieldValue === $searchValue) {
                        $matchScore += 100; // Exact match in extracted fields
                    } elseif (strpos($fieldValue, $searchValue) !== false) {
                        $matchScore += 75; // Partial match in extracted fields
                    }
                }
                // Check OCR data as fallback
                else {
                    $ocrFieldValue = $ocrData[$dbField] ?? ($ocrData[$field] ?? null);
                    if ($ocrFieldValue !== null) {
                        $ocrFieldValue = strtolower(trim($ocrFieldValue));
                        if ($ocrFieldValue === $searchValue) {
                            $matchScore += 80; // Exact match in OCR data
                        } elseif (strpos($ocrFieldValue, $searchValue) !== false) {
                            $matchScore += 60; // Partial match in OCR data
                        }
                    }
                }
            }

            $scan->match_score = $totalFields > 0 ? round($matchScore / $totalFields, 2) : 0;
            return $scan;
        })->sortByDesc('match_score');
    }

    /**
     * Get document image URL
     */
    private function getDocumentImageUrl($scan)
{
        if (!$scan || !$scan->full_file_path) {
        return $this->getPlaceholderDocumentImageUrl();
    }

    return route('staff.scans.image.preview', ['scan' => $scan->id]);
}

private function getPlaceholderDocumentImageUrl(): string
{
    return asset('images/placeholder-document.png');
}

private function getDocumentRawUrl($scan)
{
    if (!$scan || !$scan->full_file_path) {
        return null;
    }

    return route('staff.scans.image.preview', [
        'scan' => $scan->id,
        'raw' => 1,
    ]);
}

private function getDocumentDownloadUrl($scan)
{
    if (!$scan || !$scan->full_file_path) {
        return null;
    }

    return route('staff.scans.image.download', ['scan' => $scan->id]);
}

private function isPdfDocument($scan): bool
{
    if (!$scan) {
        return false;
    }

    $mimeType = strtolower((string) ($scan->file_mime_type ?? ''));
    if ($mimeType === 'application/pdf') {
        return true;
    }

    $path = strtolower((string) ($scan->file_path ?? ''));

    return str_ends_with($path, '.pdf');
}

private function buildDocumentMediaPayload($scan): array
{
    return [
        'image_url' => $this->getDocumentImageUrl($scan),
        'preview_url' => $this->getDocumentImageUrl($scan),
        'preview_raw_url' => $this->getDocumentRawUrl($scan),
        'download_url' => $this->getDocumentDownloadUrl($scan),
        'file_mime_type' => $scan->file_mime_type,
        'is_pdf' => $this->isPdfDocument($scan),
    ];
}
    /**
     * Check if document can be printed
     */
    private function canPrintDocument($scan)
    {
        $verificationStatus = strtolower((string) ($scan->verification_status ?? ''));

        // Keep release restricted to verified/completed records.
        if ($verificationStatus !== 'completed') {
            return false;
        }

        // If blockchain is intentionally disabled for the record, allow print/release.
        if (isset($scan->blockchain_enabled) && (int) $scan->blockchain_enabled === 0) {
            return true;
        }

        $blockchainStatus = strtolower(trim((string) ($scan->blockchain_status ?? '')));

        if (in_array($blockchainStatus, ['confirmed', 'completed'], true)) {
            return true;
        }

        // Backward-compatible fallback for legacy/inconsistent rows where
        // confirmation timestamp exists but blockchain_status was not normalized.
        return !empty($scan->blockchain_confirmed_at);
    }

    /**
     * Check if user can view document
     */
    private function canViewDocument($scan)
    {
        // Add your authorization logic here
        return auth()->check() && auth()->user()->hasRole(['staff', 'admin']);
    }

    /**
     * Get verification status display name
     */
    private function getVerificationStatusName($status)
    {
        return match($status) {
            'completed' => 'Verified',
            'pending' => 'Pending',
            'reviewing' => 'Under Review',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Get CSS class for status
     */
    private function getStatusCssClass($status)
    {
        return match($status) {
            'completed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'reviewing' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}