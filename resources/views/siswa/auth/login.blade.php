<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body
    class="flex flex-col bg-[linear-gradient(136deg,#E5E7EB_24.53%,#9CA3AF_95.42%)] h-screen w-screen items-center justify-center relative"
    x-data="{
        showError: true
    }">
    @if ($errors->any())
        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 w-fit bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm flex flex-row items-center gap-2"
            x-show="showError">
            {{ $errors->first() }}
            <div class="cursor-pointer">
                <svg viewBox="0 0 10 10" width="0.75em" height="0.75em" stroke="currentColor" stroke-width="2"
                    @click="showError = false">
                    <line x1="1" y1="1" x2="9" y2="9" />
                    <line x1="9" y1="1" x2="1" y2="9" />
                </svg>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
            class="absolute top-4 left-1/2 transform -translate-x-1/2 w-fit bg-emerald-100 border border-emerald-400 text-emerald-900 px-4 py-3 rounded-lg text-sm flex flex-row items-center gap-2">
            ✅ {{ session('success') }}
            <div class="cursor-pointer">
                <svg viewBox="0 0 10 10" width="0.75em" height="0.75em" stroke="currentColor" stroke-width="2"
                    @click="show = false">
                    <line x1="1" y1="1" x2="9" y2="9" />
                    <line x1="9" y1="1" x2="1" y2="9" />
                </svg>
            </div>
        </div>
    @endif
    <div class="bg-white flex flex-col items-center justify-content-start p-8 gap-6 rounded-lg shadow-2xl"
        x-data="{
            showPassword: false,
            togglePasswordVisibility() {
                this.showPassword = !this.showPassword;
            },
        }">
        <div class="flex flex-col items-center gap-4">
            <div class="bg-gray-200 p-1.5 w-fit rounded-xl">
                <img src="{{ asset('Icon/Lock_alt.svg') }}" alt="Lock Logo" class="w-7 h-7" />
            </div>
            <div>
                <h1 class="text-3xl leading-8 font-bold text-center">
                    Login Siswa
                </h1>
                <span class="text-sm leading-4 font-normal">
                    Silahkan masukkan NISN dan password
                </span>
            </div>
        </div>




        <form action="{{ route('siswa.login.form') }}" method="POST" class="w-full flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-4 ">
                <div class="flex flex-col gap-1">
                    <label for="fnisn" class="text-sm leading-4 font-semibold">
                        NISN
                    </label>
                    <input name="nisn" id="fnisn" type="number" placeholder="1234567890" required min="10"
                        class="border border-black rounded-lg py-1 px-4">
                </div>
                <div class="flex flex-col gap-1 relative">
                    <label for="fpassword" class="text-sm leading-4 font-semibold">
                        password
                    </label>
                    <input name="password" id="fpassword" :type="showPassword ? 'text' : 'password'" placeholder="****"
                        required class="border border-black rounded-lg py-1 px-4">
                    <img :src="showPassword ? '{{ asset('Icon/Eye_fill.svg') }}' : '{{ asset('Icon/Eye_close.svg') }}'"
                        alt="Toggle Password Visibility" class="w-5 h-5 absolute right-3 top-1/2  cursor-pointer"
                        @click="togglePasswordVisibility()">
                </div>
            </div>
            <button
                class="bg-[#1E293B] text-lg py-1 text-white font-medium border border-gray-400 rounded-lg shadow-inner hover:scale-105 hover:bg-gray-700 hover:shadow-md active:bg-gray-900 active:scale-95 active:shadow-sm transition-all duration-300"
                type="submit">
                Masuk
            </button>
            <span>
                Belum mendaftarkan akun ? <a href="{{ route('siswa.register.form') }}"
                    class="text-blue-500 hover:underline">
                    daftarkan akun
                </a>
            </span>
        </form>
    </div>

</body>

</html>
