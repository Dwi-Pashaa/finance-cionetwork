<table class="table card-table table-vcenter text-nowrap datatable">
    <thead>
        <tr>
            <th class="w-1">Code</th>
            <th>Admin</th>
            <th>Investor</th>
            <th>Jumlah</th>
            <th>Bank</th>
            <th>Tanggal Transfer</th>
            <th>Tangal Konfirmasi</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transfers as $item)
            <tr>
                <td>
                    {{ $item->code }}
                </td>
                <td>
                    {{ $item->admin->name }}
                </td>
                <td>
                    {{ $item->investor->name }}
                </td>
                <td>
                    Rp. {{ number_format($item->amount) }}
                </td>
                <td>
                    {{ $item->payment_method }}
                </td>
                <td>
                    {{ \Carbon\Carbon::parse($item->transfer_date)->format('d/m/Y') }}
                </td>
                <td>
                    {{ $item->confirmation_date ? \Carbon\Carbon::parse($item->confirmation_date)->format('d/m/Y') : 'Belum Konfirmasi' }}
                </td>
                <td>
                    @if ($item->status === 'success')
                        <span class="badge bg-success text-white">
                            {{ strtoupper($item->status) }}
                        </span>
                    @else
                        <span class="badge bg-warning text-white">
                            {{ strtoupper($item->status) }}
                        </span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">Tidak Ada Data</td>
            </tr>
        @endforelse
    </tbody>
</table>