param(
    [Parameter(Mandatory=$true)][string]$ProjectPath,
    [Parameter(Mandatory=$true)][string]$ZipName
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

try {
    $ProjectPath = (Resolve-Path -LiteralPath $ProjectPath).Path.TrimEnd('\')
    $ZipName = [System.IO.Path]::GetFileNameWithoutExtension($ZipName.Trim())
    if ([string]::IsNullOrWhiteSpace($ZipName) -or $ZipName -match '[\\/:*?"<>|]') {
        throw 'ZIP filename is invalid. Use a simple name such as InsitePortal-Azure-Deployment.'
    }
    foreach ($required in @('artisan', 'composer.json', 'composer.lock', 'public\build\manifest.json')) {
        if (-not (Test-Path -LiteralPath (Join-Path $ProjectPath $required))) {
            throw "Missing $required. Run npm run build if the frontend manifest is missing."
        }
    }

    $outputRoot = Join-Path (Split-Path -Parent $ProjectPath) 'IRAD-Azure-Deploy'
    $stage = Join-Path $outputRoot '_staging'
    $zip = Join-Path $outputRoot ($ZipName + '.zip')
    if ($outputRoot -eq $ProjectPath) { throw 'Deployment workspace must not be the source folder.' }

    Write-Host "`nSource: $ProjectPath"
    Write-Host "Workspace: $outputRoot"
    Write-Host 'IMPORTANT: .env files and development data will NOT be packaged.'
    New-Item -ItemType Directory -Force -Path $outputRoot | Out-Null
    if (Test-Path -LiteralPath $stage) { Remove-Item -LiteralPath $stage -Recurse -Force }
    New-Item -ItemType Directory -Force -Path $stage | Out-Null

    # Exclude folders by ABSOLUTE PATH to avoid excluding matching nested names.
    # In particular, root storage must be omitted but app/Storage MUST be copied.
    # robocopy copies existing local edits and untracked application files as well.
    $excludeDirs = @(
        '.git', '.github', '.idea', '.vscode', '.fleet', 'node_modules',
        'vendor', 'tests', 'storage', '_packages', '_staging', 'docs', 'coverage',
        'test-results', 'bootstrap\cache', 'public\storage'
    ) | ForEach-Object { Join-Path $ProjectPath $_ }
    $excludeFiles = @('.env*', 'auth.json', 'AZURE-INFO', 'phpunit.xml',
        '.phpunit.result.cache', '*.log', '*.sqlite', '*.sqlite3', '*.pem', '*.pfx',
        '*.p12', '*.key', 'npm-debug.log', 'yarn-error.log', 'phpinfo.php')
    Write-Host "`n[1/6] Copying deployable application files..."
    $robocopyArgs = @($ProjectPath, $stage, '/E', '/XJ', '/R:1', '/W:1', '/NFL', '/NDL', '/NP', '/NJH', '/NJS', '/XD') +
        $excludeDirs + @('/XF') + $excludeFiles
    & robocopy.exe @robocopyArgs | Out-Host
    if ($LASTEXITCODE -ge 8) { throw "Robocopy failed with code $LASTEXITCODE" }

    # public/storage is normally a symlink to local user files; do not ship it.
    $publicStorage = Join-Path $stage 'public\storage'
    if (Test-Path -LiteralPath $publicStorage) { Remove-Item -LiteralPath $publicStorage -Recurse -Force }
    $publicHot = Join-Path $stage 'public\hot'
    if (Test-Path -LiteralPath $publicHot) { Remove-Item -LiteralPath $publicHot -Force }

    Write-Host '[2/6] Creating Laravel runtime directories...'
    foreach ($sub in @('bootstrap\cache', 'storage\app\private', 'storage\app\public',
        'storage\framework\cache\data', 'storage\framework\sessions',
        'storage\framework\views', 'storage\logs')) {
        $dir = Join-Path $stage $sub
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
        # ZIP archives do not reliably preserve empty directories.
        [System.IO.File]::WriteAllText((Join-Path $dir '.gitignore'), "*`n!.gitignore`n")
    }

    Write-Host '[3/6] Installing production Composer dependencies in STAGING only...'
    $composer = Get-Command composer -ErrorAction SilentlyContinue
    if (-not $composer) { throw 'Composer was not found in PATH.' }
    Push-Location $stage
    try {
        & composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts
        if ($LASTEXITCODE -ne 0) { throw "Composer install failed with exit code $LASTEXITCODE" }
    }
    finally { Pop-Location }
    if (-not (Test-Path -LiteralPath (Join-Path $stage 'vendor\autoload.php'))) {
        throw 'Production vendor/autoload.php was not created.'
    }

    Write-Host '[4/6] Discovering Laravel packages in staging (no network required at runtime)...'
    Push-Location $stage
    try {
        & php artisan package:discover --ansi
        if ($LASTEXITCODE -ne 0) { throw "Laravel package discovery failed with exit code $LASTEXITCODE" }
    }
    finally { Pop-Location }

    Write-Host '[5/6] Validating offline runtime files and scanning for sensitive filenames...'
    foreach ($required in @('vendor\autoload.php', 'vendor\composer\installed.php',
        'bootstrap\cache\packages.php',
        'public\build\manifest.json', 'public\index.php', 'composer.lock')) {
        if (-not (Test-Path -LiteralPath (Join-Path $stage $required))) {
            throw "Offline runtime file is missing: $required"
        }
    }
    foreach ($required in @('artisan', 'vendor\autoload.php', 'public\build\manifest.json',
        'app\Storage\AzureBlobAdapter.php', 'config\insite_storage.php',
        'startup.sh', 'insite-nginx.conf')) {
        if (-not (Test-Path -LiteralPath (Join-Path $stage $required))) {
            throw "Required deployment file is missing: $required"
        }
    }
    $bad = @(Get-ChildItem -LiteralPath $stage -File -Recurse -Force | Where-Object {
        $_.Name -like '.env*' -or $_.Name -eq 'auth.json' -or $_.Extension -in @('.pem', '.key', '.pfx', '.p12', '.sqlite', '.sqlite3')
    })
    if ($bad.Count -gt 0) {
        throw ('Sensitive or database files detected in staging: ' + (($bad | Select-Object -First 5 -ExpandProperty FullName) -join ', '))
    }

    Write-Host '[6/6] Creating Linux-compatible offline Azure ZIP (forward-slash paths)...'
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    if (Test-Path -LiteralPath $zip) { Remove-Item -LiteralPath $zip -Force }

    # IMPORTANT: ZipFile.CreateFromDirectory on Windows can emit backslash
    # entry names. Explicitly create each ZIP entry using / to ensure Linux
    # Azure App Service extracts public/index.php rather than public\index.php.
    $archive = [System.IO.Compression.ZipFile]::Open(
        $zip, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        $stagePrefix = $stage.TrimEnd('\', '/') + [System.IO.Path]::DirectorySeparatorChar
        $files = @(Get-ChildItem -LiteralPath $stage -Recurse -Force -File)
        foreach ($file in $files) {
            $relative = $file.FullName.Substring($stagePrefix.Length).Replace('\', '/')
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $archive, $file.FullName, $relative,
                [System.IO.Compression.CompressionLevel]::Optimal)
        }
    }
    finally {
        $archive.Dispose()
    }

    # Fail the build if any ZIP member has backslashes or a required file is absent.
    $archive = [System.IO.Compression.ZipFile]::OpenRead($zip)
    try {
        $names = @($archive.Entries | ForEach-Object { $_.FullName })
        $badPaths = @($names | Where-Object { $_.Contains('\') -or $_.StartsWith('/') })
        if ($badPaths.Count -gt 0) {
            throw ('ZIP contains non-Linux paths: ' + (($badPaths | Select-Object -First 3) -join ', '))
        }
        foreach ($required in @('startup.sh', 'insite-nginx.conf',
            'public/index.php', 'vendor/autoload.php',
            'public/build/manifest.json', 'app/Storage/AzureBlobAdapter.php')) {
            if ($names -cnotcontains $required) {
                throw "Required ZIP entry is missing: $required"
            }
        }
        Write-Host 'ZIP validation passed: Linux-style paths and all required files verified.'
    }
    finally {
        $archive.Dispose()
    }

    $zipInfo = Get-Item -LiteralPath $zip
    Write-Host "`nSUCCESS: $($zipInfo.FullName)"
    Write-Host ('ZIP size: {0:N1} MB' -f ($zipInfo.Length / 1MB))
    Write-Host "Staging directory: $stage"
    Write-Host 'Source application and local .env were not modified.'
    Write-Host 'OFFLINE: composer/npm are NOT required on the target. No install command should run there.'
    Write-Host 'NEXT: Verify private network access to MySQL, Blob, Key Vault, ADFS and deployment endpoint.'
    exit 0
}
catch {
    Write-Error $_.Exception.Message
    exit 1
}
