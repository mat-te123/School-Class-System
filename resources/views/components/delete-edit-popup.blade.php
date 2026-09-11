@props([
    'deleteUrl' => '',
    'updateUrl' => '',
    'data' => null,
    'item' => null,
    'confirmationData' => '',
])
{{-- test URL --}}
<div x-show="showeditdeletepopup" x-cloak x-transition.opacity @click.outside="showeditdeletepopup = false"
    class="bg-white p-2 flex flex-col gap-2 w-full max-w-16.75 h-fit rounded-lg border border-gray-200 shadow-lg">
    <span class="text-sm w-full text-red-500 bg-red-50 py-0.5 px-3 rounded-md hover:bg-red-100 cursor-pointer"
        @click="
        showDeleteModal= true;
        showeditdeletepopup= false;
        confirmationData='{{ addslashes($confirmationData) }}';
        deleteUrl='{{ $deleteUrl }}';  
         ">
        delete
    </span>
    <span
        class="text-sm w-full text-blue-500 bg-blue-50 py-0.5 px-3 text-center rounded-md hover:bg-blue-100 cursor-pointer"
        @click="
        showupdateModal= true;
        showeditdeletepopup= false;
        Data=@js($data);
        updateUrl='{{ $updateUrl }}';

    ">
        edit
    </span>
</div>
