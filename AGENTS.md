# SafeEdu

Monorepo: Laravel backend + web admin (`admin/`), and two Flutter mobile apps (`user_app/` = siswa/pelapor, `teknisi_app/` = petugas/teknisi). The Flutter apps talk only to the Laravel JSON API.

## Structure

- `admin/` — Laravel 13 + Sanctum + Reverb broadcasting. Mixes a Breeze web admin (Blade + Tailwind + Vite, routes in `routes/web.php`) with the JSON API the apps consume (`routes/api.php`). Ownership: `user_app` → `pengaduan`/`notifikasi` routes; `teknisi_app` → `penugasan` routes; web admin → `fasilitas` (apiResource) + Blade views.
- `user_app/` / `teknisi_app/` — plain Flutter with only `http`; no state management, no auth token handling.

## API gotchas (don't guess, read first)

- The API is effectively unauthenticated. `POST /api/auth/login` only validates email+password against users with `role = siswa` and returns the user id — no tokens are issued. Endpoints identify users via query params (`?user_id=`, `?teknisi_id=`) with no middleware. Don't add Sanctum token logic; it's not how the apps consume the API.
- Users carry a `role` column (`admin` / `teknisi` / `siswa`) that drives role checks in controllers.
- `PenugasanController::store` hardcodes `admin_id = 1`.
- File uploads (`file_bukti`) are stored under `storage/app/public/bukti`; URLs need `php artisan storage:link` to resolve.
- Notifications broadcast on Reverb channel `notifikasi.{user_id}` (event `notifikasi.baru`, see `app/Events/NotifikasiDikirim.php`). Default `BROADCAST_CONNECTION` is `null`, so nothing actually broadcasts until `.env` configures Reverb/Reverb keys. The web/Flutter clients do not yet subscribe to the channel.

## Commands (run inside `admin/`)

- `php artisan serve` — API on `:8000`. Both Flutter apps hardcode `http://127.0.0.1:8000`; use `php artisan serve --host=0.0.0.0` when testing from a device/emulator.
- `composer test` / `php artisan test` — PHPUnit, sqlite `:memory:` (`phpunit.xml`), no DB setup needed.
- `vendor/bin/pint` — code style.
- `php artisan migrate` then `php artisan db:seed` — seeds users `admin/admin`, `teknisi/teknisi`, `user/user` plus a couple of Kategori/Fasilitas rows.
- `npm run dev` / `npm run build` — Vite (Blade assets only).

## Flutter

- `user_app` now authenticates against `POST /api/auth/login` and keeps the session in `lib/services/auth_service.dart` (in-memory singleton). `user_id` flows from login → report fetching, no more hardcoded id. API base URL is centralized in `lib/services/api_config.dart` but older screens (e.g. teknisi app, `home_screen` fasilitas fetch) still hardcode `http://127.0.0.1:8000` per-request.
- `user_app` polls unread notifications every 30s while active (badge in `home_screen`, panel in `notifikasi_screen`). Real-time Reverb push is still not consumed — polling is the fallback.
- `file_picker` v13 is used for report attachments (static `FilePicker.pickFile(...)`, returns `PlatformFile?`; it is NOT the old `FilePicker.platform.pickFiles` API). Note: building for Windows desktop needs Developer Mode enabled (plugin symlinks).
- The teknisi login screen is UI-only — it navigates without calling `/api/auth/login`.
- Lint/analyze: `flutter analyze` (both define `flutter_lints`). `user_app/` has one smoke widget test (`flutter test`).

## Scope notes

- `admin/AGENTS.md` and `admin/CLAUDE.md` are stock Laravel template placeholders instructing you to install `laravel/boost`; that package is NOT installed or needed here. Ignore the boost install prompt; this root file is authoritative.
- Root and `admin/` `fix_*.py` scripts, `admin/dump_schema.php`, `admin/schema.sql`, and `db_safeedu_v2.sql` are one-off dev artifacts/database snapshots, not part of the application.