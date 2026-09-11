@props([
    'item' => null,
    'nama_periode' => 'Periode Penjurusan',
    'tahun_ajaran' => '2026',
    'tanggal_buka' => '11 Agustus 2026',
    'tanggal_tutup' => '11 Agustus 2027',
    'is_active' => true,
])


<div class="bg-gray-50 border border-gray-300 p-3 rounded-lg flex flex-col gap-2" x-data="{ showeditdeletepopup: false, }">
    <div class="flex flex-row w-full justify-between relative">
        <div class="flex flex-col gap-1">
            <h2 class="text-lg leading-7 font-bold">
                {{ $nama_periode }}
            </h2>
            <span class="text-xs leading-4 font-medium">
                T.A {{ $tahun_ajaran }}
            </span>
        </div>
        <div class="hover:bg-gray-200 active:bg-gray-200 cursor-pointer h-fit py-1 rounded-2xl"
            @click="
                showeditdeletepopup=true;
            ">
            <img src="{{ asset('Icon/Meatballs_menu.svg') }}" />
        </div>
        @php
            $formattedData = array_merge($item->toArray(), [
                'is_active' => $item->is_active ? 1 : 0,
                'tanggal_buka' => \Illuminate\Support\Str::before($item->tanggal_buka, ' '),
                'tanggal_tutup' => \Illuminate\Support\Str::before($item->tanggal_tutup, ' '),
            ]);
        @endphp
        <div class="absolute top-0 right-0 z-10">
            <x-delete-edit-popup :item="$item" :confirmationData="$nama_periode" :updateUrl="route('periode-penjurusan.update', ['id' => $item->id])" :deleteUrl="route('periode-penjurusan.destroy', ['id' => $item->id])"
                :data="$formattedData" />
        </div>
    </div>

    @if ($is_active)
        <span class="text-xs leading-4 font-semibold text-green-600 px-3 py-1 rounded-lg bg-green-50 w-fit">
            Aktif
        </span>
    @else
        <span class="text-xs leading-4 font-semibold text-red-600 px-3 py-1 rounded-lg bg-red-50 w-fit">
            Tidak Aktif
        </span>
    @endif
    <span class="text-xs leading-4 flex flex-row gap-2 ">
        <img src="{{ asset('Icon/Date_range.svg') }}" />
        {{ $tanggal_buka }} - {{ $tanggal_tutup }}
    </span>
    <div class="flex flex-row w-full justify-end border-t border-gray-300 pt-2">
        <span class="text-xs leading-4 font-medium  cursor-pointer hover:underline text-right">
            lihat detail
        </span>
    </div>


</div>
