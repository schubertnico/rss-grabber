<#
.SYNOPSIS
    Baut ein verteilbares Release-ZIP des RSS Grabbers fuer die Auslieferung an
    Endnutzer.

.DESCRIPTION
    Packt nur die Laufzeit-Dateien der Anwendung. Bewusst NICHT enthalten:
      - Docker-Umgebung (.docker/)
      - Dev-/Test-Konfigurationen (composer.*, phpstan.neon, phpunit.xml,
        package*.json, playwright.config.ts)
      - lokale Konfiguration inc/config.php (wird vom Installer erzeugt)
      - Tests (tests/), interne Doku (docs/), Build-Tooling (build/)
      - vendor/, node_modules/, .git, .idea, .claude, Cache-/Report-Ordner
      - README.md, CHANGELOG.md, altes Installationsanleitung_2.00.pdf

    Die ZIP-Eintraege verwenden Forward-Slashes (System.IO.Compression), damit
    das Archiv auf Linux-Webspace sauber entpackt.

.PARAMETER Version
    Optionale Versionsbezeichnung fuer den Dateinamen. Standard: aus
    inc/config.php ($script_version), Fallback "3.0".

.PARAMETER Tag
    Erzeugt nach dem Build einen annotierten Git-Tag in SemVer-Form
    "vX.Y.Z" (z. B. v3.0.0) am aktuellen HEAD (falls noch nicht vorhanden).

.PARAMETER Push
    Pusht den Git-Tag nach origin (nur zusammen mit -Tag sinnvoll).
#>
param(
    [string]$Version = "",
    [switch]$Tag,
    [switch]$Push
)

$ErrorActionPreference = "Stop"
$root = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path

# --- Version ermitteln ---
$ver = "3.0"
$cfgPath = Join-Path $root "inc\config.php"
if (Test-Path $cfgPath) {
    $cfg = Get-Content $cfgPath -Raw
    if ($cfg -match "\`$script_version\s*=\s*'([^']+)'") { $ver = $Matches[1] }
}
if ($Version) { $ver = $Version }

# SemVer-Form (X.Y.Z) fuer den Git-Tag ableiten; der ZIP-Name nutzt die
# (ggf. kuerzere) Anzeige-Version.
$semverParts = @($ver -split '\.')
while ($semverParts.Count -lt 3) { $semverParts += '0' }
$semver = ($semverParts[0..2]) -join '.'

# --- Ausschluesse ---
$excludeDirs = @(
    '.git', '.idea', '.claude', '.docker', 'vendor', 'node_modules',
    'tests', 'docs', 'build', '.phpunit.cache', 'test-results',
    'playwright-report', 'coverage'
)
$excludeFiles = @(
    'inc/config.php',
    'composer.json', 'composer.lock',
    'phpunit.xml', 'phpstan.neon',
    'package.json', 'package-lock.json', 'playwright.config.ts',
    '.gitignore', '.gitattributes',
    'README.md', 'CHANGELOG.md',
    'Installationsanleitung_2.00.pdf'
)

$zipName = "rss-grabber-v$ver.zip"
$zipPath = Join-Path $root "build\$zipName"
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

# --- Dateien einsammeln ---
$rootLen = $root.Length + 1
$files = Get-ChildItem -Path $root -Recurse -File -Force
$selected = New-Object System.Collections.Generic.List[object]
foreach ($f in $files) {
    $rel = $f.FullName.Substring($rootLen) -replace '\\', '/'
    $top = ($rel -split '/')[0]
    if ($excludeDirs -contains $top) { continue }
    if ($excludeFiles -contains $rel) { continue }
    if ($rel -like 'rss-grabber-v*.zip') { continue }
    $selected.Add(@{ Full = $f.FullName; Rel = $rel })
}

# --- ZIP schreiben (Forward-Slash-Eintraege, Top-Ordner "rss-grabber/") ---
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($item in $selected) {
        $entry = "rss-grabber/" + $item.Rel
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip, $item.Full, $entry,
            [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
}
finally {
    $zip.Dispose()
}

$sizeKb = [math]::Round((Get-Item $zipPath).Length / 1KB, 1)
Write-Host "Release-ZIP erstellt: $zipPath"
Write-Host ("Version: v{0}  |  Dateien: {1}  |  Groesse: {2} KB" -f $ver, $selected.Count, $sizeKb)

# --- Optional: Git-Tag erzeugen / pushen ---
if ($Tag) {
    $tagName = "v$semver"
    $existing = & git -C $root tag --list $tagName
    if ([string]::IsNullOrWhiteSpace($existing)) {
        & git -C $root tag -a $tagName -m "RSS Grabber free $tagName"
        if ($LASTEXITCODE -eq 0) { Write-Host "Git-Tag $tagName erstellt." }
        else { Write-Host "Git-Tag $tagName konnte nicht erstellt werden." }
    }
    else {
        Write-Host "Git-Tag $tagName existiert bereits."
    }
    if ($Push) {
        & git -C $root push origin $tagName
        if ($LASTEXITCODE -eq 0) { Write-Host "Git-Tag $tagName nach origin gepusht." }
        else { Write-Host "Push von $tagName fehlgeschlagen (evtl. bereits auf origin vorhanden)." }
    }
}
