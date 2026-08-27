<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar bg-white border-bottom shadow-sm py-1">
            <div class="container-xl">
                <div class="row flex-fill align-items-center">
                    <div class="col">
                        <ul class="navbar-nav gap-1">
                            <li class="nav-item {{ Route::is('dashboard') ? 'active' : '' }}">
                                <a class="nav-link px-3 py-2 rounded-2 d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                                    <span class="nav-link-icon d-inline-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                                    </span>
                                    <span class="nav-link-title fw-semibold">
                                        Dashboard
                                    </span>
                                </a>
                            </li>

                            @canany(['lihat user', 'lihat level'])
                                <li class="nav-item dropdown {{ request()->is('role*') || request()->is('user*') ? 'active' : '' }}">
                                    <a class="nav-link dropdown-toggle px-3 py-2 rounded-2 d-flex align-items-center gap-2" href="#navbar-master" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                        <span class="nav-link-icon d-inline-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h6v6h-6z" /><path d="M14 4h6v6h-6z" /><path d="M4 14h6v6h-6z" /><path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /></svg>
                                        </span>
                                        <span class="nav-link-title fw-semibold">
                                            Master Data
                                        </span>
                                    </a>
                                    <div class="dropdown-menu shadow-lg border-0 rounded-3 mt-1">
                                        @can('lihat level')
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ Route::is('role*') ? 'active' : '' }}" href="{{ route('role.index') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                                <span>Data Level / Role</span>
                                            </a>
                                        @endcan
                                        @can('lihat user')
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ Route::is('user*') ? 'active' : '' }}" href="{{ route('user.index') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-info"><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                                <span>Data Pengguna (Users)</span>
                                            </a>
                                        @endcan
                                    </div>
                                </li>
                            @endcanany

                            @canany(['lihat pemasukan', 'lihat pengeluaran', 'lihat kategori keuangan'])
                                <li class="nav-item dropdown {{ request()->is('income*') || request()->is('expense*') || request()->is('finance-category*') ? 'active' : '' }}">
                                    <a class="nav-link dropdown-toggle px-3 py-2 rounded-2 d-flex align-items-center gap-2" href="#navbar-finance" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                        <span class="nav-link-icon d-inline-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 10l18 0" /><path d="M5 6l7 -3l7 3" /><path d="M4 10l0 11" /><path d="M20 10l0 11" /><path d="M8 14l0 3" /><path d="M12 14l0 3" /><path d="M16 14l0 3" /></svg>
                                        </span>
                                        <span class="nav-link-title fw-semibold">
                                            Finance
                                        </span>
                                    </a>
                                    <div class="dropdown-menu shadow-lg border-0 rounded-3 mt-1">
                                        @can('lihat pemasukan')
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ Route::is('income*') ? 'active' : '' }}" href="{{ route('income.index') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-success"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                                <span>Pemasukan</span>
                                            </a>
                                        @endcan
                                        @can('lihat pengeluaran')
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ Route::is('expense*') ? 'active' : '' }}" href="{{ route('expense.index') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-danger"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                                <span>Pengeluaran</span>
                                            </a>
                                        @endcan
                                        @can('lihat kategori keuangan')
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ Route::is('finance-category*') ? 'active' : '' }}" href="{{ route('finance-category.index') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-warning"><path d="M4 4h6v6h-6z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6h-6z"/><path d="M14 14h6v6h-6z"/></svg>
                                                <span>Kategori Keuangan</span>
                                            </a>
                                        @endcan
                                    </div>
                                </li>
                            @endcanany

                            @can('lihat saldo')
                                <li class="nav-item {{ request()->is('saldo-website*') ? 'active' : '' }}">
                                    <a class="nav-link px-3 py-2 rounded-2 d-flex align-items-center gap-2" href="{{ route('saldo-website.index') }}">
                                        <span class="nav-link-icon d-inline-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 5v11a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-11" /><path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M9 3v1" /><path d="M15 3v1" /></svg>
                                        </span>
                                        <span class="nav-link-title fw-semibold">
                                            Pengaturan Saldo
                                        </span>
                                    </a>
                                </li>
                            @endcan

                            @can('Api Management')
                                <li class="nav-item {{ request()->is('api-management*') ? 'active' : '' }}">
                                    <a class="nav-link px-3 py-2 rounded-2 d-flex align-items-center gap-2" href="{{ route('api-management.index') }}">
                                        <span class="nav-link-icon d-inline-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M12 2a4 4 0 0 1 3.95 3.44a4 4 0 0 1 4.05 4.06a4 4 0 0 1 -3.55 3.97a4 4 0 0 1 -4.45 4.53a4 4 0 0 1 -4.45 -4.53a4 4 0 0 1 -3.55 -3.97a4 4 0 0 1 4.05 -4.06a4 4 0 0 1 3.95 -3.44z" /></svg>
                                        </span>
                                        <span class="nav-link-title fw-semibold">
                                            API Management
                                        </span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
