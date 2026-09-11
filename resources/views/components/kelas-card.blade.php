@props([
    'item' => null,
])

<div class=" flex flex-col p-2 border border-gray-300 rounded-lg gap-4" x-data="{ showeditdeletepopup: false, }">
    <div class="relative flex flex-row justify-between border-b border-gray-300 pb-2">
        <div class="flex flex-row gap-2">
            <div class="bg-gray-300 h-fit p-1 rounded-lg">
                <img src="{{ asset('Icon/Group_duotone_fill.svg') }}" alt="book_icon" />
            </div>
            <div class="flex flex-col">
                <h2 class="text-base font-bold leading-6">
                    {{ $item['nama_menu'] }}
                </h2>
                <span class="text-xs leading-4 font-normal">
                    rumpun {{ $item['rumpun'] }}
                </span>
            </div>
        </div>
        <div class="hover:bg-gray-200 active:bg-gray-200 cursor-pointer h-fit py-1 rounded-2xl"
            @click="
                showeditdeletepopup=true;
            ">
            <img src="{{ asset('Icon/Meatballs_menu.svg') }}" />
        </div>
        @php
            $formattedData = array_merge($item, [
                'is_active' => $item['is_active'] ? 1 : 0,
            ]);
        @endphp
        <div class="absolute top-0 right-0 z-10">
            <x-delete-edit-popup :item="$item" :confirmationData="$item['nama_menu']" :deleteUrl="route('paket-menu.destroy', ['identifier' => $item['id']])" :data="$formattedData"
                :updateUrl="route('paket-menu.update', ['identifier' => $item['id']])" />
        </div>
    </div>
    <div class="flex flex-col w-full">
        <div class="flex flex-row justify-between text-xs leading-4 font-normal">
            <span>
                Kuota Terisi
            </span>
            <span>
                {{ $item['kuota_terisi'] }} / {{ $item['kuota_kapasitas'] }}
            </span>
        </div>
        <div class="w-full bg-gray-200 h-2 rounded-lg overflow-hidden">
            <div class="w-[{{ ($item['kuota_terisi'] / $item['kuota_kapasitas']) * 100 }}%] h-2 bg-blue-600 rounded-lg">
            </div>

        </div>

    </div>
    <div class="flex flex-row justify-end">
        <span class="text-xs leading-4 font-normal cursor-pointer hover:underline">
            Lihat Detail
        </span>
    </div>

</div>
