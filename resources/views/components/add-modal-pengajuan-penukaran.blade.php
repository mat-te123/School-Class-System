<x-add-modal type="Pengajuan penukaran kelas" subtext="Ajukan penukaran kelas kepada guru">
    <form action="{{ route('siswa.pertukaran.store') }}" method="POST" enctype="multipart/form-data"
        class="flex flex-col gap-5">
        @csrf
        @method('POST')
        <div class="flex flex-col gap-2">
            <label for="paket_tujuan_id" class="text-sm font-semibold text-gray-800">Kelas tujuan</label>
            <select name="paket_tujuan_id" id="paket_tujuan_id" required autofocus class="select-primary w-full">
                <option value="" disabled selected>Pilih kelas</option>
                <template x-for="paket in paket_list" :key="paket.id">
                    <option :value="paket.id" x-text="paket.nama_menu"></option>
                </template>
            </select>
        </div>
        <div class="flex flex-col gap-2">
            <label for="alasan" class="text-sm font-semibold text-gray-800">Alasan penukaran</label>
            <textarea id="alasan" name="alasan" rows="4" required placeholder="Tuliskan alasan Anda..."
                class="block w-full resize-y rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-slate-700 focus:outline-none focus:ring-1 focus:ring-slate-700"></textarea>
            <p class="text-xs leading-4 text-gray-500">Jelaskan alasan secara singkat dan jelas.</p>
        </div>
        <div class="flex flex-col gap-2">
            <label for="file_pendukung" class="text-sm font-semibold text-gray-800">
                File pendukung <span class="font-normal text-gray-500">(opsional)</span>
            </label>
            <input type="file" name="file_pendukung" id="file_pendukung" accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full rounded-lg border border-gray-300 text-sm text-gray-600 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
            <p class="text-xs leading-4 text-gray-500">PDF, JPG, atau PNG. Maksimal 2mb.</p>
        </div>
        <div class="flex flex-col-reverse gap-2 border-t border-gray-200 pt-4 sm:flex-row sm:justify-end">
            <button type="button" @click="showaddmodal = false"
                class="negative-primary-button w-full whitespace-nowrap sm:w-fit">
                Batal
            </button>
            <button type="submit" class="primary-button w-full whitespace-nowrap sm:w-fit">
                Ajukan penukaran
            </button>
        </div>

    </form>

</x-add-modal>
