$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Web
$u = 'http://127.0.0.1:8000/en/s?where=Antwerp%20Downtown&city=Antwerp&country=Belgium&latitude=51.210998&longitude=4.45074&unified_location_id=3038777513&provider=mixed&date_from=2026-05-05&date_to=2026-05-12&start_time=09:00&end_time=09:00&age=35&currency=EUR'
$r = Invoke-WebRequest -UseBasicParsing $u
$m = [regex]::Match($r.Content, 'data-page="([^"]+)"')
if (-not $m.Success) { throw 'data-page not found' }
$json = [System.Web.HttpUtility]::HtmlDecode($m.Groups[1].Value)
$page = $json | ConvertFrom-Json -Depth 100
$vehicles = @($page.props.vehicles.data)
$internal = @($vehicles | Where-Object { $_.source -eq 'internal' })
[PSCustomObject]@{
  component = $page.component
  totalVehicles = $vehicles.Count
  internalCount = $internal.Count
  providerStatus = $page.props.providerStatus
  firstInternal = ($internal | Select-Object -First 1)
} | ConvertTo-Json -Depth 100
