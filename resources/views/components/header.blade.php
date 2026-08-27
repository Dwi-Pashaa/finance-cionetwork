<header class="navbar navbar-expand-md sticky-top d-print-none border-bottom bg-white">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
            aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-brand navbar-brand-autodark pe-0 pe-md-3">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ asset('img/logo.jpg') }}" alt="{{ config('app.name') }}" class="navbar-brand-image" style="height: 40px; width: auto; object-fit: contain;">
            </a>
        </div>
        <div class="navbar-nav flex-row order-md-last align-items-center gap-2 gap-md-3">
            <!-- Date Indicator -->
            <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border text-muted small fw-medium">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            </div>

            @if(Auth::check())
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex align-items-center p-1 text-reset" data-bs-toggle="dropdown" aria-label="Menu Pengguna">
                        <span class="avatar avatar-sm rounded-circle text-uppercase fw-bold bg-primary text-white shadow-sm border border-2 border-white">
                            {{ substr(Auth::user()->name, 0, 2) }}
                        </span>
                        <div class="d-none d-md-block ps-2 text-start">
                            <div class="fw-bold text-dark leading-tight">{{ Auth::user()->name }}</div>
                            <div class="small text-primary fw-medium" style="font-size: 11px;">{{ Auth::user()->roles->first()->name ?? 'User' }}</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-chevron-down ms-1 text-muted d-none d-md-inline-block"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 9l6 6l6 -6" /></svg>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                        <div class="dropdown-header bg-light rounded-top py-2">
                            <div class="fw-bold text-dark">{{ Auth::user()->name }}</div>
                            <div class="small text-muted">{{ Auth::user()->email }}</div>
                        </div>
                        <div class="dropdown-divider my-1"></div>
                        <a href="{{ route('logout') }}" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg>
                            <span>Keluar (Logout)</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</header>