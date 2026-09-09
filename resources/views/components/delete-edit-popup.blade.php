@props([
    'confirmationData' => '',
])
{{-- test URL --}}
<div x-show="showeditdeletepopup" x-clock x-transition.opacity @click.outside="showeditdeletepopup = false"
    class="bg-white p-2 flex flex-col gap-2 w-full max-w-16.75 h-fit rounded-lg border border-gray-200 shadow-lg">
    <span class="w-full text-red-500 bg-red-50 py-0.5 px-3 rounded-md hover:bg-red-100 cursor-pointer"
        @click="
        showDeleteModal= true;
        showeditdeletepopup= false;
        confirmationData='{{ addslashes($confirmationData) }}';
        {{-- deleteUrl='{{ route('periode-penjurusan.delete') }}';   --}}
         ">
        delete
    </span>
    <span class="w-full text-blue-500 bg-blue-50 py-0.5 px-3 text-center rounded-md hover:bg-blue-100 cursor-pointer">
        edit
    </span>
</div>
