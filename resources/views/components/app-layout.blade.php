@props(['title' => 'Dashboard', 'type' => ''])

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.svg') }}">
</head>

<body class="bg-gray-100 min-h-screen flex">
    <x-admin-sidebar :isAdmin=" $type !== 'siswa' " />
    <main class="flex-1 h-screen flex flex-col overflow-y-auto">
        {{ $slot }}
    </main>
    @stack('scripts')
</body>

</html>
