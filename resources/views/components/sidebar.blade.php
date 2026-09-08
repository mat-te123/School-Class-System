<aside class="w-64 bg-slate-900 text-white min-h-screen p-4 flex flex-col gap-4">
    <div class="text-xl font-bold px-2">School App</div>

    <nav class="flex flex-col gap-2">
        <a href="{{ route('admin-siswa.index') }}" class="p-2 rounded hover:bg-slate-800">Dashboard</a>
        <a href="{{ route('admin-periode-penjurusan.index') }}" class="p-2 rounded hover:bg-slate-800">Periode</a>
        <a href="#" class="p-2 rounded hover:bg-slate-800">Nilai</a>
    </nav>

    <form action="{{ route('logout') }}" method="POST">
        <button type="submit" class="p-1 bg-red-600 text-white w-full">
            Logout
        </button>
    </form>
</aside>
