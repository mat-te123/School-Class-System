<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body
    class="flex flex-col bg-[linear-gradient(136deg,#E5E7EB_24.53%,#9CA3AF_95.42%)] h-screen w-screen items-center justify-center">
    <div class="bg-white flex flex-col items-center justify-content-start p-8 gap-6 rounded-lg shadow-2xl"
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
                <h1 class="text-3xl leading-8 font-bold">
                    Login Admin / Guru
                </h1>
                <span class="text-sm leading-4 font-normal">
                    Silahkan masukkan username dan password
                </span>
            </div>
        </div>


        @if ($errors->any())
            <div class="w-full bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="w-full flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-4 ">
                <div class="flex flex-col gap-1">
                    <label for="fusername" class="text-sm leading-4 font-semibold">
                        username
                    </label>
                    <input name="username" id="fusername" type="text" placeholder="John Doe" required
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
        </form>
    </div>

</body>

</html>
