param(
    [Parameter(Mandatory = $true)]
    [string]$Url
)

$response = Invoke-WebRequest -UseBasicParsing $Url

$match = [regex]::Match($response.Content, 'data-page="([^"]+)"')
if (-not $match.Success) {
    throw "data-page not found"
}

Add-Type -AssemblyName System.Web
Add-Type -AssemblyName System.Web.Extensions
$json = [System.Web.HttpUtility]::HtmlDecode($match.Groups[1].Value)
$serializer = New-Object System.Web.Script.Serialization.JavaScriptSerializer
$serializer.MaxJsonLength = [int]::MaxValue
$page = $serializer.DeserializeObject($json)
$vehicles = @($page.props.vehicles.data)
$providerStatus = @($page.props.providerStatus)

$sourceCounts = @{}
foreach ($vehicle in $vehicles) {
    $source = [string]$vehicle.source
    if ([string]::IsNullOrWhiteSpace($source)) {
        $source = "(unknown)"
    }

    if (-not $sourceCounts.ContainsKey($source)) {
        $sourceCounts[$source] = 0
    }

    $sourceCounts[$source]++
}

$internalVehicle = $vehicles | Where-Object { $_.source -eq "internal" } | Select-Object -First 1
$firstVehicle = $vehicles | Select-Object -First 1
$firstVehicleSummary = $null
$firstInternalSummary = $null

if ($null -ne $firstVehicle) {
    $firstVehicleSummary = [PSCustomObject]@{
        source = $firstVehicle.source
        provider_vehicle_id = $firstVehicle.provider_vehicle_id
        provider_product_id = $firstVehicle.provider_product_id
        provider_rate_id = $firstVehicle.provider_rate_id
        availability_status = $firstVehicle.availability_status
        display_name = $firstVehicle.display_name
        provider_pickup_id = $firstVehicle.provider_pickup_id
        provider_return_id = $firstVehicle.provider_return_id
        provider_dropoff_id = $firstVehicle.provider_dropoff_id
        latitude = $firstVehicle.latitude
        longitude = $firstVehicle.longitude
        full_vehicle_address = $firstVehicle.full_vehicle_address
    }
}

if ($null -ne $internalVehicle) {
    $firstInternalSummary = [PSCustomObject]@{
        id = $internalVehicle.id
        source = $internalVehicle.source
        provider_vehicle_id = $internalVehicle.provider_vehicle_id
        provider_product_id = $internalVehicle.provider_product_id
        availability_status = $internalVehicle.availability_status
        display_name = $internalVehicle.display_name
        full_vehicle_address = $internalVehicle.full_vehicle_address
        latitude = $internalVehicle.latitude
        longitude = $internalVehicle.longitude
    }
}

[PSCustomObject]@{
    component = $page.component
    via_gateway = $page.props.via_gateway
    total_vehicles = $vehicles.Count
    source_counts = $sourceCounts
    provider_status = $providerStatus
    first_vehicle = $firstVehicleSummary
    first_internal_vehicle = $firstInternalSummary
} | ConvertTo-Json -Depth 100
