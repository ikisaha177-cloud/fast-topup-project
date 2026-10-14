# Extract true glyph outlines from a TTF via WPF's GlyphTypeface, dump to JSON.
# Usage: powershell -File extract_glyphs.ps1 -Font <ttf> -Chars "FASTOP-UR" -Out <json>
param(
  [Parameter(Mandatory=$true)][string]$Font,
  [Parameter(Mandatory=$true)][string]$Chars,
  [Parameter(Mandatory=$true)][string]$Out,
  [double]$Em = 1000.0
)
$ErrorActionPreference = "Stop"
Add-Type -AssemblyName PresentationCore
Add-Type -AssemblyName WindowsBase

$uri = New-Object System.Uri((Resolve-Path $Font).Path)
$gt = New-Object System.Windows.Media.GlyphTypeface($uri)

function Fmt([double]$v) { return [math]::Round($v, 3) }

$result = @{ font = (Split-Path $Font -Leaf); em = $Em; glyphs = @{} }

foreach ($ch in $Chars.ToCharArray()) {
  $code = [int][char]$ch
  if (-not $gt.CharacterToGlyphMap.ContainsKey($code)) { continue }
  $gi = $gt.CharacterToGlyphMap[$code]
  $outline = $gt.GetGlyphOutline($gi, $Em, $Em)

  $subpaths = @()
  foreach ($fig in $outline.Figures) {
    $cmds = @()
    $cmds += ("M {0} {1}" -f (Fmt $fig.StartPoint.X), (Fmt $fig.StartPoint.Y))
    foreach ($seg in $fig.Segments) {
      $t = $seg.GetType().Name
      if ($t -eq "LineSegment") {
        $cmds += ("L {0} {1}" -f (Fmt $seg.Point.X), (Fmt $seg.Point.Y))
      } elseif ($t -eq "PolyLineSegment") {
        foreach ($p in $seg.Points) { $cmds += ("L {0} {1}" -f (Fmt $p.X), (Fmt $p.Y)) }
      } elseif ($t -eq "BezierSegment") {
        $cmds += ("C {0} {1} {2} {3} {4} {5}" -f (Fmt $seg.Point1.X),(Fmt $seg.Point1.Y),(Fmt $seg.Point2.X),(Fmt $seg.Point2.Y),(Fmt $seg.Point3.X),(Fmt $seg.Point3.Y))
      } elseif ($t -eq "PolyBezierSegment") {
        $pts = $seg.Points
        for ($i = 0; $i + 2 -lt $pts.Count; $i += 3) {
          $cmds += ("C {0} {1} {2} {3} {4} {5}" -f (Fmt $pts[$i].X),(Fmt $pts[$i].Y),(Fmt $pts[$i+1].X),(Fmt $pts[$i+1].Y),(Fmt $pts[$i+2].X),(Fmt $pts[$i+2].Y))
        }
      } elseif ($t -eq "QuadraticBezierSegment") {
        $cmds += ("Q {0} {1} {2} {3}" -f (Fmt $seg.Point1.X),(Fmt $seg.Point1.Y),(Fmt $seg.Point2.X),(Fmt $seg.Point2.Y))
      } elseif ($t -eq "PolyQuadraticBezierSegment") {
        $pts = $seg.Points
        for ($i = 0; $i + 1 -lt $pts.Count; $i += 2) {
          $cmds += ("Q {0} {1} {2} {3}" -f (Fmt $pts[$i].X),(Fmt $pts[$i].Y),(Fmt $pts[$i+1].X),(Fmt $pts[$i+1].Y))
        }
      } else {
        throw ("Unhandled segment type: " + $t)
      }
    }
    if ($fig.IsClosed) { $cmds += "Z" }
    $subpaths += ,@($cmds)
  }

  $adv = [double]$gt.AdvanceWidths[$gi] * $Em
  # join subpaths with a rare delimiter so a 1-subpath glyph stays a plain string
  # (avoids PowerShell's scalar-vs-array JSON ambiguity)
  $joined = (($subpaths | ForEach-Object { $_ -join " " }) -join "|")
  $result.glyphs[$ch] = @{ advance = (Fmt $adv); d = $joined }
}

# hand-rolled JSON (ConvertTo-Json chokes on nested arrays-of-arrays)
$sb = New-Object System.Text.StringBuilder
[void]$sb.Append("{")
[void]$sb.Append('"font":' + ($result.font | ConvertTo-Json -Compress) + ",")
[void]$sb.Append(('"em":' + $Em) + ",")
[void]$sb.Append('"glyphs":{')
$firstG = $true
foreach ($key in $result.glyphs.Keys) {
  if (-not $firstG) { [void]$sb.Append(",") }
  $firstG = $false
  $g = $result.glyphs[$key]
  $dJson = ($g.d | ConvertTo-Json -Compress)
  [void]$sb.Append(('"' + $key + '":{"advance":' + $g.advance + ',"d":' + $dJson + "}"))
}
[void]$sb.Append("}}")
$json = $sb.ToString()
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $Out), $json, (New-Object System.Text.UTF8Encoding($false)))
Write-Host ("wrote {0} ({1} glyphs) from {2}" -f $Out, $result.glyphs.Count, (Split-Path $Font -Leaf))
