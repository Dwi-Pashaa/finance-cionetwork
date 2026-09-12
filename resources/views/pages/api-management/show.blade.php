@extends('layouts.app')

@section('title')
    Detail API Client
@endsection

@section('content')
@include('components.alert.success')
@include('components.alert.danger')
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom">
                <div>
                    <h3 class="card-title fw-bold text-dark mb-1">{{ $client->name }}</h3>
                    <div class="text-muted small">Detail API Client</div>
                </div>
                @if ($client->status->value === 'active')
                    <span class="badge bg-success-lt px-2.5 py-1">ACTIVE</span>
                @elseif ($client->status->value === 'inactive')
                    <span class="badge bg-warning-lt px-2.5 py-1">DISABLED</span>
                @else
                    <span class="badge bg-danger-lt px-2.5 py-1">REVOKED</span>
                @endif
            </div>
            <div class="card-body px-4 py-3">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Client Name</div>
                        <div class="datagrid-content fw-bold text-dark">{{ $client->name }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Code</div>
                        <div class="datagrid-content"><span class="badge bg-primary-lt font-monospace">{{ $client->code }}</span></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Client ID</div>
                        <div class="datagrid-content"><code class="text-primary bg-primary-subtle px-2 py-0.5 rounded font-monospace">{{ $client->client_id }}</code></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Saldo Manual</div>
                        <div class="datagrid-content fw-bold text-success font-monospace">Rp {{ number_format($client->balance?->balance_manual ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Saldo Xendit</div>
                        <div class="datagrid-content fw-bold text-primary font-monospace">Rp {{ number_format($client->balance?->balance_xendit ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Total Saldo</div>
                        <div class="datagrid-content fw-bold fs-2 text-dark font-monospace">Rp {{ number_format($client->balance?->balance ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Rate Limit</div>
                        <div class="datagrid-content fw-semibold">{{ $client->rate_limit_per_minute }} <span class="text-muted fw-normal">req/menit</span></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Last Used</div>
                        <div class="datagrid-content text-muted small">{{ $client->last_used_at ? \Carbon\Carbon::parse($client->last_used_at)->format('d M Y, H:i') : '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Last IP</div>
                        <div class="datagrid-content text-muted small font-monospace">{{ $client->last_ip ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title text-muted small fw-semibold">Deskripsi</div>
                        <div class="datagrid-content text-secondary">{{ $client->description ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="card-footer py-3 px-4 bg-white border-top d-flex flex-wrap gap-2">
                <a href="{{ route('api-management.edit', $client->id) }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                    Edit Client
                </a>

                @if ($client->status->value === 'active')
                    <form method="POST" action="{{ route('api-management.disable', $client->id) }}" onsubmit="return confirmDisable(this)" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12h6" /></svg>
                            Disable Client
                        </button>
                    </form>
                @elseif ($client->status->value === 'inactive')
                    <form method="POST" action="{{ route('api-management.enable', $client->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>
                            Enable Client
                        </button>
                    </form>
                @endif

                @if ($client->status->value !== 'revoked')
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#revokeModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M5.7 5.7l12.6 12.6" /></svg>
                        Revoke Client
                    </button>
                @endif

                <a href="{{ route('api-management.index') }}" class="btn btn-outline-secondary ms-auto">Kembali</a>
            </div>
        </div>

        <!-- Credentials -->
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 px-4 bg-white border-bottom">
                <h3 class="card-title fw-bold text-dark mb-0">Credentials Active</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter table-hover">
                    <thead>
                        <tr>
                            <th>Key ID</th>
                            <th>Status</th>
                            <th>Expires At</th>
                            <th>Last Used</th>
                            <th>Dibuat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($client->credentials as $credential)
                            <tr>
                                <td><code class="text-primary bg-primary-subtle px-2 py-0.5 rounded font-monospace" style="font-size: 12px;">{{ $credential->key_id }}</code></td>
                                <td>
                                    @if ($credential->isActive())
                                        <span class="badge bg-success-lt">Active</span>
                                    @elseif ($credential->status->value === 'revoked')
                                        <span class="badge bg-danger-lt">Revoked</span>
                                    @else
                                        <span class="badge bg-secondary-lt">Expired</span>
                                    @endif
                                </td>
                                <td class="text-muted small text-nowrap">{{ $credential->expires_at ? \Carbon\Carbon::parse($credential->expires_at)->format('d M Y, H:i') : '-' }}</td>
                                <td class="text-muted small text-nowrap">{{ $credential->last_used_at ? \Carbon\Carbon::parse($credential->last_used_at)->format('d M Y, H:i') : '-' }}</td>
                                <td class="text-muted small text-nowrap">{{ \Carbon\Carbon::parse($credential->created_at)->format('d M Y, H:i') }}</td>
                                <td class="text-center">
                                    @if ($credential->isActive())
                                        <button type="button"
                                                onclick="return revokeCredential('{{ $credential->id }}')"
                                                class="btn-action btn-action-danger"
                                                title="Revoke Credential">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M5.7 5.7l12.6 12.6" /></svg>
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state-container py-3">
                                        <div class="empty-state-icon" style="width: 40px; height: 40px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /></svg>
                                        </div>
                                        <h5 class="empty-state-title" style="font-size: 13px;">Tidak ada credential aktif</h5>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer py-3 px-4 bg-white border-top">
                @if ($client->status->value === 'active' && $client->credentials()->where('status', 'active')->exists())
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rotateModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
                        Rotate Secret
                    </button>
                @else
                    <button type="button" class="btn btn-primary" disabled title="Hanya client active dengan kredential aktif yang bisa rotate">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
                        Rotate Secret
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Rotate Modal -->
<div class="modal modal-blur fade" id="rotateModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('api-management.credentials.rotate', $client->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Rotate Secret — {{ $client->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Secret baru akan dibuat dan ditampilkan sekali. Pilih mode rotasi:</p>
                    <label class="form-check mb-2">
                        <input type="radio" name="mode" value="overlap" class="form-check-input" checked>
                        <span class="form-check-label">
                            <strong>Overlap</strong> — secret lama tetap aktif 24 jam (zero downtime)
                        </span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="mode" value="immediate" class="form-check-input">
                        <span class="form-check-label">
                            <strong>Immediate</strong> — secret lama langsung dicabut
                        </span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Generate Secret Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Revoke Modal -->
<div class="modal modal-blur fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('api-management.revoke', $client->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Revoke Client — {{ $client->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        Revoke bersifat <strong>PERMANEN</strong>. Semua credential client ini akan dicabut dan semua request akan ditolak.
                    </div>
                    <label class="form-label required">Ketik kode client <code>{{ $client->code }}</code> untuk konfirmasi</label>
                    <input type="text" name="confirmation_code" class="form-control" placeholder="{{ $client->code }}" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Revoke Permanen</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    function confirmDisable(form) {
        Swal.fire({
            title: "Konfirmasi Disable",
            text: "Client akan dinonaktifkan dan semua request API akan ditolak sampai diaktifkan kembali.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#f59f00",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Ya, Disable",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }

    function revokeCredential(credentialId) {
        Swal.fire({
            title: "Konfirmasi Revoke Credential",
            text: "Kredential ini tidak akan bisa digunakan lagi.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Ya, Revoke",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('api-management.credentials.revoke', ['credentialId' => ':credentialId']) }}".replace(':credentialId', credentialId),
                    method: "POST",
                    dataType: "json",
                    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
                    success: function() {
                        window.location.reload();
                    },
                    error: function() {
                        Toast.fire({ icon: "error", title: "Gagal mencabut kredential." });
                    }
                });
            }
        });
        return false;
    }
</script>
@endpush
