param(
    [string]$FtpHost = "c2821557.ferozo.com",
    [string]$FtpUser = "c2821557",
    [string]$FtpPass = "HGf@0n2*X6",
    [switch]$DryRun
)

$RepoRoot = "C:\xampp\htdocs"
$FTP = New-Object System.Net.WebClient
$FTP.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
$FTP.BaseAddress = "ftp://$FtpHost"

$createdDirs = @{}
$exclude = @('.gitignore', 'config_local.php', 'AGENTS.md', 'deploy.ps1', '*.gitkeep')

function ShouldExclude($file) {
    foreach ($e in $exclude) {
        if ($file -like $e) { return $true }
        if ($file.EndsWith("/$e") -or $file -eq $e) { return $true }
    }
    return $false
}

function CreateFtpDir($path) {
    if ([string]::IsNullOrEmpty($path) -or $createdDirs.ContainsKey($path)) { return }
    if ($DryRun) { Write-Host "  [DIR]  /$path"; return }
    try {
        $req = [System.Net.WebRequest]::Create("ftp://$FtpHost/$path")
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
        $req.GetResponse().Close()
    } catch { }
    $createdDirs[$path] = $true
}

function ToRemotePath($localPath) {
    # taller/ → root, modulos/ → modulos/
    if ($localPath -like "taller/*") {
        return $localPath.Substring(7)  # remove "taller/"
    } elseif ($localPath.StartsWith("taller/")) {
        return $localPath.Substring(7)
    }
    return $localPath
}

function UploadFile($localFullPath, $remotePath) {
    if ($DryRun) { Write-Host "  [UP]   /$remotePath"; return }
    try {
        $FTP.UploadFile("ftp://$FtpHost/$remotePath", 'STOR', $localFullPath)
        Write-Host "  OK /$remotePath"
    } catch {
        Write-Host "  FAIL /$remotePath : $_"
    }
}

# Get tracked files from git
$files = git -C $RepoRoot ls-files

Write-Host "=== DEPLOY TO $FtpHost ==="
if ($DryRun) { Write-Host "--- DRY RUN ---" }
Write-Host ""

$count = 0
foreach ($f in $files) {
    if (ShouldExclude $f) { continue }
    $remotePath = ToRemotePath $f
    $localFullPath = Join-Path $RepoRoot $f.Replace('/', '\')

    if (-not (Test-Path $localFullPath)) { continue }

    # Create parent directory
    $dir = Split-Path $remotePath -Parent
    if ($dir -ne '') {
        CreateFtpDir $dir.Replace('\', '/')
    }

    UploadFile $localFullPath $remotePath.Replace('\', '/')
    $count++
}

Write-Host ""
Write-Host "=== $count files uploaded ==="
