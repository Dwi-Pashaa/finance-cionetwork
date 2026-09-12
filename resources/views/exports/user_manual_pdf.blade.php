<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Panduan Penggunaan Aplikasi - CIO Finance</title>
    <style>
        @page {
            margin: 28mm 18mm 25mm 18mm;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Running Header & Footer */
        header {
            position: fixed;
            top: -20mm;
            left: 0;
            right: 0;
            height: 12mm;
            border-bottom: 1px solid #cbd5e1;
            font-size: 8pt;
            color: #64748b;
            line-height: 12mm;
        }
        header .header-left {
            float: left;
            font-weight: bold;
            color: #1e40af;
        }
        header .header-right {
            float: right;
            color: #94a3b8;
        }

        footer {
            position: fixed;
            bottom: -18mm;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #64748b;
            line-height: 10mm;
        }
        footer .footer-left {
            float: left;
        }
        footer .footer-right {
            float: right;
        }
        .page-number:before {
            content: counter(page);
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Cover Page */
        .cover-page {
            page-break-after: always;
            text-align: center;
            padding-top: 50px;
        }
        .cover-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #1e40af;
            font-size: 9pt;
            font-weight: bold;
            padding: 6px 16px;
            border-radius: 20px;
            border: 1px solid #bfdbfe;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cover-title {
            font-size: 24pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }
        .cover-subtitle {
            font-size: 13pt;
            color: #475569;
            margin: 0 0 35px 0;
        }
        .cover-divider {
            height: 4px;
            width: 80px;
            background-color: #2563eb;
            margin: 0 auto 40px auto;
            border-radius: 2px;
        }
        .cover-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            margin: 0 auto 40px auto;
            width: 85%;
        }
        .cover-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .cover-card td {
            padding: 5px 8px;
            font-size: 9.5pt;
        }
        .cover-card td.label {
            width: 35%;
            color: #64748b;
            font-weight: bold;
        }
        .cover-card td.value {
            color: #0f172a;
            font-weight: 600;
        }
        .cover-footer-text {
            font-size: 8.5pt;
            color: #94a3b8;
            margin-top: 60px;
        }

        /* Headings */
        h1 {
            font-size: 15pt;
            color: #0f172a;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 6px;
            margin-top: 24px;
            margin-bottom: 12px;
            page-break-after: avoid;
        }
        h2 {
            font-size: 12pt;
            color: #1e40af;
            margin-top: 18px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }
        h3 {
            font-size: 10.5pt;
            color: #334155;
            margin-top: 14px;
            margin-bottom: 6px;
            page-break-after: avoid;
        }

        p {
            margin: 0 0 8px 0;
            text-align: justify;
        }

        ul, ol {
            margin: 0 0 10px 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 4px;
        }

        /* Table of Contents */
        .toc-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .toc-table td {
            padding: 6px 4px;
            border-bottom: 1px dotted #cbd5e1;
            font-size: 9.5pt;
        }
        .toc-table td.toc-num {
            width: 30px;
            font-weight: bold;
            color: #2563eb;
        }
        .toc-table td.toc-title {
            color: #1e293b;
            font-weight: 500;
        }

        /* Content Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px 0;
            font-size: 8.5pt;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            text-align: left;
            padding: 7px 9px;
            border: 1px solid #cbd5e1;
        }
        table.data-table td {
            padding: 6px 9px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Alert / Callout Boxes */
        .callout {
            border-left: 4px solid #2563eb;
            background-color: #f8fafc;
            padding: 10px 14px;
            margin: 12px 0;
            border-radius: 0 6px 6px 0;
            font-size: 9pt;
        }
        .callout-info {
            border-left-color: #3b82f6;
            background-color: #eff6ff;
            color: #1e3a8a;
        }
        .callout-success {
            border-left-color: #10b981;
            background-color: #ecfdf5;
            color: #064e3b;
        }
        .callout-warning {
            border-left-color: #f59e0b;
            background-color: #fffbeb;
            color: #78350f;
        }
        .callout-danger {
            border-left-color: #ef4444;
            background-color: #fef2f2;
            color: #7f1d1d;
        }
        .callout-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        /* Badges & Code */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-blue { background-color: #dbeafe; color: #1e40af; }
        .badge-green { background-color: #d1fae5; color: #065f46; }
        .badge-yellow { background-color: #fef3c7; color: #92400e; }
        .badge-red { background-color: #fee2e2; color: #991b1b; }

        code {
            font-family: monospace;
            background-color: #f1f5f9;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8.5pt;
            color: #0f172a;
        }

        .step-num {
            display: inline-block;
            width: 18px;
            height: 18px;
            line-height: 18px;
            background-color: #2563eb;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
            border-radius: 50%;
            margin-right: 6px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <!-- Running Header & Footer -->
    <header class="clearfix">
        <div class="header-left">CIO FINANCE &bull; BUKU PANDUAN PENGGUNAAN</div>
        <div class="header-right">CIO Network Solution</div>
    </header>

    <footer class="clearfix">
        <div class="footer-left">&copy; {{ date('Y') }} CIO Network Solution. Hak Cipta Dilindungi.</div>
        <div class="footer-right">Halaman <span class="page-number"></span></div>
    </footer>

    <!-- COVER PAGE -->
    <div class="cover-page">
        <div class="cover-badge">Dokumen Panduan Resmi Pengguna</div>
        <div class="cover-title">PANDUAN PENGGUNAAN APLIKASI<br>CIO FINANCE</div>
        <div class="cover-subtitle">Manual Pengoperasian Seluruh Modul & Menu Sistem Keuangan Terpadu</div>
        <div class="cover-divider"></div>

        <div class="cover-card">
            <table>
                <tr>
                    <td class="label">Nama Sistem</td>
                    <td class="value">: CIO Finance Management System</td>
                </tr>
                <tr>
                    <td class="label">Penyusun</td>
                    <td class="value">: Tim Pengembang CIO Network Solution</td>
                </tr>
                <tr>
                    <td class="label">Versi Dokumen</td>
                    <td class="value">: 1.0 (Produksi)</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Terbit</td>
                    <td class="value">: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Sasaran Pembaca</td>
                    <td class="value">: Administrator, Staf Keuangan, Tim Integrasi & Operator</td>
                </tr>
            </table>
        </div>

        <div class="cover-footer-text">
            Dokumen ini ditujukan sebagai pedoman operasional resmi untuk memudahkan seluruh pengguna dalam menjalankan sistem pencatatan keuangan internal, manajemen saldo, serta otorisasi API secara optimal.
        </div>
    </div>

    <!-- DAFTAR ISI & PENDAHULUAN -->
    <h1>Daftar Isi</h1>
    <table class="toc-table">
        <tr>
            <td class="toc-num">1.</td>
            <td class="toc-title">Pendahuluan & Gambaran Umum Sistem</td>
        </tr>
        <tr>
            <td class="toc-num">2.</td>
            <td class="toc-title">Akses & Autentikasi Sistem</td>
        </tr>
        <tr>
            <td class="toc-num">3.</td>
            <td class="toc-title">Menu Dashboard Overview & Metrik KPI</td>
        </tr>
        <tr>
            <td class="toc-num">4.</td>
            <td class="toc-title">Menu Master Data (Level / Role & Pengguna)</td>
        </tr>
        <tr>
            <td class="toc-num">5.</td>
            <td class="toc-title">Menu Finance (Pemasukan, Pengeluaran, Biaya Admin, & Kategori)</td>
        </tr>
        <tr>
            <td class="toc-num">6.</td>
            <td class="toc-title">Menu Pengaturan Saldo Website (Dual-Pocket & Top-Up Xendit)</td>
        </tr>
        <tr>
            <td class="toc-num">7.</td>
            <td class="toc-title">Menu API Management (Kredensial, Rotasi Key, & Otorisasi)</td>
        </tr>
        <tr>
            <td class="toc-num">8.</td>
            <td class="toc-title">Matriks Hak Akses Pengguna (Permissions Guide)</td>
        </tr>
        <tr>
            <td class="toc-num">9.</td>
            <td class="toc-title">Pertanyaan Umum & Solusi Masalah (FAQ)</td>
        </tr>
    </table>

    <h1>1. Pendahuluan & Gambaran Umum Sistem</h1>
    <p>
        <strong>CIO Finance</strong> adalah aplikasi web manajemen keuangan berbasis Laravel yang dirancang khusus untuk memenuhi kebutuhan pembukuan arus kas internal serta mengelola integrasi saldo multi-website klien secara aman dan terpusat.
    </p>
    
    <div class="callout callout-info">
        <div class="callout-title">Tujuan Utama Sistem:</div>
        <ul>
            <li>Memberikan transparansi dan akurasi terhadap pencatatan arus dana masuk (pemasukan) dan keluar (pengeluaran).</li>
            <li>Menyediakan pemantauan saldo payment gateway <strong>Xendit</strong> secara langsung (*real-time*).</li>
            <li>Mengatur saldo website klien dengan model <em>Dual-Pocket</em> (Saldo Manual & Saldo Xendit) yang dapat dikontrol aktif/tidaknya per-klien.</li>
            <li>Menjamin keamanan koneksi aplikasi eksternal melalui mekanisme autentikasi <em>HMAC-SHA256</em> dan rotasi kredensial <em>zero-downtime</em>.</li>
        </ul>
    </div>

    <div class="page-break"></div>

    <h1>2. Akses & Autentikasi Sistem</h1>
    <h2>2.1. Masuk ke Aplikasi (Login)</h2>
    <p>Untuk memulai penggunaan sistem, buka browser web dan akses alamat resmi aplikasi.</p>
    <ol>
        <li>Ketikkan <strong>Username</strong> atau <strong>Email</strong> yang telah terdaftar pada database.</li>
        <li>Masukkan <strong>Password</strong> akun Anda.</li>
        <li>Klik tombol <strong>Sign In</strong>. Sistem akan memverifikasi kredensial dan mengarahkan Anda ke Dashboard jika berhasil.</li>
    </ol>

    <h2>2.2. Struktur Menu Navigasi</h2>
    <p>Navigasi terletak pada bilah menu horizontal di bagian atas antarmuka:</p>
    <ul>
        <li><strong>Dashboard</strong>: Ringkasan metrik, grafik performa, dan riwayat aktivitas mutasi.</li>
        <li><strong>Master Data</strong>: Sub-menu <em>Data Level / Role</em> dan <em>Data Pengguna (Users)</em>.</li>
        <li><strong>Finance</strong>: Sub-menu <em>Pemasukan</em>, <em>Pengeluaran</em>, dan <em>Kategori Keuangan</em>.</li>
        <li><strong>Pengaturan Saldo</strong>: Manajemen saldo website klien dan top-up Xendit.</li>
        <li><strong>API Management</strong>: Konfigurasi teknis klien API, pembuatan kunci rahasia, dan batasan *rate limit*.</li>
        <li><strong>Profil Pengguna & Logout</strong>: Di sebelah kanan atas untuk melihat akun aktif dan keluar dari sistem.</li>
    </ul>

    <h1>3. Menu: Dashboard Overview</h1>
    <p>Dashboard adalah pusat kendali eksekutif untuk melihat kesehatan finansial secara instan.</p>
    
    <h2>3.1. Kartu Indikator KPI Keuangan</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Kartu Metrik</th>
                <th style="width: 15%;">Warna</th>
                <th>Deskripsi & Cara Perhitungan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Pemasukan Bulan Ini</strong></td>
                <td><span class="badge badge-green">Hijau</span></td>
                <td>Total seluruh transaksi pemasukan terverifikasi pada bulan berjalan.</td>
            </tr>
            <tr>
                <td><strong>Pengeluaran Bulan Ini</strong></td>
                <td><span class="badge badge-red">Merah</span></td>
                <td>Total dana keluar (termasuk biaya operasional dan biaya admin bank) pada bulan berjalan.</td>
            </tr>
            <tr>
                <td><strong>Net Bulan Ini</strong></td>
                <td><span class="badge badge-blue">Biru / Merah</span></td>
                <td>Selisih bersih (Pemasukan &minus; Pengeluaran). Menandakan kondisi <em>Surplus</em> jika bernilai positif atau <em>Defisit</em> jika negatif.</td>
            </tr>
            <tr>
                <td><strong>Saldo Bersih Kumulatif</strong></td>
                <td><span class="badge badge-blue">Biru</span></td>
                <td>Total akumulasi saldo kas bersih sejak awal sistem digunakan hingga saat ini.</td>
            </tr>
        </tbody>
    </table>

    <h2>3.2. Integrasi & Monitoring Xendit</h2>
    <p>
        Sistem terhubung langsung dengan payment gateway Xendit. Di bagian dashboard akan ditampilkan saldo <strong>Cash (Available)</strong> yang siap ditarik serta saldo <strong>Pending</strong>.
    </p>
    <div class="callout callout-success">
        <div class="callout-title">Tombol "Perbarui Saldo Xendit":</div>
        Gunakan tombol ini untuk menghapus cache lokal dan menyinkronkan data mutasi terbaru secara instan langsung dari server Xendit.
    </div>

    <h2>3.3. Grafik Analisis & Log Aktivitas</h2>
    <ul>
        <li><strong>Grafik Tren Pendapatan vs Pengeluaran</strong>: Memvisualisasikan kurva arus kas dengan opsi filter waktu: <em>Hari Ini (per jam)</em>, <em>7 Hari Terakhir</em>, <em>30 Hari Terakhir</em>, dan <em>Tahun Ini</em>.</li>
        <li><strong>Diagram Kategori</strong>: Memperlihatkan komposisi pengeluaran terbesar per kategori pos biaya.</li>
        <li><strong>Linimasa Aktivitas (Activity Logs)</strong>: Catatan audit otomatis yang mencatat siapa staf yang menambah, mengubah, atau menghapus data transaksi.</li>
    </ul>

    <div class="page-break"></div>

    <h1>4. Menu: Master Data</h1>

    <h2>4.1. Data Level / Role (Tingkatan Wewenang)</h2>
    <p>
        Menu ini digunakan untuk mengatur tingkatan pengguna serta hak akses fitur apa saja yang boleh dibuka oleh masing-masing level (contoh: <em>Super Admin, Finance, Operator</em>).
    </p>
    <p><strong>Langkah Mengatur Matriks Hak Akses:</strong></p>
    <ol>
        <li>Buka menu <strong>Master Data</strong> &rarr; <strong>Data Level / Role</strong>.</li>
        <li>Klik tombol <strong>Hak Akses</strong> (ikon perisai/kunci) pada baris role yang ingin dikonfigurasi.</li>
        <li>Pada halaman matriks izin yang terbuka, centang fitur yang diperbolehkan untuk tingkatan tersebut.</li>
        <li>Gunakan tombol <strong>Pilih Semua Akses</strong> untuk memberikan seluruh izin secara instan.</li>
        <li>Klik tombol <strong>Simpan Hak Akses</strong> di bagian bawah.</li>
    </ol>

    <h2>4.2. Data Pengguna (Users)</h2>
    <p>Menu untuk mendaftarkan dan memelihara akun staf atau pengguna sistem.</p>
    <ul>
        <li><strong>Tambah Pengguna Baru</strong>: Klik <em>+ Tambah User</em>, isi nama, username unik, alamat email, pilih role/level, dan ketikkan kata sandi (minimal 8 karakter).</li>
        <li><strong>Edit & Reset Password</strong>: Klik tombol <em>Edit</em> pada baris pengguna. Untuk mereset password, cukup ketikkan password baru pada formulir; jika tidak ingin mengubah password, biarkan kolom password kosong.</li>
        <li><strong>Hapus Pengguna</strong>: Menghapus akses akun staf yang sudah tidak aktif.</li>
    </ul>

    <h1>5. Menu: Finance (Keuangan Internal)</h1>

    <h2>5.1. Pemasukan (Income)</h2>
    <p>Mencatat setiap transaksi uang masuk ke rekening/kas perusahaan (misal: pembayaran klien, termin proyek, pendapatan langganan).</p>
    <div class="callout callout-info">
        <div class="callout-title">Langkah Pencatatan Pemasukan:</div>
        <ol>
            <li>Buka menu <strong>Finance</strong> &rarr; <strong>Pemasukan</strong>, lalu klik <strong>+ Tambah Pemasukan</strong>.</li>
            <li>Pilih <strong>Tanggal Transaksi</strong> dan <strong>Kategori Pemasukan</strong> yang sesuai.</li>
            <li>Ketikkan <strong>Nominal Uang Masuk</strong> (sistem otomatis memformat angka ke Rupiah).</li>
            <li>Isi <strong>Sumber Dana</strong> (contoh: <em>Transfer BCA, Kas Tunai, Virtual Account Mandiri</em>).</li>
            <li>Tuliskan <strong>Catatan / Keterangan</strong> peruntukan dana dan klik <strong>Simpan</strong>.</li>
        </ol>
    </div>

    <h2>5.2. Pengeluaran (Expense) & Mekanisme Biaya Admin</h2>
    <p>
        Mencatat pengeluaran operasional perusahaan seperti tagihan server, gaji, langganan software, dan biaya kantor.
    </p>
    <div class="callout callout-warning">
        <div class="callout-title">Fitur Biaya Admin Bank / Transfer Fee:</div>
        Jika transaksi transfer dikenakan biaya admin oleh bank (misal: Rp 6.500 atau Rp 2.500):
        <ol>
            <li>Centang kotak <strong>"Termasuk Biaya Admin"</strong> pada formulir pengeluaran.</li>
            <li>Masukkan nominal biaya admin pada kolom yang tersedia.</li>
            <li>Sistem akan secara otomatis menjumlahkan <strong>Total Pengeluaran = Nominal Pokok + Biaya Admin</strong> sehingga pembukuan kas tetap presisi hingga satuan terkecil.</li>
        </ol>
    </div>

    <h2>5.3. Kategori Keuangan</h2>
    <p>
        Mengatur klasifikasi pos anggaran agar laporan tersusun teratur. Kategori terbagi menjadi dua tipe: <strong>Pemasukan (Income)</strong> dan <strong>Pengeluaran (Expense)</strong>.
    </p>
    <p>
        <em>Proteksi Data:</em> Kategori yang telah terikat dengan transaksi tidak dapat dihapus sembarangan agar riwayat pembukuan tidak rusak. Gunakan fitur nonaktifkan status kategori sebagai gantinya.
    </p>

    <div class="page-break"></div>

    <h1>6. Menu: Pengaturan Saldo Website (Saldo Client)</h1>
    <p>
        Menu ini dirancang khusus untuk memonitor dan mengontrol saldo website/mitra yang terhubung ke CIO Finance via integrasi API.
    </p>

    <h2>6.1. Konsep "Dual-Pocket Balance" (Dua Kantong Saldo)</h2>
    <p>Setiap website klien memiliki 2 kantong saldo yang terpisah secara independen:</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Kantong Saldo</th>
                <th style="width: 35%;">Mekanisme Pengisian</th>
                <th>Tujuan & Peruntukan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Saldo Manual</strong></td>
                <td>Diisi / disesuaikan oleh administrator melalui formulir penyesuaian internal.</td>
                <td>Setoran deposit manual, transfer langsung rekening bank kantor, bonus saldo, atau koreksi selisih.</td>
            </tr>
            <tr>
                <td><strong>Saldo Xendit</strong></td>
                <td>Terisi otomatis melalui invoice / payment link resmi Xendit.</td>
                <td>Pembayaran online via QRIS, Virtual Account, dan E-Wallet dengan pelunasan otomatis via Webhook.</td>
            </tr>
            <tr>
                <td><strong>Total Saldo</strong></td>
                <td><code>Saldo Manual + Saldo Xendit</code></td>
                <td>Total pagu dana aktif yang dapat dipotong saat klien melakukan transaksi API.</td>
            </tr>
        </tbody>
    </table>

    <h2>6.2. Saklar Jalur Saldo (Toggle Switch ON/OFF Per-Client)</h2>
    <p>
        Pada tabel daftar klien, administrator dapat mengaktifkan atau menonaktifkan jalur pengisian saldo untuk masing-masing klien secara individual:
    </p>
    <ul>
        <li><strong>Saklar Manual ON / OFF</strong>: Menutup atau membuka izin penambahan saldo manual.</li>
        <li><strong>Saklar Xendit ON / OFF</strong>: Menutup atau membuka fitur pembuatan tagihan pembayaran Xendit.</li>
        <li>Perubahan saklar dieksekusi secara <em>real-time AJAX</em> tanpa perlu memuat ulang halaman.</li>
    </ul>

    <h2>6.3. Penyesuaian Saldo (Adjust In & Adjust Out)</h2>
    <ol>
        <li>Klik tombol <strong>Atur Saldo</strong> pada baris klien yang dipilih.</li>
        <li>Pilih kantong saldo yang ingin disesuaikan (<em>Saldo Manual</em> atau <em>Saldo Xendit</em>).</li>
        <li>Pilih tipe aksi: <strong>Tambah Saldo (+)</strong> atau <strong>Kurangi Saldo (&minus;)</strong>.</li>
        <li>Masukkan nominal rupiah dan wajib mengisi <strong>Alasan Penyesuaian</strong> untuk catatan audit.</li>
        <li>Klik <strong>Simpan Penyesuaian</strong>.</li>
    </ol>

    <h2>6.4. Pembuatan Top-Up Xendit (Invoice Gateway)</h2>
    <p>
        Administrator dapat membuat tautan pembayaran resmi Xendit untuk klien. Begitu invoice dilunasi oleh klien, sistem Xendit akan mengirimkan webhook ke server CIO Finance dan saldo klien otomatis bertambah tanpa intervensi manual.
    </p>

    <div class="page-break"></div>

    <h1>7. Menu: API Management (Admin & Integrator)</h1>
    <p>
        Menu teknis untuk mengelola kredensial koneksi bagi website eksternal yang ingin terhubung ke sistem CIO Finance.
    </p>

    <h2>7.1. Mendaftarkan Client API Baru & Provisioning Secret</h2>
    <ol>
        <li>Buka menu <strong>API Management</strong> &rarr; klik <strong>+ Tambah Client</strong>.</li>
        <li>Isi <em>Nama Klien</em>, <em>Kode Unik Klien</em> (contoh: <code>PORTAL_CIO</code>), dan batas <em>Rate Limit per Menit</em>.</li>
        <li>Klik tombol <strong>Daftarkan Client</strong>.</li>
        <li>
            <strong>Halaman Provisioning Secret (Sangat Penting):</strong> Sistem akan membuat <em>API Secret Key</em> terenkripsi dan menampilkannya <strong>hanya 1 kali</strong>. Salin dan simpan Secret Key di tempat yang aman karena tidak akan dapat dilihat kembali demi keamanan.
        </li>
    </ol>

    <h2>7.2. Status Akses & Otorisasi Klien</h2>
    <ul>
        <li><span class="badge badge-green">ACTIVE</span>: Klien dapat memanggil seluruh endpoint API dengan normal.</li>
        <li><span class="badge badge-yellow">DISABLED</span>: Klien dinonaktifkan sementara; seluruh panggilan API akan ditolak hingga diaktifkan kembali.</li>
        <li><span class="badge badge-red">REVOKED</span>: Akses dicabut secara permanen. Tindakan ini memerlukan konfirmasi pengetikan ulang kode klien.</li>
    </ul>

    <h2>7.3. Rotasi Kredensial (Rotate Secret Key Zero-Downtime)</h2>
    <p>Untuk pemeliharaan keamanan berkala tanpa membuat website klien mati:</p>
    <ul>
        <li><strong>Mode Overlap (Disarankan)</strong>: Secret Key baru langsung aktif, sedangkan Secret Key lama tetap diberikan masa aktif selama <strong>24 jam</strong> agar pihak developer klien memiliki waktu mengganti konfigurasi di server mereka tanpa downtime.</li>
        <li><strong>Mode Immediate</strong>: Kunci lama seketika dinonaktifkan dan hanya kunci baru yang berlaku.</li>
    </ul>

    <h2>7.4. Ringkasan Endpoint API External (HMAC-SHA256)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Metode</th>
                <th style="width: 40%;">Endpoint URL</th>
                <th>Kegunaan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>GET</code></td>
                <td><code>/api/v1/health</code></td>
                <td>Pengecekan status server & database</td>
            </tr>
            <tr>
                <td><code>GET</code></td>
                <td><code>/api/v1/ping</code></td>
                <td>Uji koneksi & validasi signature HMAC</td>
            </tr>
            <tr>
                <td><code>GET</code></td>
                <td><code>/api/v1/balance</code></td>
                <td>Mengecek sisa saldo klien yang tersedia</td>
            </tr>
            <tr>
                <td><code>POST</code></td>
                <td><code>/api/v1/balance/deduct</code></td>
                <td>Pemotongan saldo otomatis saat transaksi</td>
            </tr>
            <tr>
                <td><code>POST</code></td>
                <td><code>/api/v1/balance/refund</code></td>
                <td>Pengembalian saldo jika transaksi dibatalkan</td>
            </tr>
            <tr>
                <td><code>GET / POST</code></td>
                <td><code>/api/v1/history</code></td>
                <td>Pencatatan riwayat transaksi finansial</td>
            </tr>
            <tr>
                <td><code>POST</code></td>
                <td><code>/api/v1/xendit/webhook/invoice</code></td>
                <td>Callback pelunasan pembayaran otomatis Xendit</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h1>8. Matriks Hak Akses Pengguna (Permissions Guide)</h1>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Kelompok Modul</th>
                <th style="width: 30%;">Nama Izin (Permission)</th>
                <th>Fungsi & Hak Akses</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="4"><strong>Level & Otorisasi</strong></td>
                <td><code>lihat level</code></td>
                <td>Membuka halaman daftar tingkatan role.</td>
            </tr>
            <tr>
                <td><code>tambah level</code></td>
                <td>Membuat role tingkatan baru.</td>
            </tr>
            <tr>
                <td><code>edit level</code></td>
                <td>Mengubah nama role & mengatur matriks izin fitur.</td>
            </tr>
            <tr>
                <td><code>hapus level</code></td>
                <td>Menghapus role tingkatan.</td>
            </tr>

            <tr>
                <td rowspan="4"><strong>Manajemen Pengguna</strong></td>
                <td><code>lihat user</code></td>
                <td>Membuka daftar akun staf pengguna.</td>
            </tr>
            <tr>
                <td><code>buat user</code></td>
                <td>Mendaftarkan akun pengguna baru.</td>
            </tr>
            <tr>
                <td><code>ubah user</code></td>
                <td>Memperbarui profil staf & melakukan reset password.</td>
            </tr>
            <tr>
                <td><code>hapus user</code></td>
                <td>Menghapus akun staf pengguna.</td>
            </tr>

            <tr>
                <td rowspan="4"><strong>Finance - Pemasukan</strong></td>
                <td><code>lihat pemasukan</code></td>
                <td>Melihat tabel transaksi dan grafik pemasukan.</td>
            </tr>
            <tr>
                <td><code>tambah pemasukan</code></td>
                <td>Mencatat transaksi uang masuk baru.</td>
            </tr>
            <tr>
                <td><code>edit pemasukan</code></td>
                <td>Mengubah data transaksi pemasukan yang ada.</td>
            </tr>
            <tr>
                <td><code>hapus pemasukan</code></td>
                <td>Menghapus catatan transaksi pemasukan.</td>
            </tr>

            <tr>
                <td rowspan="4"><strong>Finance - Pengeluaran</strong></td>
                <td><code>lihat pengeluaran</code></td>
                <td>Melihat tabel laporan dana keluar.</td>
            </tr>
            <tr>
                <td><code>tambah pengeluaran</code></td>
                <td>Mencatat transaksi pengeluaran & biaya admin.</td>
            </tr>
            <tr>
                <td><code>edit pengeluaran</code></td>
                <td>Mengubah data transaksi pengeluaran.</td>
            </tr>
            <tr>
                <td><code>hapus pengeluaran</code></td>
                <td>Menghapus catatan transaksi pengeluaran.</td>
            </tr>

            <tr>
                <td rowspan="4"><strong>Finance - Kategori</strong></td>
                <td><code>lihat kategori keuangan</code></td>
                <td>Melihat daftar master kategori.</td>
            </tr>
            <tr>
                <td><code>tambah kategori keuangan</code></td>
                <td>Membuat pos kategori transaksi baru.</td>
            </tr>
            <tr>
                <td><code>edit kategori keuangan</code></td>
                <td>Mengubah nama pos dan keterangan kategori.</td>
            </tr>
            <tr>
                <td><code>hapus kategori keuangan</code></td>
                <td>Menghapus kategori yang belum pernah dipakai.</td>
            </tr>

            <tr>
                <td rowspan="4"><strong>Pengaturan Saldo</strong></td>
                <td><code>lihat saldo</code></td>
                <td>Melihat daftar saldo website klien & mutasi.</td>
            </tr>
            <tr>
                <td><code>buat saldo</code></td>
                <td>Melakukan penyesuaian saldo & top-up Xendit.</td>
            </tr>
            <tr>
                <td><code>edit saldo</code></td>
                <td>Mengubah saklar ON/OFF jalur saldo klien.</td>
            </tr>
            <tr>
                <td><code>hapus saldo</code></td>
                <td>Otorisasi pembatalan riwayat saldo.</td>
            </tr>

            <tr>
                <td><strong>API Management</strong></td>
                <td><code>Api Management</code></td>
                <td>Akses penuh manajemen klien API & rotasi kunci.</td>
            </tr>

            <tr>
                <td rowspan="3"><strong>Laporan & Riwayat</strong></td>
                <td><code>log history</code></td>
                <td>Membuka linimasa audit aktivitas pengguna.</td>
            </tr>
            <tr>
                <td><code>download PDF</code></td>
                <td>Mengunduh laporan dalam format berkas PDF.</td>
            </tr>
            <tr>
                <td><code>download Excel</code></td>
                <td>Mengunduh data pembukuan ke lembar Microsoft Excel.</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h1>9. Pertanyaan Umum & Solusi Masalah (FAQ)</h1>

    <div class="callout callout-info">
        <div class="callout-title">Q1: Mengapa saldo klien tidak bertambah setelah klien melakukan transfer via Xendit?</div>
        <p>
            <strong>Solusi:</strong> Pastikan webhook Xendit telah terkonfigurasi dengan URL callback <code>/api/v1/xendit/webhook/invoice</code> dan status invoice di dashboard Xendit sudah <em>COMPLETED / PAID</em>. Anda juga dapat mengklik tombol <strong>"Perbarui Saldo Xendit"</strong> di Dashboard untuk menyegarkan cache data.
        </p>
    </div>

    <div class="callout callout-warning">
        <div class="callout-title">Q2: Mengapa tombol "Top-Up Xendit" tidak bisa digunakan pada klien tertentu?</div>
        <p>
            <strong>Solusi:</strong> Periksa kolom status saklar <strong>Saldo Xendit</strong> pada halaman <em>Pengaturan Saldo</em>. Jika status saklarnya berwarna abu-abu (<em>Xendit OFF</em>), aktifkan saklar menjadi <strong>ON</strong> terlebih dahulu.
        </p>
    </div>

    <div class="callout callout-info">
        <div class="callout-title">Q3: Apakah data transaksi yang salah input nominal bisa diperbaiki?</div>
        <p>
            <strong>Solusi:</strong> Ya. Pengguna dengan izin <code>edit pemasukan</code> atau <code>edit pengeluaran</code> dapat mengklik tombol <strong>Edit</strong> (ikon pensil) pada baris transaksi terkait, memperbaiki nominal atau keterangan, lalu menyimpan perubahan.
        </p>
    </div>

    <div class="callout callout-danger">
        <div class="callout-title">Q4: Mengapa sistem menolak saat saya ingin menghapus sebuah kategori keuangan?</div>
        <p>
            <strong>Solusi:</strong> Kategori yang sudah memiliki keterkaitan dengan transaksi tidak boleh dihapus demi menjaga keutuhan laporan keuangan historis. Sebagai gantinya, ubah status kategori menjadi tidak aktif (*is_active = false*).
        </p>
    </div>

    <div class="callout callout-success">
        <div class="callout-title">Q5: Bagaimana jika Secret Key API klien hilang atau belum sempat dicatat?</div>
        <p>
            <strong>Solusi:</strong> Buka menu <strong>API Management</strong> &rarr; pilih klien yang bersangkutan &rarr; klik tombol <strong>Rotate Secret</strong> dengan memilih mode <strong>Overlap (24 Jam)</strong>. Sistem akan membuat Secret Key baru yang aman tanpa memutuskan koneksi yang sedang berjalan.
        </p>
    </div>

    <div style="margin-top: 30px; padding: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; font-size: 8.5pt; color: #64748b;">
        <strong>CIO Finance Management System</strong> &bull; Versi 1.0<br>
        Dokumentasi panduan ini dibuat secara otomatis untuk menjamin standar operasional perusahaan yang akurat dan terpercaya.
    </div>

</body>
</html>
