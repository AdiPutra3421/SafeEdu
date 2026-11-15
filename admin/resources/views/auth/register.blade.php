<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="name">Nama</label>
            <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-danger" style="font-size: 0.8rem; margin-top: 5px; color: #ef4444;" />
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger" style="font-size: 0.8rem; margin-top: 5px; color: #ef4444;" />
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger" style="font-size: 0.8rem; margin-top: 5px; color: #ef4444;" />
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-danger" style="font-size: 0.8rem; margin-top: 5px; color: #ef4444;" />
        </div>

        <div>
            <button class="btn btn-primary btn-full" type="submit">
                Daftar
            </button>
        </div>
    </form>

    <div class="auth-links">
        Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
    </div>
</x-guest-layout>
