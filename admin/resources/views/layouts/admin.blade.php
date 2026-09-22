<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SafeEdu Admin - @yield('title')</title>
  <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
  <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body>
  <div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="brand-section">
        <div class="brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
        </div>
        <div class="brand-text">
          <h1>SafeEdu</h1>
          <span>Portal Administrasi</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-label">Menu Utama</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="3" y="3" width="7" height="9" rx="1"/>
            <rect x="14" y="3" width="7" height="5" rx="1"/>
            <rect x="14" y="12" width="7" height="9" rx="1"/>
            <rect x="3" y="16" width="7" height="5" rx="1"/>
          </svg>
          Beranda & Statistik
        </a>
        <a href="{{ route('laporan') }}" class="nav-item {{ request()->routeIs('laporan') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
          </svg>
          Laporan User
          <span class="nav-badge" id="sidebar-pending-badge">2</span>
        </a>
        <a href="{{ route('konfirmasi') }}" class="nav-item {{ request()->routeIs('konfirmasi') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          Konfirmasi Teknisi
        </a>

        <div class="nav-label" style="margin-top: 20px;">Master Data</div>
        <a href="{{ route('fasilitas') }}" class="nav-item {{ request()->routeIs('fasilitas') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
          Manajemen Fasilitas
        </a>
        <a href="{{ route('konten-sekolah') }}" class="nav-item {{ request()->routeIs('konten-sekolah') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M4 4h16v16H4z"></path>
            <path d="M8 8h8M8 12h8M8 16h5"></path>
          </svg>
          Berita & Panduan
        </a>

        <div class="nav-label" style="margin-top: 20px;">Akun</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="nav-item text-danger">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              Logout
            </a>
        </form>
      </nav>

      <div class="sidebar-footer">
        <div class="admin-avatar">{{ substr(Auth::user()->name, 0, 2) }}</div>
        <div class="admin-info">
          <div class="admin-name">{{ Auth::user()->name }}</div>
          <div class="admin-role">{{ Auth::user()->email }}</div>
        </div>
      </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
      @yield('content')
    </div>
  </div>

  <script src="{{ asset('js/store.js') }}"></script>
  @yield('scripts')

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const badge = document.getElementById('sidebar-pending-badge');
        if (!badge) return;
        
        const response = await fetch('/api/pengaduan');
        const json = await response.json();
        
        if (json.success) {
            // Count reports that are "menunggu"
            const unread = json.data.filter(x => x.status === 'menunggu').length;
            if (unread > 0) {
                badge.innerText = unread;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (e) {
        console.error('Failed to load badge count', e);
    }
});
</script>
</body>

</html>
