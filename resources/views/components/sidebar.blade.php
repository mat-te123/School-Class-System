<aside x-cloak x-data="{
    isSidebarOpen: false,
    toggleSidebar() {
        this.isSidebarOpen = !this.isSidebarOpen;
    },
}" :class="isSidebarOpen ? 'w-64' : 'w-20'"
    class="relative bg-slate-900 text-white min-h-screen p-4 flex flex-col justify-between gap-4 transition-all duration-300 shadow-[8px_0px_20px_-10px_#172034]">
    <div class="flex flex-col h-fit gap-4">
        <div class="flex flex-row items-center gap-2 border-b border-gray-500 pb-2">
            <img src="{{ asset('favicon.svg') }}" alt="logo" class="w-12 h-12" />
            <span class="text-lg font-bold leading-6  transition-all duration-300 ease-in-out"
                :class="{ 'opacity-0': !isSidebarOpen }">StudIQ</span>
        </div>
        @php
            $items = [
                'Siswa' => [
                    'route' => route('admin-siswa.index'),
                    'icon' => asset('Icon/placeholder.svg'),
                    'text' => 'Siswa',
                ],
                'Periode' => [
                    'route' => route('admin-periode-penjurusan.index'),
                    'icon' => asset('Icon/placeholder.svg'),
                    'text' => 'Periode',
                ],
                'Kelas' => [
                    'route' => route('admin-paket-menu.index'),
                    'icon' => asset('Icon/placeholder.svg'),
                    'text' => 'Kelas',
                ],
            ];
        @endphp

        @foreach ($items as $key => $value)
            <a href="{{ $value['route'] }}"
                class="flex flex-row items-center gap-2 p-2 rounded hover:bg-slate-800 opacity-100 transition-all duration-300 ease-in-out text-md leading-4 font-normal">
                <img src="{{ $value['icon'] }}" alt="{{ $key }}_icon" class="w-6 h-6" />
                <span :class="{ 'opacity-0': !isSidebarOpen }">{{ $value['text'] }}</span>
            </a>
        @endforeach
    </div>


    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"
            class="flex flex-row items-center gap-2 p-2 rounded opacity-100 transition-all duration-300 ease-in-out bg-red-600 hover:bg-red-700 text-white w-full">
            <img src="{{ asset('Icon/Sign_out.svg') }}" alt="logout_icon" class="w-6 h-6" />
            <span type="submit" class="text-lg font-bold leading-6 opacity-100 transition-all duration-300 ease-in-out"
                :class="{ 'opacity-0': !isSidebarOpen }">
                Logout
            </span>
        </button>

    </form>
    <button @click="toggleSidebar()"
        class="border border-white absolute top-1/2 -right-3 transform -translate-y-1/2 bg-slate-800 hover:bg-blue-500 scale-100 active:scale-105 text-white p-1 rounded-full transition-all ease-in-out duration-300 group"
        :class="{ 'rotate-180': !isSidebarOpen }">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"  stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>
</aside>
