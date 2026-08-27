@extends('layouts.app')

@section('title')
    Data Level / Role
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3 px-4 bg-white border-bottom">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Manajemen Level & Otorisasi</h3>
            <div class="text-muted small">Kelola tingkatan role dan hak akses fitur pengguna</div>
        </div>
        @can('tambah level')
            <a href="javascript:void(0)" id="addBtn" data-bs-toggle="modal" data-bs-target="#modal-simple" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                Tambah Level
            </a>
        @endcan
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="filter-toolbar">
        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-auto d-flex align-items-center gap-2">
                <span class="text-muted small fw-medium">Tampilkan:</span>
                <select name="sort" id="sort" class="form-select form-select-sm" style="width: 75px;">
                    @foreach ([10, 25, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ request('sort') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <span class="text-muted small fw-medium">data</span>
            </div>
            <div class="col-md-4 col-12">
                <form method="GET">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama level/role...">
                        <button class="btn btn-primary px-3" type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px;">No</th>
                    <th>Nama Level / Role</th>
                    <th>Tanggal Dibuat</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $item)
                    <tr>
                        <td class="text-center text-muted fw-semibold">
                            {{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}
                        </td>
                        <td>
                            <span class="badge badge-soft-primary px-3 py-1 fw-bold fs-6">
                                {{ $item->name }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }}
                        </td>
                        <td class="text-center">
                            <div class="action-btn-group">
                                @can('edit level')
                                    <a href="{{ route('role.permission', ['id' => $item->id]) }}" class="btn-action btn-action-primary" title="Atur Hak Akses / Permission">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.5 21h-4.5a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /><path d="M20 21l2 -2l-2 -2" /><path d="M17 17l-2 2l2 2" /></svg>
                                    </a>
                                    <a href="javascript:void(0)" onclick="return editModal('{{ $item->id }}')" class="btn-action btn-action-warning" title="Edit Level">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                    </a>
                                @endcan
                                @can('hapus level')
                                    <button type="button" onclick="return deleteType('{{ $item->id }}')" class="btn-action btn-action-danger" title="Hapus Level">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                    </button>
                                @endcan
                            </div>
                        </td> 
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">Tidak Ada Data Level</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
        <p class="m-0 text-muted small">
            Menampilkan <span class="fw-semibold text-dark">{{ $roles->firstItem() ?? 0 }}</span> - <span class="fw-semibold text-dark">{{ $roles->lastItem() ?? 0 }}</span> dari <span class="fw-semibold text-dark">{{ $roles->total() }}</span> total entri
        </p>
        <div class="m-0">
            {{ $roles->links() }}
        </div>
    </div>
</div>
@endsection

@push('modal')
@canany(['tambah level', 'edit level'])
<div class="modal modal-blur fade" id="modal-simple" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-3 px-4 bg-white border-bottom">
                <h5 class="modal-title fw-bold text-dark">Tambah Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="type" id="type">
                <input type="hidden" name="id" id="id">
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Level <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: Manager / Staff">
                    <span class="invalid-feedback error_name"></span>
                </div>
            </div>
            <div class="modal-footer py-3 px-4 bg-light-subtle">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="storeBtn" class="btn btn-primary px-4">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endcanany
@endpush

@push('js')
<script>
    const BASE = "{{ route('role.index') }}";

    let params = new URLSearchParams(window.location.search);
    $("#sort").change(function() {
        params.set('sort', $(this).val());
        window.location.href = BASE + '?' + params.toString();
    });

    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    $("#addBtn").click(function() {
        $(".modal-title").html("Tambah Level");
        $("#name").val("");
        $("#type").val("create");
        $("#id").val("");
    });

    $("#storeBtn").click(function() {
        let id = $("#id").val();
        let type = $("#type").val()
        let name = $("#name").val();

        let url;
        let method;

        if (type === 'create') {
            url = BASE + '/store';
            method = "POST";
        } else {
            url = BASE + `/${id}/update`
            method = "PUT";
        }
        
        $.ajax({
            url: url,
            method: method,
            data: {
                name: name
            },
        }).done(function(response) {
            if (response.errors) {
                $.each(response.errors, function(index, value) {
                    $("#name").addClass('is-invalid');
                    $(".error_" + index).html(value);

                    setTimeout(() => {
                        $("#name").removeClass('is-invalid');
                        $(".error_" + index).html('');
                    }, 3000);
                })                
            } else {
                $("#modal-simple").modal('hide')
                Toast.fire({
                    icon: response.status,
                    title: response.message
                });

                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.log("Error:", textStatus, errorThrown);
        });
    });

    function editModal(id) {
        let url = BASE + `/${id}/show`
        $.ajax({
            url: url,
            method: "GET",
            dataType: "json"
        }).done(function(response){
            $(".modal-title").html("Edit Level");
            let data = response.data;
            $("#modal-simple").modal('show')

            $("#id").val(data.id);
            $("#name").val(data.name);
            $("#type").val("update");
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.log("Error:", textStatus, errorThrown);
        });
    }

    function deleteType(id) {
        Swal.fire({
            title: "Konfirmasi Hapus",
            text: "Apakah Anda yakin ingin menghapus level ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#1e40af",
            cancelButtonColor: "#ef4444",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE + '/' + id + '/destroy',
                    method: "DELETE",
                    dataType: "json",
                    success: function(response) {
                        Toast.fire({
                            icon: response.status,
                            title: response.message
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    },
                    error: function(err) {
                        Toast.fire({
                            icon: "error",
                            title: "Gagal menghapus data dari server."
                        });
                    }
                })
            }
        });
    }
</script>
@endpush
