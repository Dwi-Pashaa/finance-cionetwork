@extends('layouts.app')

@section('title')
    Hak Akses Level - {{ $role->name }}
@endsection

@section('content') 
@include('components.alert.success')

@php
    // Kelompokkan permission berdasarkan modul agar daftar mudah dipindai.
    $moduleGroups = [
        'Level & Hak Akses' => ['level'],
        'Manajemen Pengguna' => ['user', 'pengguna'],
        'Finance - Pemasukan' => ['pemasukan'],
        'Finance - Pengeluaran' => ['pengeluaran'],
        'Finance - Kategori' => ['kategori keuangan'],
        'Saldo Website' => ['saldo'],
        'API Management' => ['api management'],
        'History & Laporan' => ['log history', 'download pdf', 'download excel'],
    ];

    $groupedPermissions = [];
    $handledIds = [];
    foreach ($moduleGroups as $moduleName => $keywords) {
        $items = $permissions->filter(function ($p) use ($keywords, $handledIds) {
            if (in_array($p->id, $handledIds, true)) {
                return false;
            }

            $name = strtolower($p->name);
            foreach ($keywords as $keyword) {
                if (str_contains($name, strtolower($keyword))) {
                    return true;
                }
            }
            return false;
        });

        $groupedPermissions[$moduleName] = [
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-primary"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><path d="M9 12l2 2l4 -4" /></svg>',
            'items' => $items
        ];

        $handledIds = array_merge($handledIds, $items->pluck('id')->all());
    }

    // Permission baru tetap ditampilkan agar tidak hilang dari halaman.
    $otherPermissions = $permissions->whereNotIn('id', $handledIds);
    if ($otherPermissions->count() > 0) {
        $groupedPermissions['Sistem & Lainnya'] = [
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-primary"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 8l.01 0" /><path d="M12 12l0 4" /></svg>',
            'items' => $otherPermissions
        ];
    }

    // Urutkan hak akses: lihat, tambah/buat, edit/ubah, hapus, lainnya.
    $actionOrder = fn($name) => str_contains(strtolower($name), 'lihat') ? 0 : (str_contains(strtolower($name), 'tambah') || str_contains(strtolower($name), 'buat') ? 1 : (str_contains(strtolower($name), 'edit') || str_contains(strtolower($name), 'ubah') ? 2 : (str_contains(strtolower($name), 'hapus') ? 3 : 4)));
    foreach ($groupedPermissions as &$module) {
        $module['items'] = $module['items']->sortBy($actionOrder)->values();
    }
    unset($module);
@endphp

<div class="card shadow-sm border-0">
    <!-- Card Header -->
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3 px-4 bg-white border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 class="card-title fw-bold text-dark mb-0">Hak Akses Role:</h3>
                <span class="badge badge-soft-primary px-3 py-1 fw-bold fs-6">{{ $role->name }}</span>
            </div>
            <div class="text-muted small mt-1">Konfigurasi izin fitur dan batasan menu sistem untuk tingkatan pengguna ini</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="toggleAllBtn" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 shadow-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l3 3l8 -8" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
                Pilih Semua Akses
            </button>
            <a href="{{ route('role.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
                Kembali
            </a>
        </div>
    </div>

    <!-- Form Content -->
    <form action="{{ route('role.savePermission', ['id' => $role->id]) }}" method="POST">
        @csrf
        @method("PUT")
        
        <div class="card-body p-4 bg-light-subtle">
            <div class="row g-4">
                @foreach ($groupedPermissions as $moduleName => $moduleData)
                    @if ($moduleData['items']->count() > 0)
                        <div class="col-lg-6 col-12">
                            <div class="card shadow-none border bg-white h-100" style="border-radius: 10px;">
                                <!-- Module Header -->
                                <div class="card-header py-2.5 px-3 bg-white d-flex justify-content-between align-items-center border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        {!! $moduleData['icon'] !!}
                                        <span class="fw-bold text-dark">{{ $moduleName }}</span>
                                    </div>
                                    <span class="badge bg-light text-muted small fw-normal">
                                        {{ $moduleData['items']->count() }} Izin
                                    </span>
                                </div>
                                <!-- Module Permissions List -->
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        @foreach ($moduleData['items'] as $item)
                                            @php
                                                $isChecked = $role->hasPermissionTo($item->name);
                                                $pName = strtolower($item->name);
                                                
                                                // Icon based on permission type
                                                $pIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1 text-secondary"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /></svg>';
                                                
                                                if (str_contains($pName, 'lihat')) {
                                                    $pIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>';
                                                } elseif (str_contains($pName, 'tambah')) {
                                                    $pIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>';
                                                } elseif (str_contains($pName, 'ubah') || str_contains($pName, 'edit')) {
                                                    $pIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>';
                                                } elseif (str_contains($pName, 'hapus')) {
                                                    $pIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /></svg>';
                                                } elseif (str_contains($pName, 'excel') || str_contains($pName, 'download')) {
                                                    $pIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M10 12l4 4m0 -4l-4 4" /></svg>';
                                                }
                                            @endphp
                                            <div class="col-sm-6 col-12">
                                                <div class="permission-item p-2 border rounded-2 d-flex align-items-center justify-content-between {{ $isChecked ? 'bg-primary-subtle border-primary-subtle' : 'bg-white' }}" style="transition: all 0.15s ease-in-out;">
                                                    <label class="form-check form-switch m-0 d-flex align-items-center justify-content-between w-100 cursor-pointer">
                                                        <div class="d-flex align-items-center">
                                                            {!! $pIcon !!}
                                                            <span class="form-check-label fw-semibold text-dark small text-capitalize ms-1">
                                                                {{ $item->name }}
                                                            </span>
                                                        </div>
                                                        <input class="form-check-input permission-checkbox" 
                                                               name="permissions[]" 
                                                               value="{{ $item->name }}" 
                                                               type="checkbox" 
                                                               {{ $isChecked ? 'checked' : '' }}>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3 px-4 border-top">
            <a href="{{ route('role.index') }}" class="btn btn-outline-secondary px-3">
                Batal
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                Simpan Hak Akses
            </button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        // Toggle item styling on check/uncheck
        $('.permission-checkbox').on('change', function() {
            var item = $(this).closest('.permission-item');
            if ($(this).is(':checked')) {
                item.addClass('bg-primary-subtle border-primary-subtle');
            } else {
                item.removeClass('bg-primary-subtle border-primary-subtle');
            }
        });

        // Master toggle all permissions
        var allSelected = false;
        $('#toggleAllBtn').on('click', function() {
            allSelected = !allSelected;
            $('.permission-checkbox').prop('checked', allSelected).trigger('change');
            
            if (allSelected) {
                $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg> Batalkan Semua Akses');
            } else {
                $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l3 3l8 -8" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg> Pilih Semua Akses');
            }
        });
    });
</script>
@endpush
