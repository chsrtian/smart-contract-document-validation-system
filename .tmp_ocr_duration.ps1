$log='storage/logs/laravel.log'
$targetDocs=@('birth_certificate','death_certificate','marriage_certificate')
$runs=New-Object System.Collections.Generic.List[object]
$current=$null

function Parse-Time([string]$line){
 if($line -match '^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]'){ return [datetime]::ParseExact($matches[1],'yyyy-MM-dd HH:mm:ss',$null) }
 return $null
}

Get-Content $log | ForEach-Object {
 $line=$_
 if($line -match 'Starting (enhanced )?OCR processing\s*\{.*"type":"([^"]+)"'){
   if($current){ $runs.Add($current)|Out-Null }
   $current=[pscustomobject]@{doc=$matches[2]; start=(Parse-Time $line); end=$null; completed=$false; paddle=$false; tess=$false}
   return
 }
 if(-not $current){ return }
 if($line -match 'OCRService: PaddleOCR extraction successful'){ $current.paddle=$true; return }
 if($line -match 'Starting (enhanced )?Tesseract OCR'){ $current.tess=$true; return }
 if($line -match 'OCR processing completed successfully\s*\{"confidence":'){ $current.completed=$true; $current.end=(Parse-Time $line); $runs.Add($current)|Out-Null; $current=$null; return }
 if($line -match 'OCR processing failed\s*\{'){ $runs.Add($current)|Out-Null; $current=$null; return }
}
if($current){ $runs.Add($current)|Out-Null }

$done=$runs|?{ $_.completed -and ($targetDocs -contains $_.doc) -and $_.start -and $_.end }|%{
 $eng='unknown'; if($_.paddle -and -not $_.tess){$eng='paddle'} elseif($_.tess -and -not $_.paddle){$eng='tesseract'} elseif($_.tess -and $_.paddle){$eng='hybrid'}
 [pscustomobject]@{engine=$eng; doc=$_.doc; sec=[math]::Round(($_.end-$_.start).TotalSeconds,1)}
}
$summary=$done|?{ $_.engine -in @('paddle','tesseract') }|Group-Object engine,doc|%{
 $avg=[math]::Round(($_.Group|Measure-Object sec -Average).Average,1)
 $min=[math]::Round(($_.Group|Measure-Object sec -Minimum).Minimum,1)
 $max=[math]::Round(($_.Group|Measure-Object sec -Maximum).Maximum,1)
 [pscustomobject]@{key=$_.Name; count=$_.Count; avg_sec=$avg; min_sec=$min; max_sec=$max}
}|Sort-Object key
$summary|ConvertTo-Json -Compress
