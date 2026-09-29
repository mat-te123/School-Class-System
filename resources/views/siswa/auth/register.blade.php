<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Register Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body
    class="relative flex flex-col bg-[linear-gradient(136deg,#E5E7EB_24.53%,#9CA3AF_95.42%)] h-screen w-screen items-center justify-center">
    {{-- Pesan Error Validasi --}}
    @if ($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
            class=" bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm absolute top-4 left-3/5 transform translate-y-1 w-fit">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('step') === 2)
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
            class="absolute top-4 left-3/5 transform translate-y-1 w-fit bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg text-sm">
            NISN ditemukan. Silakan lengkapi data berikut untuk mendaftar.
        </div>
    @endif


    <div class="bg-white flex flex-col items-center justify-start p-8 gap-6 rounded-lg shadow-2xl max-w-md w-full"
        x-data="{
            showPassword: false,
            togglePasswordVisibility() {
                this.showPassword = !this.showPassword;
            }
        }">

        <div class="flex flex-col items-center gap-4">
            <div class="bg-gray-200 p-1.5 w-fit rounded-xl">
                <img src="{{ asset('Icon/Lock_alt.svg') }}" alt="Lock Logo" class="w-7 h-7" />
            </div>
            <div>
                <h1 class="text-3xl leading-8 font-bold text-center">
                    Register Siswa
                </h1>
                <span class="text-sm leading-4 font-normal text-center text-gray-600 block mt-1">
                    Silakan daftarkan akun anda berdasarkan NISN yang terdaftar
                </span>
            </div>
        </div>

        @php
            $step = session('step', 1);
            $actionUrl = $step === 2 ? route('siswa.register') : route('siswa.register.check');
            $siswa = (object) session('siswa');
        @endphp

        <form action="{{ $actionUrl }}" method="POST" class="w-full flex flex-col gap-4">
            @csrf



            {{-- Input NISN (Selalu Tampil) --}}
            <div class="flex flex-col gap-1">
                <label for="fnisn" class="text-sm leading-4 font-semibold">
                    NISN
                </label>
                <input name="nisn" id="fnisn" type="text" placeholder="Masukkan NISN" required
                    value="{{ old('nisn', $siswa->nisn ?? '') }}" {{ $step === 2 ? 'readonly' : '' }}
                    class="border border-black rounded-lg py-1 px-4 {{ $step === 2 ? 'bg-gray-100 cursor-not-allowed' : '' }}">
            </div>

            {{-- Tahap 2: Field Tambahan --}}
            @if ($step === 2)
                {{-- Menampilkan Nama Lengkap Terverifikasi --}}
                @if (isset($siswa->nama_lengkap))
                    <div class="flex flex-col gap-1">
                        <label class="text-sm leading-4 font-semibold text-gray-600">Nama Lengkap</label>
                        <input type="text" value="{{ $siswa->nama_lengkap }}" disabled
                            class="border border-black  rounded-lg py-1 px-4 text-gray-700">
                    </div>
                @endif

                <div class="flex flex-row gap-4 w-full">
                    <div class="flex flex-col gap-1 w-full">
                        <label for="fjenis_kelamin" class="text-sm leading-4 font-semibold">
                            Jenis Kelamin
                        </label>
                        <select name="jenis_kelamin" id="fjenis_kelamin" required
                            class="border border-black rounded-lg py-1 px-4">
                            <option value="" disabled {{ old('jenis_kelamin') ? '' : 'selected' }}>Pilih
                            </option>
                            <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>
                                Laki-laki
                            </option>
                            <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>
                                Perempuan
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1 w-full">
                        <label for="ftanggal_lahir" class="text-sm leading-4 font-semibold">
                            Tanggal Lahir
                        </label>
                        <input name="tanggal_lahir" id="ftanggal_lahir" type="date" required
                            value="{{ old('tanggal_lahir') }}" class="border border-black rounded-lg py-1 px-4">
                    </div>
                </div>

                @php $year = (int) date('Y'); @endphp
                <div class="flex flex-col gap-1">
                    <label for="fangkatan" class="text-sm leading-4 font-semibold">
                        Angkatan
                    </label>
                    <select name="angkatan" id="fangkatan" required class="border border-black rounded-lg py-1 px-4">
                        @for ($i = $year - 2; $i <= $year; $i++)
                            <option value="{{ $i }}" {{ old('angkatan', $year) == $i ? 'selected' : '' }}>
                                {{ $i }} / {{ $i + 1 }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="fpassword" class="text-sm leading-4 font-semibold">
                        Password
                    </label>
                    <div class="relative w-full">
                        <input name="password" id="fpassword" :type="showPassword ? 'text' : 'password'"
                            placeholder="******" required class="w-full border border-black rounded-lg py-1 px-4 pr-10">
                        <img :src="showPassword ? '{{ asset('Icon/Eye_fill.svg') }}' :
                            '{{ asset('Icon/Eye_close.svg') }}'"
                            alt="Toggle Password Visibility"
                            class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer"
                            @click="togglePasswordVisibility()">
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="fpassword_confirmation" class="text-sm leading-4 font-semibold">
                        Konfirmasi Password
                    </label>
                    <input name="password_confirmation" id="fpassword_confirmation"
                        :type="showPassword ? 'text' : 'password'" placeholder="******" required
                        class="border border-black rounded-lg py-1 px-4">
                </div>
            @endif

            <div class="flex flex-row gap-2 w-full mt-2">
                <a class="w-full text-center bg-white text-[#1E293B] border border-[#1E293B] text-lg py-1 font-medium rounded-lg shadow-inner hover:scale-105 hover:bg-gray-50 transition-all duration-300"
                    href="{{ $step === 2 ? route('siswa.register.form') : route('siswa.login.form') }}">
                    {{ $step === 2 ? 'Batal' : 'Kembali' }}
                </a>
                <button
                    class="w-full bg-[#1E293B] text-lg py-1 text-white font-medium border border-gray-400 rounded-lg shadow-inner hover:scale-105 hover:bg-gray-700 transition-all duration-300"
                    type="submit">
                    {{ $step === 2 ? 'Daftar' : 'Cek NISN' }}
                </button>
            </div>
        </form>
    </div>
</body>

</html>
