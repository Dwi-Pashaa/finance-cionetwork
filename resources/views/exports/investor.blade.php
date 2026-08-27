<table class="table card-table table-vcenter text-nowrap datatable">
    <thead>
        <tr>
            <th class="w-1">No</th>
            <th>Username</th>
            <th>Nama</th>
            <th>Email</th>
            <th>No. Telepon</th>
            <th>Kategori Kerjasama</th>
            <th>Total Dana Usaha</th>
            <th>Pendapatan Bulanan</th>
            <th>Pendapatan Perbulan</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($investors as $item)
            <tr>
                <td>
                    <span class="text-secondary">
                        {{ $loop->iteration }}
                    </span>
                </td>
                <td>
                    {{ $item->user->username }}
                </td>
                <td>
                    {{ $item->user->name }}
                </td>
                <td>
                    {{ $item->user->email }}
                </td>
                <td>
                    {{ $item->user->phone ?? '-' }}
                </td>
                <td>
                    {{ optional($item->categorie)->name ?? '-' }}
                </td>
                <td>
                    Rp. {{ number_format($item->bussines_funds) }}
                </td>
                <td>
                    {{ $item->persentase }}% - {{ optional($item->type)->name ?? '' }}
                </td>
                <td>
                    Rp. {{ number_format($item->monthly_income) }}
                </td>
                <td>
                    {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">Tidak Ada Data</td>
            </tr>
        @endforelse
    </tbody>
</table>