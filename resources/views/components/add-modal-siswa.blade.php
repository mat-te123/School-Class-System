<x-add-modal subtext="Menambahkan Data baru pada sistem">
    @php
        $btn_primary = 'bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg py-2 px-4 transition';
    @endphp
    <div class="flex flex-row w-full bg-[#EFF6FF] p-1 rounded-md">
        <span @click="showmanual = true; showotomatis = false;"
            :class="showmanual ? 'bg-[#3B82F6] text-[#F9FAFB] shadow-sm' : 'text-[#9CA3AF] hover:text-gray-600'"
            class="w-full py-1.5 items-center justify-center text-center cursor-pointer rounded-sm font-medium transition-all duration-300 ease-in-out select-none">
            Manual
        </span>
        <span @click="showmanual = false; showotomatis = true;"
            :class="showotomatis ? 'bg-[#3B82F6] text-[#F9FAFB] shadow-sm' : 'text-[#9CA3AF] hover:text-gray-600'"
            class="w-full py-1.5 items-center justify-center text-center cursor-pointer rounded-sm font-medium transition-all duration-300 ease-in-out select-none">
            Otomatis
        </span>
    </div>
    <form :action="addUrl" method="POST" class="flex flex-col gap-3" x-show="showmanual">
        @csrf
        @method('POST')

        <div class="flex flex-col gap-1">
            <label for="fnamalengkap" class="text-sm leading-4 font-semibold">Nama Lengkap</label>
            <input name="nama_lengkap" id="fnamalengkap" type="text" required
                class="border border-black rounded-lg py-1 px-4 text-base">
        </div>

        <div class="flex flex-row gap-2" id="NisNisnFormGroup">
            <div class="flex flex-col gap-1 w-full ">
                <label for="fnisn" class="text-sm leading-4 font-semibold">NISN</label>
                <input name="nisn" id="fnisn" type="text"
                    class="border border-black rounded-lg py-1 px-4 text-base w-full" minlength="10" size="10"
                    required>
            </div>
            <div class="flex flex-col gap-1 w-full">
                <label for="fnis" class="text-sm leading-4 font-semibold">NIS</label>
                <input name="nis" id="fnis" type="text"
                    class="border border-black rounded-lg py-1 px-4 text-base w-full" maxlength="10" required>
            </div>
        </div>

        <div class="flex flex-row gap-2" id="KelasKelaminFormGroup">
            <div class="flex flex-col gap-1 w-full">
                <label for="fkelas" class="text-sm leading-4 font-semibold">Nama Lengkap</label>
                <select id="fkelas" name="kelas" class="border border-black rounded-lg py-1 px-4 w-full text-base">
                    <option value=""> - Pilih Kelas -</option>
                    @php
                        $listKelas = ['A', 'B', 'C', 'D'];
                    @endphp
                    @foreach ($listKelas as $kelas)
                        <option value="X-{{ $kelas }}">
                            X-{{ $kelas }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1 w-full">
                <label for="fjeniskelamin" class="text-sm leading-4 font-semibold">Jenis Kelamin</label>
                <select name="jenis_kelamin" id="fjeniskelamin" x-model="studentData.jenis_kelamin"
                    class="border border-black rounded-lg py-1 px-4 w-full text-base">
                    <option value="">- Jenis Kelamin - </option>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </div>
        </div>

        @php
            $currentyear = (int) date('Y');
            $startyear = $currentyear - 5;
            $endyear = $currentyear + 5;
        @endphp
        <div class="flex flex-col gap-1">
            <label for="fangkatan" class="text-sm leading-4 font-semibold">Angkatan</label>
            <select name="angkatan" id="fangkatan" x-model="studentData.angkatan"
                class="border border-black rounded-lg py-1 px-4 text-base">
                <option value="">- Pilih angkatan -</option>

                @for ($year = $endyear; $year >= $startyear; $year--)
                    <option value="{{ $year }}/{{ $year + 1 }}">{{ $year }}/{{ $year + 1 }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="flex flex-row justify-end gap-3 mt-4">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showaddmodal = false">
                Batal
            </button>
            <button class="{{ $btn_primary }}" type="submit">
                Tambahkan
            </button>
        </div>

    </form>

    <form :action="addExcelUrl" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3"
        x-show="showotomatis">
        @csrf
        @method('POST')

        <div x-data="{
            isDragging: false,
            fileName: '',
            handleFileSelect(e) {
                const files = e.target.files || e.dataTransfer.files;
                if (files.length > 0) {
                    this.fileName = files[0].name;
                    $refs.fileInput.files = files;
                }
            }
        }" class="w-full">
            <label @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                @drop.prevent="isDragging = false; handleFileSelect($event)"
                :class="isDragging ? 'border-blue-500 bg-blue-100' : 'border-[#BFDBFE] bg-[#EFF6FF]'"
                class="flex flex-col items-center justify-center w-full aspect-square border-2 border-dashed rounded-lg cursor-pointer transition-all duration-300 ease-in-out p-4 text-center hover:bg-blue-100 hover:scale-105">

                <img src="{{ asset('Icon/Upload_fill.svg') }}" class="w-10 h-10 mb-2">

                <template x-if="!fileName">
                    <div class="flex flex-col items-center">
                        <span class="text-xs text-blue-500 font-medium">
                            klik untuk unggah atau seret file ke sini
                        </span>
                        <span class="text-[10px] text-blue-400 mt-1">
                            format yang didukung: .xlsx, .xls
                        </span>
                    </div>
                </template>

                <template x-if="fileName">
                    <div class="flex items-center gap-2 text-xs text-emerald-600 font-semibold">
                        <span>File terpilih:</span>
                        <span x-text="fileName" class="underline"></span>
                    </div>
                </template>

                <input x-ref="fileInput" type="file" name="excel_file" accept=".xlsx, .xls" class="hidden"
                    @change="handleFileSelect($event)" required>
            </label>
        </div>

        <div class="flex justify-end gap-3 mt-2">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showaddmodal = false">
                Batal
            </button>
            <button id="btn-primary" class="{{ $btn_primary }}" type="submit">
                Unggah File
            </button>
        </div>
    </form>

</x-add-modal>
