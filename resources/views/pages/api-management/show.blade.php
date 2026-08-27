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
            <div class="card-body px-4">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Client Name</div>
                        <div class="datagrid-content">{{ $client->name }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Code</div>
                        <div class="datagrid-content"><span class="badge bg-blue-lt">{{ $client->code }}</span></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Client ID</div>
                        <div class="datagrid-content"><code>{{ $client->client_id }}</code></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Saldo</div>
                        <div class="datagrid-content fw-bold">Rp {{ number_format($client->balance?->balance ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Rate Limit</div>
                        <div class="datagrid-content">{{ $client->rate_limit_per_minute }} request/menit</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Last Used</div>
                        <div class="datagrid-content">{{ $client->last_used_at ? \Carbon\Carbon::parse($client->last_used_at)->format('d M Y, H:i') : '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Last IP</div>
                        <div class="datagrid-content">{{ $client->last_ip ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Deskripsi</div>
                        <div class="datagrid-content">{{ $client->description ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="card-footer py-3 px-4 bg-white border-top d-flex flex-wrap gap-2">
                <a href="{{ route('api-management.edit', $client->id) }}" class="btn btn-outline-primary">Edit Client</a>

                @if ($client->status->value === 'active')
                    <form method="POST" action="{{ route('api-management.disable', $client->id) }}" onsubmit="return confirmDisable(this)" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">Disable Client</button>
                    </form>
                @elseif ($client->status->value === 'inactive')
                    <form method="POST" action="{{ route('api-management.enable', $client->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Enable Client</button>
                    </form>
                @endif

                @if ($client->status->value !== 'revoked')
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#revokeModal">Revoke Client</button>
                @endif

                <a href="{{ route('api-management.index') }}" class="btn btn-outline-secondary ms-auto">Kembali</a>
            </div>
        </div>

        <!-- Credentials -->
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 px-4 bg-white border-bottom">
                <h3 class="card-title fw-bold text-dark mb-0">Credentials</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
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
                                <td><code>{{ $credential->key_id }}</code></td>
                                <td>
                                    @if ($credential->isActive())
                                        <span class="badge bg-success-lt">Active</span>
                                    @elseif ($credential->status->value === 'revoked')
                                        <span class="badge bg-danger-lt">Revoked</span>
                                    @else
                                        <span class="badge bg-secondary-lt">Expired</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $credential->expires_at ? \Carbon\Carbon::parse($credential->expires_at)->format('d M Y, H:i') : '-' }}</td>
                                <td class="text-muted small">{{ $credential->last_used_at ? \Carbon\Carbon::parse($credential->last_used_at)->format('d M Y, H:i') : '-' }}</td>
                                <td class="text-muted small">{{ \Carbon\Carbon::parse($credential->created_at)->format('d M Y, H:i') }}</td>
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
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ada credential</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer py-3 px-4 bg-white border-top">
                @if ($client->status->value === 'active' && $client->credentials()->where('status', 'active')->exists())
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rotateModal">Rotate Secret</button>
                @else
                    <button type="button" class="btn btn-primary" disabled title="Hanya client active dengan kredential aktif yang bisa rotate">Rotate Secret</button>
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
