<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'AI Form Builder')
    </title>

    {{-- Bootstrap CSS --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Livewire --}}
    @livewireStyles

</head>

<body>

    {{ $slot }}

    {{-- Bootstrap JS --}}
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
    <script 
    src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js">
    </script>


    {{-- Livewire JS --}}
    @livewireScripts

</body>

</html>