param(
    [Parameter(Mandatory = $true)]
    [string]$ProjectPath,

    [Parameter(Mandatory = $true)]
    [string]$ZipName
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ProjectPath = (Resolve-Path -LiteralPath $ProjectPath -ErrorAction Stop).ProviderPath.TrimEnd('\', '/')
if (-not (Test-Path -LiteralPath (Join-Path $ProjectPath 'composer.json') -PathType Leaf)) {
    throw 'Expected composer.json in the project root. Place this packager in the Laravel project root.'
}

# Treat the requested name as a simple filename, never as a path.
$ZipName = $ZipName.Trim()
if ($ZipName.EndsWith('.zip', [System.StringComparison]::OrdinalIgnoreCase)) {
    $ZipName = $ZipName.Substring(0, $ZipName.Length - 4)
}
if ($ZipName -notmatch '^[A-Za-z0-9][A-Za-z0-9._-]{0,99}$' -or $ZipName -eq '.' -or $ZipName -eq '..') {
    throw 'ZIP name must be 1-100 characters: letters, digits, dots, underscores, or hyphens (no paths).'
}

$OutputDirectory = Join-Path $ProjectPath '_packages'
$ZipPath = Join-Path $OutputDirectory ($ZipName + '.zip')
New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
$TempZipPath = Join-Path $OutputDirectory ($ZipName + '.' + [guid]::NewGuid().ToString('N') + '.tmp')

# Directory names are excluded anywhere in the source tree.
$ExcludedDirectoryNames = @(
    '.git', '.idea', '.vscode', 'node_modules', 'vendor', '_packages',
    '.next', '.nuxt', '.turbo', 'coverage', '.phpunit-watcher.yml'
)

# Project-root-relative directories. Keep docs, modules, tests, and source.
$ExcludedRelativeDirectories = @(
    'bootstrap/cache', 'public/build', 'public/storage',
    'storage/app', 'storage/framework', 'storage/logs',
    'storage/debugbar', 'storage/telescope', 'storage/clockwork',
    'tests/Fixtures/private'
)

$ExcludedFileNames = @(
    '.phpunit.result.cache', 'auth.json', '.npmrc', '.yarnrc', '.netrc',
    'npm-debug.log', 'yarn-error.log', 'Thumbs.db', '.DS_Store',
    'phpinfo.php', 'id_rsa', 'id_ed25519', 'known_hosts',
    'AZURE-INFO', '_ide_helper.php', '.phpstorm.meta.php',
    'desktop.ini'
)

$ExcludedExtensions = @(
    '.zip', '.7z', '.rar', '.tar', '.gz', '.log', '.sqlite', '.sqlite3',
    '.sql', '.bak', '.backup', '.dump', '.pem', '.key', '.p12', '.pfx',
    '.crt', '.jks', '.keystore'
)

function Test-IsExcluded {
    param([System.IO.FileInfo]$File, [string]$RelativePath)

    if (($File.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) { return $true }
    $parts = $RelativePath.Split('/')
    foreach ($name in $ExcludedDirectoryNames) {
        if ($parts.Count -gt 1 -and $parts[0..($parts.Count - 2)] -contains $name) { return $true }
    }
    foreach ($prefix in $ExcludedRelativeDirectories) {
        if ($RelativePath.Equals($prefix, [System.StringComparison]::OrdinalIgnoreCase) -or
            $RelativePath.StartsWith($prefix + '/', [System.StringComparison]::OrdinalIgnoreCase)) {
            return $true
        }
    }

    # Only the template .env.example is safe by default. Exclude .env.testing,
    # .env.local, .env.production, .env-testing, etc.
    if ($File.Name -match '^\.env([.-]|$)' -and $File.Name -ne '.env.example') { return $true }
    if ($ExcludedFileNames -contains $File.Name) { return $true }
    if ($File.Name -eq '_source-package-manifest.json') { return $true }
    if ($ExcludedExtensions -contains $File.Extension.ToLowerInvariant()) { return $true }

    # Common credentials/export filenames and server-side secrets.
    # NOTE: Filename rules cannot detect credentials embedded in ordinary notes/code.
    # Always inspect the finished ZIP before sharing.
    if ($File.Name -match '(?i)(^|[._-])(azure[-_]?info|passwords?|secrets?|credentials?|private[-_]?key|database[-_]?dump)([._-]|$)') { return $true }
    return $false
}

function Get-GitValue {
    param([string[]]$GitArguments)
    try {
        $value = & git -C $ProjectPath @GitArguments 2>$null
        if ($LASTEXITCODE -eq 0) { return (($value | Out-String).Trim()) }
    } catch { }
    return 'unavailable'
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$branch = Get-GitValue -GitArguments @('branch', '--show-current')
$commit = Get-GitValue -GitArguments @('rev-parse', '--short=12', 'HEAD')
$gitChanges = Get-GitValue -GitArguments @('status', '--porcelain')
$workingTree = if ($gitChanges -eq 'unavailable') { 'unknown' } elseif ($gitChanges -eq '') { 'clean' } else { 'modified' }

$fileCount = 0
$totalBytes = [long]0
$includedPaths = New-Object 'System.Collections.Generic.List[string]'
$archive = $null

try {
    $archive = [System.IO.Compression.ZipFile]::Open($TempZipPath, [System.IO.Compression.ZipArchiveMode]::Create)
    $rootLength = $ProjectPath.Length + 1

    Get-ChildItem -LiteralPath $ProjectPath -File -Recurse -Force -ErrorAction Stop |
        Sort-Object FullName | ForEach-Object {
            if ($_.FullName.Length -lt $rootLength) { return }
            $relativePath = $_.FullName.Substring($rootLength).Replace('\', '/')
            if (-not (Test-IsExcluded -File $_ -RelativePath $relativePath)) {
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                    $archive, $_.FullName, $relativePath,
                    [System.IO.Compression.CompressionLevel]::Optimal
                ) | Out-Null
                $includedPaths.Add($relativePath)
                $fileCount++
                $totalBytes += $_.Length
            }
        }

    if ($fileCount -eq 0) { throw 'No source files were selected.' }

    # Metadata is generated in memory, not saved to the source tree.
    $metadata = [ordered]@{
        package_format = 'Insite/Laravel Vue source snapshot v1'
        project = (Split-Path -Leaf $ProjectPath)
        created_utc = [DateTime]::UtcNow.ToString('yyyy-MM-ddTHH:mm:ssZ')
        git_branch = $branch
        git_commit = $commit
        git_working_tree = $workingTree
        included_file_count = $fileCount
        included_source_bytes = $totalBytes
        notes = 'Source snapshot only. Exclusions are heuristic; review before sharing. No vendor/node_modules, databases or personal uploads.'
    }
    $manifestEntry = $archive.CreateEntry('_source-package-manifest.json', [System.IO.Compression.CompressionLevel]::Optimal)
    $writer = New-Object System.IO.StreamWriter($manifestEntry.Open(), (New-Object System.Text.UTF8Encoding($false)))
    try { $writer.Write(($metadata | ConvertTo-Json -Depth 5)) } finally { $writer.Dispose() }
}
finally {
    if ($null -ne $archive) { $archive.Dispose() }
}

try {
    $verify = [System.IO.Compression.ZipFile]::OpenRead($TempZipPath)
    try {
        if ($verify.Entries.Count -ne ($fileCount + 1)) { throw 'ZIP verification failed: entry count mismatch.' }
        foreach ($entry in $verify.Entries) {
            if ($entry.FullName.StartsWith('/') -or $entry.FullName -match '(^|/)\.\.(/|$)') {
                throw "ZIP contains invalid path: $($entry.FullName)"
            }
        }
        if ($null -eq $verify.GetEntry('_source-package-manifest.json')) {
            throw 'ZIP verification failed: missing manifest.'
        }
    } finally { $verify.Dispose() }

    # Never remove the existing package until the replacement is complete and verified.
    Move-Item -LiteralPath $TempZipPath -Destination $ZipPath -Force
} catch {
    if (Test-Path -LiteralPath $TempZipPath) { Remove-Item -LiteralPath $TempZipPath -Force }
    throw
}

$zipInfo = Get-Item -LiteralPath $ZipPath
Write-Host ''
Write-Host 'Source package created and verified.' -ForegroundColor Green
Write-Host "Files included: $fileCount"
Write-Host ('Source size:    {0:N2} MB' -f ($totalBytes / 1MB))
Write-Host ('ZIP size:       {0:N2} MB' -f ($zipInfo.Length / 1MB))
Write-Host "Git:            $branch @ $commit ($workingTree)"
Write-Host "Output:         $ZipPath"
Write-Host 'IMPORTANT: Review the ZIP for confidential files before uploading.' -ForegroundColor Yellow
