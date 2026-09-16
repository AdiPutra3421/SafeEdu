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
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger" style="font-size: 0.8rem; margin-top: 5px; color: #ef4444;" />
        </div>

        <!-- Remember Me -->
        <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
            <label for="remember_me" style="display: flex; align-items: center; cursor: pointer;">
                <input id="remember_me" type="checkbox" name="remember" style="margin-right: 8px;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">Ingat Saya</span>
            </label>

            @if (Route::has('password.request'))
                <a style="font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600;" href="{{ route('password.request') }}">
                    Lupa Password?
                </a>
            @endif
        </div>

        <div>
            <button class="btn btn-primary btn-full" type="submit">
                Masuk ke Dashboard
            </button>
        </div>
    </form>
</x-guest-layout>
