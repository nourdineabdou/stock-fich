<x-guest-layout>

    {{-- Session Status --}}
    @if (session('status'))
    <div class="mb-5 flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
        <i class="fas fa-check-circle text-emerald-500 flex-shrink-0"></i>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    {{-- Global error --}}
    @if ($errors->any() && !$errors->has('email') && !$errors->has('password'))
    <div class="mb-5 flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        <i class="fas fa-exclamation-circle text-red-500 flex-shrink-0"></i>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

        {{-- Email --}}
        <div class="mb-5">
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">
                Adresse e-mail
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i class="fas fa-envelope text-slate-400 text-sm"></i>
                </div>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="vous@entreprise.ma"
                    class="input-field {{ $errors->has('email') ? 'border-red-400 bg-red-50' : '' }}"
                >
            </div>
            @error('email')
            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                <i class="fas fa-exclamation-circle"></i> {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-5">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-slate-700">
                    Mot de passe
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium hover:underline">
                    Mot de passe oublié ?
                </a>
                @endif
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i class="fas fa-lock text-slate-400 text-sm"></i>
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••••"
                    class="input-field {{ $errors->has('password') ? 'border-red-400 bg-red-50' : '' }}"
                    style="padding-right:3rem;"
                >
                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600" tabindex="-1">
                    <i class="fas fa-eye text-sm" id="eyeIcon"></i>
                </button>
            </div>
            @error('password')
            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                <i class="fas fa-exclamation-circle"></i> {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <div class="relative">
                    <input id="remember_me" name="remember" type="checkbox" class="sr-only peer">
                    <div class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:bg-primary-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4 peer-focus:ring-2 peer-focus:ring-primary-300" style="transition:transform 0.2s;"></div>
                </div>
                <span class="text-sm text-slate-600 font-medium">Se souvenir de moi</span>
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-login" id="loginBtn">
            <span id="btnText" class="flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </span>
            <span id="btnLoading" class="hidden flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Connexion en cours…
            </span>
        </button>

    </form>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Toggle switch behavior
        document.getElementById('remember_me').addEventListener('change', function() {
            const dot = this.parentElement.nextElementSibling;
        });

        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('btnText').classList.add('hidden');
            document.getElementById('btnLoading').classList.remove('hidden');
            document.getElementById('loginBtn').disabled = true;
        });
    </script>

</x-guest-layout>
