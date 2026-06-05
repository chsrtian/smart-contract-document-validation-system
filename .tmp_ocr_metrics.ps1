$logPath='storage\logs\laravel.log'
if (!(Test-Path $logPath)) { Write-Output 'LOG_NOT_FOUND'; exit 1 }

$targetDocs=@('birth_certificate','death_certificate','marriage_certificate')

$runs = New-Object System.Collections.Generic.List[object]
$current = $null

function New-Run($doc) {
    [pscustomobject]@{
        doc = $doc
        paddleSuccess = $false
        tesseractUsed = $false
        paddleEngineConfidence = $null
        finalConfidence = $null
        completed = $false
        failed = $false
        startLine = 0
    }
}

$lineNo = 0
Get-Content $logPath | ForEach-Object {
    $line = $_
    $lineNo++

    if ($line -match 'Starting (enhanced )?OCR processing\s*\{.*"type":"([^"]+)"') {
        if ($current -ne $null) {
            $runs.Add($current) | Out-Null
        }
        $doc = $matches[2]
        $current = New-Run $doc
        $current.startLine = $lineNo
        return
    }

    if ($current -eq $null) { return }

    if ($line -match 'OCRService: PaddleOCR extraction successful\s*\{') {
        $current.paddleSuccess = $true
        if ($line -match '"confidence":([0-9]+(?:\.[0-9]+)?)') {
            $current.paddleEngineConfidence = [double]$matches[1]
        }
        return
    }

    if ($line -match 'Starting (enhanced )?Tesseract OCR') {
        $current.tesseractUsed = $true
        return
    }

    if ($line -match 'OCR processing completed successfully\s*\{"confidence":([0-9]+(?:\.[0-9]+)?)') {
        $current.completed = $true
        $current.finalConfidence = [double]$matches[1]
        $runs.Add($current) | Out-Null
        $current = $null
        return
    }

    if ($line -match 'OCR processing failed\s*\{') {
        $current.failed = $true
        $runs.Add($current) | Out-Null
        $current = $null
        return
    }
}
if ($current -ne $null) { $runs.Add($current) | Out-Null }

$completedTarget = $runs | Where-Object { $_.completed -and ($targetDocs -contains $_.doc) }

$classified = $completedTarget | ForEach-Object {
    $engine = 'unknown'
    if ($_.paddleSuccess -and -not $_.tesseractUsed) { $engine = 'paddle' }
    elseif ($_.tesseractUsed -and -not $_.paddleSuccess) { $engine = 'tesseract' }
    elseif ($_.tesseractUsed -and $_.paddleSuccess) { $engine = 'hybrid' }

    [pscustomobject]@{
        engine = $engine
        doc = $_.doc
        finalConfidence = $_.finalConfidence
        paddleEngineConfidence = $_.paddleEngineConfidence
    }
}

function Summarize-Confidence($items, $confidenceProp) {
    $out = @()
    foreach ($doc in $targetDocs) {
        $grp = $items | Where-Object { $_.doc -eq $doc -and $_.$confidenceProp -ne $null }
        $count = ($grp | Measure-Object).Count
        $avg = if ($count -gt 0) { [math]::Round((($grp | Measure-Object -Property $confidenceProp -Average).Average), 2) } else { $null }
        $out += [pscustomobject]@{ doc=$doc; runs=$count; avg_confidence=$avg }
    }
    return $out
}

$tesseractRows = Summarize-Confidence ($classified | Where-Object { $_.engine -eq 'tesseract' }) 'finalConfidence'
$paddleRows = Summarize-Confidence ($classified | Where-Object { $_.engine -eq 'paddle' }) 'paddleEngineConfidence'

$paddleSend = @{ birth_certificate=0; death_certificate=0; marriage_certificate=0 }
$paddleSuccess = @{ birth_certificate=0; death_certificate=0; marriage_certificate=0 }
$pendingPaddleDoc = New-Object System.Collections.Generic.Queue[string]

Get-Content $logPath | ForEach-Object {
    $line = $_
    if ($line -match 'OCRService: Sending image to PaddleOCR\s*\{.*"document_type":"([^"]+)"') {
        $d = $matches[1]
        if ($paddleSend.ContainsKey($d)) {
            $paddleSend[$d]++
            $pendingPaddleDoc.Enqueue($d)
        }
        return
    }
    if ($line -match 'OCRService: PaddleOCR extraction successful\s*\{') {
        if ($pendingPaddleDoc.Count -gt 0) {
            $d2 = $pendingPaddleDoc.Dequeue()
            if ($paddleSuccess.ContainsKey($d2)) { $paddleSuccess[$d2]++ }
        }
        return
    }
    if ($line -match 'PaddleOCR service is unreachable|Connection to PaddleOCR failed|PaddleOCR unavailable') {
        if ($pendingPaddleDoc.Count -gt 0) {
            [void]$pendingPaddleDoc.Dequeue()
        }
        return
    }
}

$artifactRows = @()
Get-ChildItem 'paddle_ocr_service' -Filter '*.json' | ForEach-Object {
    try {
        $j = Get-Content $_.FullName -Raw | ConvertFrom-Json
        $artifactRows += [pscustomobject]@{
            file = $_.Name
            success = $j.success
            confidence = $j.confidence
            word_count = $j.word_count
            line_count = $j.line_count
            processing_time_ms = $j.processing_time_ms
        }
    } catch {
        $artifactRows += [pscustomobject]@{ file=$_.Name; success=$null; confidence=$null; word_count=$null; line_count=$null; processing_time_ms=$null }
    }
}

Write-Output '===TESSERACT_METRICS==='
$tesseractRows | ConvertTo-Json -Compress
Write-Output '===PADDLE_METRICS==='
$paddleRows | ConvertTo-Json -Compress
Write-Output '===ENGINE_CLASSIFIED_COUNTS==='
($classified | Group-Object engine,doc | Select-Object Name,Count | Sort-Object Name | ConvertTo-Json -Compress)
Write-Output '===PADDLE_ATTEMPTS_SUCCESS==='
[pscustomobject]@{
    attempts=$paddleSend
    successes=$paddleSuccess
} | ConvertTo-Json -Compress
Write-Output '===RUN_TOTALS==='
[pscustomobject]@{
    total_runs=($runs | Measure-Object).Count
    completed_target_runs=($completedTarget | Measure-Object).Count
    completed_paddle_runs=(($classified | Where-Object engine -eq 'paddle' | Measure-Object).Count)
    completed_tesseract_runs=(($classified | Where-Object engine -eq 'tesseract' | Measure-Object).Count)
    completed_hybrid_runs=(($classified | Where-Object engine -eq 'hybrid' | Measure-Object).Count)
    failed_runs=(($runs | Where-Object failed | Measure-Object).Count)
} | ConvertTo-Json -Compress
Write-Output '===ARTIFACT_JSON==='
$artifactRows | Sort-Object file | ConvertTo-Json -Compress
