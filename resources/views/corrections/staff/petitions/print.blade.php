<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print – {{ $petition->petition_number }}</title>
    <style>
        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Base ── */
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            background: #e5e7eb;
        }

        /* ── Screen-only controls bar ── */
        .no-print {
            background: #f0fdf4;
            border-bottom: 2px solid #16a34a;
            padding: 0.625rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .no-print-title {
            font-family: Arial, sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            color: #166534;
            flex: 1;
        }
        .btn-print {
            background: #16a34a;
            color: #fff;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-print:hover { background: #15803d; }
        .btn-close {
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 0.5rem 1.25rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            cursor: pointer;
            font-family: Arial, sans-serif;
        }

        /* ── A4 document page ── */
        .document-page {
            max-width: 210mm;
            margin: 1.25rem auto 2rem;
            padding: 18mm 20mm;
            background: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        }

        /* ── Official header ── */
        .official-header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
        }
        .republic-title {
            font-size: 9pt;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 0.2rem;
        }
        .office-name {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.1rem;
        }
        .doc-form-title {
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 0.5rem;
        }
        .doc-form-subtitle {
            font-size: 9pt;
            color: #444;
            margin-top: 0.2rem;
            font-style: italic;
        }

        /* ── Meta bar ── */
        .meta-bar {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.25rem;
            border-bottom: 1px solid #999;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            font-size: 9pt;
        }
        .meta-bar span strong { font-weight: 700; }

        /* ── Section heading ── */
        .section-heading {
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.2rem;
            margin-top: 1.1rem;
            margin-bottom: 0.55rem;
        }

        /* ── Detail grid ── */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.3rem 1.25rem;
            margin-bottom: 0.5rem;
        }
        .detail-item dt {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #555;
            margin-bottom: 0.1rem;
        }
        .detail-item dd {
            font-size: 10pt;
            font-weight: 600;
            color: #000;
        }
        .span-full { grid-column: 1 / -1; }

        /* ── Grounds paragraph ── */
        .grounds-text {
            font-size: 10pt;
            line-height: 1.65;
            color: #111;
            margin-bottom: 0.5rem;
        }

        /* ── Corrections table ── */
        .corrections-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 0.5rem;
        }
        .corrections-table th {
            background: #f0f0f0;
            border: 1px solid #888;
            padding: 0.3rem 0.5rem;
            text-align: left;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .corrections-table td {
            border: 1px solid #bbb;
            padding: 0.3rem 0.5rem;
            vertical-align: top;
        }
        .old-val {
            text-decoration: line-through;
            color: #991b1b;
        }
        .new-val {
            color: #14532d;
            font-weight: 700;
        }

        /* ── Annotation box ── */
        .annotation-box {
            border: 2px solid #000;
            background: #fefce8;
            padding: 0.75rem 1rem;
            font-size: 10.5pt;
            line-height: 1.75;
            font-style: italic;
            margin-bottom: 0.4rem;
        }
        .annotation-meta {
            font-size: 8.5pt;
            color: #333;
            margin-bottom: 0.5rem;
        }

        /* ── Approval record ── */
        .approval-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.3rem 1rem;
            margin-bottom: 0.5rem;
        }

        /* ── Signature block ── */
        .signature-area {
            margin-top: 2.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .sig-line {
            border-top: 1.5px solid #000;
            margin-bottom: 0.25rem;
        }
        .sig-label {
            font-size: 8.5pt;
            text-align: center;
            color: #333;
        }
        .sig-name {
            font-size: 9pt;
            text-align: center;
            font-weight: 700;
            color: #000;
        }

        /* ── Document image (new page) ── */
        .doc-image-wrap {
            margin-top: 1rem;
            text-align: center;
        }
        .doc-image-wrap img {
            max-width: 100%;
            max-height: 230mm;
            border: 1px solid #ccc;
            display: block;
            margin: 0.5rem auto 0;
        }

        .attachment-page {
            page-break-before: always;
            break-before: page;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .attachment-page .section-heading {
            margin-top: 0;
            margin-bottom: 3mm;
        }

        /* ── PSA block ── */
        .psa-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.3rem 1.25rem;
            margin-bottom: 0.5rem;
        }

        /* ── Admin notes ── */
        .admin-notes {
            font-size: 9pt;
            font-style: italic;
            color: #444;
            margin-top: 0.4rem;
            border-left: 3px solid #d1d5db;
            padding-left: 0.75rem;
        }

        /* ── Print media rules ── */
        @media print {
            @page { size: A4; margin: 14mm 18mm; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .document-page {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .attachment-page {
                page-break-before: always;
                break-before: page;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .attachment-page .section-heading {
                margin-top: 0;
                margin-bottom: 3mm;
            }
        }

        /* ── Document image container with embedded remarks overlay ── */
        .doc-image-container {
            position: relative;
            display: block;
            width: fit-content;
            max-width: 100%;
            margin: 0 auto;
            line-height: 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .doc-image-container img {
            width: auto;
            max-width: 100%;
            max-height: 238mm;
            height: auto;
            display: block;
            border: 1px solid #aaa;
            object-fit: contain;
        }
        /* Red-bordered REMARKS/ANNOTATION overlay — sits inside the document image
           at the position matching the REMARKS column on PH civil registry forms  */
        .remarks-overlay {
            position: absolute;
            top: 4%;
            right: 1.2%;
            width: 12.5%;
            height: 18%;
            border: 1.5px solid #cc0000;
            background: rgba(255, 255, 255, 0.91);
            box-sizing: border-box;
            padding: 3px 4px;
            overflow: hidden;
        }
        .remarks-overlay-header {
            font-family: 'Times New Roman', Times, serif;
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: center;
            padding: 4px 3px 3px;
            border-bottom: 1px solid #cc0000;
            color: #cc0000;
            line-height: 1.35;
            flex-shrink: 0;
            writing-mode: horizontal-tb;
        }
        .remarks-overlay-body {
            flex: 1;
            overflow: hidden;
            padding: 5px 3px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        /* Keep annotation text inside the printed remarks box without spilling over scanned content. */
        .remarks-overlay-text {
            font-family: 'Times New Roman', Times, serif;
            font-size: 5.8pt;
            font-weight: 400;
            text-transform: uppercase;
            line-height: 1.15;
            color: #000;
            writing-mode: horizontal-tb;
            text-orientation: mixed;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            max-height: 100%;
            overflow: hidden;
        }
    </style>
</head>
<body>

{{-- ── Screen-only control bar (hidden when printing) ── --}}
<div class="no-print">
    <span class="no-print-title">&#128438; Print Preview &mdash; {{ $petition->petition_number }} ({{ $petition->petition_type_label }})</span>
    <button class="btn-print" onclick="window.print()">&#128438; &nbsp;Print Document</button>
    <button class="btn-close" onclick="window.close()">Close</button>
</div>

{{-- ── Printable A4 Page ── --}}
<div class="document-page">

    {{-- Official Header --}}
    <div class="official-header">
        <div class="republic-title">Republic of the Philippines</div>
        <div class="office-name">Local Civil Registry Office</div>
        <div class="doc-form-title">Marginal Annotation Record</div>
        <div class="doc-form-subtitle">Pursuant to {{ $petition->legal_basis }}</div>
    </div>

    {{-- Petition meta bar --}}
    <div class="meta-bar">
        <span>Petition No.: <strong>{{ $petition->petition_number }}</strong></span>
        <span>Type: <strong>{{ $petition->petition_type_label }}</strong></span>
        <span>Date Filed: <strong>{{ $petition->created_at->format('F d, Y') }}</strong></span>
        <span>Status: <strong>APPROVED</strong></span>
    </div>

    {{-- I. Document Subject --}}
    <div class="section-heading">I. Civil Registry Document Subject to Correction</div>
    @if($petition->scan)
    <div class="detail-grid">
        <dl class="detail-item">
            <dt>Document Type</dt>
            <dd>{{ ucwords(str_replace('_', ' ', $petition->scan->document_type)) }}</dd>
        </dl>
        <dl class="detail-item">
            <dt>Document ID</dt>
            <dd>{{ $petition->scan->document_id ?? $petition->scan->id }}</dd>
        </dl>
        <dl class="detail-item">
            <dt>Title</dt>
            <dd>{{ $petition->scan->title ?? 'N/A' }}</dd>
        </dl>
        <dl class="detail-item">
            <dt>Registry Number</dt>
            <dd>{{ $petition->scan->registry_number ?? 'N/A' }}</dd>
        </dl>
    </div>
    @else
    <p style="font-size: 9.5pt; color: #666; margin-bottom: 0.5rem;">Document information not available.</p>
    @endif

    {{-- II. Petitioner Information --}}
    <div class="section-heading">II. Petitioner Information</div>
    <div class="detail-grid">
        <dl class="detail-item">
            <dt>Full Name</dt>
            <dd>{{ $petition->petitioner_name }}</dd>
        </dl>
        <dl class="detail-item">
            <dt>Relationship to Subject</dt>
            <dd>{{ \App\Models\LegalCorrectionPetition::RELATIONSHIP_TYPES[$petition->petitioner_relationship] ?? $petition->petitioner_relationship }}</dd>
        </dl>
        @if($petition->petitioner_address)
        <dl class="detail-item span-full">
            <dt>Address</dt>
            <dd>{{ $petition->petitioner_address }}</dd>
        </dl>
        @endif
    </div>

    {{-- III. Grounds for Correction --}}
    @if($petition->reason)
    <div class="section-heading">III. Grounds for Correction</div>
    <p class="grounds-text">{{ $petition->reason }}</p>
    @endif

    {{-- IV. Ordered Corrections --}}
    <div class="section-heading">IV. Ordered Corrections</div>
    <table class="corrections-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 20%;">Field</th>
                <th style="width: 22%;">Original Value</th>
                <th style="width: 22%;">Corrected Value</th>
                <th>Justification</th>
            </tr>
        </thead>
        <tbody>
            @foreach($petition->fieldChanges as $i => $change)
            <tr>
                <td style="text-align: center;">{{ $i + 1 }}</td>
                <td style="font-weight: 600;">{{ $change->field_label }}</td>
                <td><span class="old-val">{{ $change->current_value ?: '(blank)' }}</span></td>
                <td><span class="new-val">{{ $change->proposed_value }}</span></td>
                <td style="color: #444;">{{ $change->justification ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- V. Approved Marginal Annotation --}}
    @if($petition->annotations->count() > 0)
    <div class="section-heading">V. Approved Marginal Annotation</div>
    @foreach($petition->annotations as $annotation)
    <div class="annotation-box">{{ $annotation->annotation_text }}</div>
    <div class="annotation-meta">
        Decision No.: <strong>{{ $annotation->lcro_decision_number }}</strong>
        &bull; Date: <strong>{{ $annotation->annotation_date ? $annotation->annotation_date->format('F d, Y') : 'N/A' }}</strong>
        &bull; Legal Reference: <strong>{{ $annotation->legal_reference }}</strong>
        @if($annotation->annotator)
        &bull; Annotated by: <strong>{{ $annotation->annotator->name }}</strong>
        @endif
    </div>
    @endforeach
    @endif

    {{-- VI. LCRO Approval Record --}}
    <div class="section-heading">VI. LCRO Approval Record</div>
    <div class="approval-grid">
        <dl class="detail-item">
            <dt>Approved By</dt>
            <dd>{{ $petition->approver->name ?? 'N/A' }}</dd>
        </dl>
        <dl class="detail-item">
            <dt>Date Approved</dt>
            <dd>{{ $petition->approved_at ? $petition->approved_at->format('F d, Y') : 'N/A' }}</dd>
        </dl>
        <dl class="detail-item">
            <dt>Filed / Prepared By</dt>
            <dd>{{ $petition->creator->name ?? 'N/A' }}</dd>
        </dl>
    </div>
    @if($petition->admin_notes)
    <div class="admin-notes">Admin Notes: {{ $petition->admin_notes }}</div>
    @endif

    {{-- PSA Forwarding (if applicable) --}}
    @if($petition->psaForwardingLog)
    <div class="section-heading">PSA Forwarding Reference</div>
    <div class="psa-grid">
        <dl class="detail-item">
            <dt>Forwarding Reference</dt>
            <dd>{{ $petition->psaForwardingLog->forwarding_reference }}</dd>
        </dl>
        <dl class="detail-item">
            <dt>Date Forwarded</dt>
            <dd>{{ $petition->psaForwardingLog->forwarded_at ? $petition->psaForwardingLog->forwarded_at->format('F d, Y h:i A') : 'N/A' }}</dd>
        </dl>
    </div>
    @endif

    {{-- Signature Block --}}
    <div class="signature-area">
        <div>
            <div class="sig-line">&nbsp;</div>
            <div class="sig-label">Signature Over Printed Name</div>
            <div class="sig-name">{{ $petition->approver->name ?? '________________________' }}</div>
            <div class="sig-label">Local Civil Registrar / Approving Authority</div>
        </div>
        <div>
            <div class="sig-line">&nbsp;</div>
            <div class="sig-label">Signature Over Printed Name</div>
            <div class="sig-name">{{ $petition->petitioner_name }}</div>
            <div class="sig-label">Petitioner /
                {{ \App\Models\LegalCorrectionPetition::RELATIONSHIP_TYPES[$petition->petitioner_relationship] ?? 'Authorized Representative' }}
            </div>
        </div>
    </div>

    {{-- Document Image (break to new page) --}}
    @if($petition->scan && $petition->scan->file_path)
    @php
        $imageUrl = $petition->scan->full_file_path
            ? route('staff.scans.image.preview', ['scan' => $petition->scan->id])
            : asset('images/placeholder-document.png');
    @endphp
    <div class="attachment-page">
        <div class="section-heading">Attachment: Original Civil Registry Document</div>
        <div class="doc-image-container">
            <img src="{{ $imageUrl }}"
                 alt="Civil Registry Document – {{ $petition->scan->title ?? $petition->scan->document_id }}"
                 onerror="this.onerror=null; this.src='{{ asset('images/placeholder-document.png') }}'">
            @if(!empty($remarks))
            <div class="remarks-overlay" aria-label="LCRO remarks annotation">
                <div class="remarks-overlay-text">{{ strtoupper($remarks) }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>{{-- end .document-page --}}

<script>
    // Auto-trigger print dialog when the page finishes loading
    // — matching the behaviour of the existing document search print flow
    window.onload = function () {
        window.print();
    };

    // Close the tab automatically after printing
    window.onafterprint = function () {
        window.close();
    };
</script>
</body>
</html>
