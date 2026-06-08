param(
    [string]$FtpHost = "c2821557.ferozo.com",
    [string]$FtpUser = "c2821557",
    [string]$FtpPass = "HGf@0n2*X6",
    [switch]$DryRun
)

$FTP = New-Object System.Net.WebClient
$FTP.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
$FTP.BaseAddress = "ftp://$FtpHost"

function DeleteFtpPath($remotePath) {
    if ($DryRun) { Write-Host "  [DRY]   DELETE /$remotePath"; return }
    try {
        $req = [System.Net.WebRequest]::Create("ftp://$FtpHost/$remotePath")
        $req.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
        $req.GetResponse().Close()
        Write-Host "  DEL /$remotePath"
    } catch {
        Write-Host "  FAIL /$remotePath : $_"
    }
}

# Files that were uploaded to wrong root (anything that should be inside public_html/)
$misplacedFiles = @(
    "login.php", "logout.php", "index.php", "logocel.png",
    "setup_admin_pass.php", "setup_taller.php", "suscripcion_vencida.php"
)

$misplacedDirs = @(
    "admin", "modulos", "includes", "fulltaller"
)

Write-Host "=== CLEANUP: Removing misplaced files from FTP root ==="
if ($DryRun) { Write-Host "--- DRY RUN ---" }
Write-Host ""

foreach ($f in $misplacedFiles) {
    DeleteFtpPath $f
}

# For directories, try to delete files inside first, then the directory
$dirFiles = @{}
foreach ($d in $misplacedDirs) {
    try {
        $listReq = [System.Net.WebRequest]::Create("ftp://$FtpHost/$d/")
        $listReq.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
        $listReq.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
        $resp = $listReq.GetResponse()
        $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
        $lines = @()
        while ($null -ne ($line = $reader.ReadLine())) { $lines += $line }
        $reader.Close()
        $resp.Close()
        $dirFiles[$d] = $lines
    } catch {
        Write-Host "  SKIP /$d/ (not found or error: $_ )"
    }
}

Write-Host ""
Write-Host "Done. Use FileZilla to verify and manually remove any remaining files."
