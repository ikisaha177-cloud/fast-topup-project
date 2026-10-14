param(
  [Parameter(Mandatory=$true)][string]$Svg,
  [Parameter(Mandatory=$true)][string]$Png,
  [int]$W = 760,
  [int]$H = 1100,
  [int]$Scale = 2,
  [string]$Bg = "FFFFFFFF"
)
$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"
$tmp = "$env:TEMP\ftu_chrome"
New-Item -ItemType Directory -Force -Path $tmp | Out-Null
$svgFull = (Resolve-Path $Svg).Path
$pngFull = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $Png))
$url = "file:///" + ($svgFull -replace "\\", "/").Replace(" ", "%20")
$pane = Start-Process -FilePath $chrome -ArgumentList @(
  "--headless=new","--disable-gpu","--hide-scrollbars",
  "--user-data-dir=`"$tmp`"",
  "--force-device-scale-factor=$Scale",
  "--default-background-color=$Bg",
  "--screenshot=`"$pngFull`"",
  "--window-size=$W,$H",
  "`"$url`""
) -NoNewWindow -Wait -PassThru -RedirectStandardError "$env:TEMP\ftu_err.txt"
if (Test-Path $pngFull) { Write-Host ("ok  {0}  ({1} bytes)" -f $Png, (Get-Item $pngFull).Length) }
else { Write-Host "FAILED $Png"; Get-Content "$env:TEMP\ftu_err.txt" -Tail 5 }
