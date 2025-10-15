<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
        rel="stylesheet">

    <!-- Google Maps SDK -->
    <script>
        window.googleMapsReady = new Promise((resolve) => {
            window.initGoogleMaps = () => {
                resolve();
            };
        }); 
    </script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&loading=async&callback=initGoogleMaps"></script>

    <!-- Hreflang for SEO -->
    @php
        $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
        $routeParameters = request()->route()->parameters();
        unset($routeParameters['locale']); // Remove locale from parameters
        $alternateUrls = \App\Helpers\LocaleHelper::getAlternateUrls($currentRoute, $routeParameters);
    @endphp

    @foreach($alternateUrls as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
    @endforeach

    @if(isset($alternateUrls[config('app.locale')]))
        <link rel="alternate" hreflang="x-default" href="{{ $alternateUrls[config('app.locale')] }}">
    @endif

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])

    {{-- Injected Header Scripts --}}
    @if(!empty($headerScript))
        {!! $headerScript !!}
    @endif

    {{-- Organization Schema --}}
    @if(!empty($organizationSchemaForBlade))
        <script type="application/ld+json">
                        {!! json_encode($organizationSchemaForBlade, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
                    </script>
    @endif

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-16944650756"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'AW-16944650756');
        gtag('event', 'conversion', { 'send_to': 'AW-16944650756/gSgaCLXb-skaEIS0648_' });
    </script>

    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
        (function () {
            var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = 'https://embed.tawk.to/6876027021757c1912dc0f68/1j06fj2e0';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
    <!--End of Tawk.to Script-->

    <script src="https://script.tapfiliate.com/tapfiliate.js" type="text/javascript" async></script>
<script type="text/javascript">
  (function(t,a,p){t.TapfiliateObject=a;t[a]=t[a]||function(){ (t[a].q=t[a].q||[]).push(arguments)}})(window,'tap');

  tap('create', '60750-874db3', { integration: "javascript" });
  tap('detect');
</script>

    @inertiaHead
</head>

<body class="antialiased">
    @inertia

    {{-- Injected Footer Scripts --}}
    @if(!empty($footerScript))
        {!! $footerScript !!}
    @endif
</body>

</html>
