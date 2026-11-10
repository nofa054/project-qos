<!DOCTYPE html>
<html>
<head>
    <title>QoS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900">

<div class="flex">

    <!-- SIDEBAR -->
    <div class="w-64 h-screen bg-slate-800 border-r border-slate-700 p-5 text-white">

        <h2 class="text-xl font-bold mb-6 text-slate-200">MENU</h2>

        <ul class="space-y-3">

            <li>
                <a href="/" 
                   class="flex items-center gap-2 p-3 rounded-lg transition-colors
                   {{ request()->is('/') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                   Dashboard
                </a>
            </li>

        </ul>

    </div>

    <!-- CONTENT -->
    <div class="flex-1 p-6 h-screen overflow-y-auto">
        @yield('content')
    </div>

</div>

</body>
</html>