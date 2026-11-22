<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-group">
            <label class="form-label" for="email">Username</label>
            <input id="email" class="form-control" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger" style="font-size: 0.8rem; margin-top: 5px; color: #ef4444;" />
        </div>

        <!-- Password -->
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div style="position: relative; display: flex; align-items: center;">
                <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" style="padding-right: 42px;" />
                <button type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 4px; display: flex; align-items: center; justify-content: center;" title="Tampilkan/Sembunyikan Password">
                    <svg id="eyeIcon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg id="eyeOffIcon" style="width: 20px; height: 20px; display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger" style="font-size: 0.8rem; margin-top: 5px; color: #ef4444;" />
        </div>

        <!-- Remember Me -->
        <div class="form-group" style="display: flex; align-items: center;">
            <label for="remember_me" style="display: flex; align-items: center; cursor: pointer;">
                <input id="remember_me" type="checkbox" name="remember" style="margin-right: 8px;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">Ingat Saya</span>
            </label>
        </div>

        <div>
            <button class="btn btn-primary btn-full" type="submit">
                Masuk ke Dashboard
            </button>
        </div>
    </form>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }
    </script>
</x-guest-layout>
