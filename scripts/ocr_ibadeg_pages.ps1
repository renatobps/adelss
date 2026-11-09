param(
    [Parameter(Mandatory=$true)][string]$ImageDirectory,
    [Parameter(Mandatory=$true)][string]$OutputPath
)

# OCR local do Windows: os arquivos não são enviados para serviços externos.
Add-Type -AssemblyName System.Runtime.WindowsRuntime
[Windows.Storage.StorageFile, Windows.Storage, ContentType=WindowsRuntime] | Out-Null
[Windows.Storage.Streams.IRandomAccessStreamWithContentType, Windows.Storage.Streams, ContentType=WindowsRuntime] | Out-Null
[Windows.Graphics.Imaging.BitmapDecoder, Windows.Graphics.Imaging, ContentType=WindowsRuntime] | Out-Null
[Windows.Graphics.Imaging.SoftwareBitmap, Windows.Graphics.Imaging, ContentType=WindowsRuntime] | Out-Null
[Windows.Media.Ocr.OcrEngine, Windows.Foundation, ContentType=WindowsRuntime] | Out-Null
[Windows.Media.Ocr.OcrResult, Windows.Foundation, ContentType=WindowsRuntime] | Out-Null

$ocrTaskMethod = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
    $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 -and
    $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1'
} | Select-Object -First 1

function Wait-OcrOperation($Operation, [Type]$ResultType) {
    $ocrTask = $ocrTaskMethod.MakeGenericMethod($ResultType).Invoke($null, @($Operation))
    $ocrTask.Wait()
    return $ocrTask.Result
}

$ocrEngine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
if ($null -eq $ocrEngine) { throw 'Nenhum reconhecedor OCR local disponível.' }
Write-Host ('Idioma OCR: ' + $ocrEngine.RecognizerLanguage.LanguageTag)
$ocrPages = @()
foreach ($ocrImage in (Get-ChildItem -LiteralPath $ImageDirectory -Filter '*.png' | Sort-Object Name)) {
    $ocrFile = Wait-OcrOperation ([Windows.Storage.StorageFile]::GetFileFromPathAsync($ocrImage.FullName)) ([Windows.Storage.StorageFile])
    $ocrStream = Wait-OcrOperation ($ocrFile.OpenReadAsync()) ([Windows.Storage.Streams.IRandomAccessStreamWithContentType])
    try {
        $ocrDecoder = Wait-OcrOperation ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($ocrStream)) ([Windows.Graphics.Imaging.BitmapDecoder])
        $ocrBitmap = Wait-OcrOperation ($ocrDecoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
        try {
            $ocrResult = Wait-OcrOperation ($ocrEngine.RecognizeAsync($ocrBitmap)) ([Windows.Media.Ocr.OcrResult])
            $ocrText = ($ocrResult.Lines | ForEach-Object { $_.Text }) -join "`n"
            $ocrPages += $ocrText
            [System.IO.File]::WriteAllText($OutputPath, (ConvertTo-Json -InputObject $ocrPages -Depth 4), [System.Text.UTF8Encoding]::new($false))
            Write-Host ($ocrImage.Name + ': ' + $ocrText.Length + ' caracteres')
        } finally { $ocrBitmap.Dispose() }
    } finally { $ocrStream.Dispose() }
}
