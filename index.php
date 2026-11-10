<?php
// Panggil file konfigurasi dan fungsi
include 'config.php';

// Data awal otomatis jika tabel masih kosong
$cek_isi = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pengaduan_konseling");
$row_isi = mysqli_fetch_assoc($cek_isi);
if ($row_isi['total'] == 0) {
    $sample_data = [
        [
            'tiket' => 'KSL-2026-001',
            'nama' => 'Rizky Pratama',
            'nim' => '2430511012',
            'kontak' => '081288990011',
            'kategori' => 'Bimbingan & Konseling Akademik',
            'keluhan' => 'Mohon jadwal konsultasi terkait pengambilan mata kuliah semester depan karena ada jadwal praktikum yang bentrok.',
            'catatan' => 'Jadwal konsultasi telah diatur pada hari Kamis pukul 10.00 WIB di ruang kemahasiswaan.',
            'status' => 'Sedang Diproses'
        ],
        [
            'tiket' => 'KSL-2026-002',
            'nama' => 'Nadia Putri',
            'nim' => '2430511045',
            'kontak' => '085711223344',
            'kategori' => 'Keluhan Sarana & Fasilitas Perkuliahan',
            'keluhan' => 'Proyektor di ruang kelas lantai 3 gedung utama sering mati sendiri saat presentasi berlangsung.',
            'catatan' => 'Sudah ditindaklanjuti bersama teknisi sarana prasarana dan unit proyektor telah diganti.',
            'status' => 'Selesai'
        ]
    ];

    $stmt_seed = mysqli_prepare($conn, "INSERT INTO pengaduan_konseling (kode_tiket, nama_mahasiswa, nim, kontak, kategori, isi_keluhan, catatan_konselor, status_penanganan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sample_data as $item) {
        $n   = enkripsiData($item['nama'], SYSTEM_SYMMETRIC_KEY);
        $nim = enkripsiData($item['nim'], SYSTEM_SYMMETRIC_KEY);
        $k   = enkripsiData($item['kontak'], SYSTEM_SYMMETRIC_KEY);
        $kat = enkripsiData($item['kategori'], SYSTEM_SYMMETRIC_KEY);
        $kel = enkripsiData($item['keluhan'], SYSTEM_SYMMETRIC_KEY);
        $cat = enkripsiData($item['catatan'], SYSTEM_SYMMETRIC_KEY);
        $st  = enkripsiData($item['status'], SYSTEM_SYMMETRIC_KEY);
        mysqli_stmt_bind_param($stmt_seed, "ssssssss", $item['tiket'], $n, $nim, $k, $kat, $kel, $cat, $st);
        mysqli_stmt_execute($stmt_seed);
    }
}

$nomor_tiket_baru = "";

// Proses saat form dikirim oleh mahasiswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pengaduan'])) {
    $kode_tiket = "KSL-" . date("Y") . "-" . strtoupper(substr(md5(uniqid()), 0, 4));
    
    $nama_enc     = enkripsiData(trim($_POST['nama']), SYSTEM_SYMMETRIC_KEY);
    $nim_enc      = enkripsiData(trim($_POST['nim']), SYSTEM_SYMMETRIC_KEY);
    $kontak_enc   = enkripsiData(trim($_POST['kontak']), SYSTEM_SYMMETRIC_KEY);
    $kategori_enc = enkripsiData(trim($_POST['kategori']), SYSTEM_SYMMETRIC_KEY);
    $keluhan_enc  = enkripsiData(trim($_POST['keluhan']), SYSTEM_SYMMETRIC_KEY);
    $catatan_enc  = enkripsiData("Laporan telah diterima dan sedang menunggu peninjauan dari konselor akademik.", SYSTEM_SYMMETRIC_KEY);
    $status_enc   = enkripsiData("Menunggu Verifikasi", SYSTEM_SYMMETRIC_KEY);

    $stmt = mysqli_prepare($conn, "INSERT INTO pengaduan_konseling (kode_tiket, nama_mahasiswa, nim, kontak, kategori, isi_keluhan, catatan_konselor, status_penanganan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssssss", $kode_tiket, $nama_enc, $nim_enc, $kontak_enc, $kategori_enc, $keluhan_enc, $catatan_enc, $status_enc);
    
    if (mysqli_stmt_execute($stmt)) {
        $nomor_tiket_baru = $kode_tiket;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Konseling & Pengaduan Akademik Mahasiswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-muhammadiyah { background-color: #005596; }
        .bg-muhammadiyah-dark { background-color: #003B6D; }
        .text-muhammadiyah { color: #005596; }
        .border-muhammadiyah { border-color: #005596; }
    </style>
</head>
<body class="bg-[#F4F7FA] text-slate-700 min-h-screen flex flex-col">

    <!-- Top Bar Identitas Kampus -->
    <div class="bg-muhammadiyah-dark text-white/90 text-xs py-2 px-4">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <span>Unit Layanan Bimbingan Konseling & Kemahasiswaan</span>
            <span class="hidden sm:inline">Senin - Jumat, 08.00 - 15.30 WIB</span>
        </div>
    </div>

    <!-- Navbar Utama -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-muhammadiyah flex items-center justify-center text-white font-bold text-lg shadow-sm">
                    BK
                </div>
                <div>
                    <h1 class="font-bold text-slate-800 text-base leading-tight">Portal Konseling & Pengaduan</h1>
                    <p class="text-xs text-slate-500">Layanan Pendampingan Akademik Mahasiswa</p>
                </div>
            </div>
            <a href="admin.php" class="text-xs font-semibold text-muhammadiyah hover:bg-blue-50 border border-blue-200 px-3.5 py-2 rounded-lg transition">
                Login Petugas / Konselor
            </a>
        </div>
    </nav>

    <!-- Banner Sambutan Singkat -->
    <section class="bg-muhammadiyah text-white py-10 border-b-4 border-amber-400">
        <div class="max-w-6xl mx-auto px-4">
            <div class="max-w-2xl">
                <h2 class="text-2xl md:text-3xl font-bold leading-snug">Ruang Aman Penyampaian Aspirasi, Pengaduan, dan Konseling Mahasiswa</h2>
                <p class="mt-2.5 text-blue-100 text-sm leading-relaxed">
                    Sampaikan kendala perkuliahan, permohonan bimbingan akademik, maupun keluhan fasilitas kampus melalui formulir di bawah ini. Setiap data yang Anda kirimkan dijamin kerahasiaannya dan hanya dapat diakses oleh konselor berwenang.
                </p>
            </div>
        </div>
    </section>

    <!-- Konten Utama -->
    <main class="max-w-6xl mx-auto px-4 py-8 flex-grow w-full">
        <div class="grid md:grid-cols-12 gap-8 items-start">

            <!-- Kolom Kiri: Formulir Pengaduan (8 Kolom) -->
            <div class="md:col-span-8 bg-white rounded-xl shadow-sm border border-slate-200/80 p-6 md:p-8">
                <div class="border-b border-slate-100 pb-4 mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Formulir Pengajuan Konseling & Pengaduan</h3>
                    <p class="text-xs text-slate-500 mt-1">Isi data diri Anda dengan benar agar tim kami dapat menghubungi Anda kembali.</p>
                </div>

                <?php if ($nomor_tiket_baru): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-lg p-4 mb-6 text-sm">
                        <p class="font-semibold">Laporan Anda berhasil dikirim!</p>
                        <p class="text-xs text-emerald-700 mt-1">
                            Nomor Tiket Laporan: <strong class="font-mono bg-white px-2 py-0.5 rounded border border-emerald-200"><?php echo htmlspecialchars($nomor_tiket_baru); ?></strong>. Data Anda telah diamankan di dalam sistem dan akan segera ditinjau oleh petugas.
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap</label>
                            <input type="text" name="nama" required placeholder="Masukkan nama lengkap Anda" class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:border-sky-700 focus:ring-2 focus:ring-sky-700/15 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nomor Induk Mahasiswa (NIM)</label>
                            <input type="text" name="nim" required placeholder="Contoh: 2430511057" class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:border-sky-700 focus:ring-2 focus:ring-sky-700/15 focus:outline-none transition">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nomor WhatsApp / Email Aktif</label>
                            <input type="text" name="kontak" required placeholder="0812xxxxxxxx atau email" class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:border-sky-700 focus:ring-2 focus:ring-sky-700/15 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Kategori Layanan</label>
                            <select name="kategori" required class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm bg-white focus:border-sky-700 focus:ring-2 focus:ring-sky-700/15 focus:outline-none transition">
                                <option value="Bimbingan & Konseling Akademik">Bimbingan & Konseling Akademik</option>
                                <option value="Konsultasi KRS & Perkuliahan">Konsultasi KRS & Perkuliahan</option>
                                <option value="Konseling Pribadi / Beban Studi">Konseling Pribadi / Beban Studi</option>
                                <option value="Keluhan Sarana & Fasilitas Perkuliahan">Keluhan Sarana & Fasilitas Perkuliahan</option>
                                <option value="Pengaduan Layanan & Etika Kampus">Pengaduan Layanan & Etika Kampus</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Uraian Permasalahan atau Pengaduan</label>
                        <textarea name="keluhan" rows="4" required placeholder="Tuliskan secara jelas kendala yang Anda alami atau permohonan jadwal konseling yang diinginkan..." class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:border-sky-700 focus:ring-2 focus:ring-sky-700/15 focus:outline-none transition"></textarea>
                    </div>

                    <div class="pt-2 flex items-center justify-between flex-wrap gap-4">
                        <p class="text-xs text-slate-500">
                            🔒 Data otomatis dienkripsi sebelum disimpan ke server.
                        </p>
                        <button type="submit" name="kirim_pengaduan" class="bg-muhammadiyah hover:bg-[#00447a] text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition shadow-sm">
                            Kirim Laporan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Kolom Kanan: Informasi Pendukung (4 Kolom) -->
            <div class="md:col-span-4 space-y-5">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                    <h4 class="font-bold text-slate-800 text-sm mb-3">Alur Tindak Lanjut</h4>
                    <ol class="space-y-3.5 text-xs text-slate-600">
                        <li class="flex items-start space-x-2.5">
                            <span class="w-5 h-5 rounded-full bg-blue-50 text-muhammadiyah font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>
                            <span><strong>Pengisian Formulir:</strong> Mahasiswa menyampaikan keluhan atau permohonan jadwal konseling melalui halaman ini.</span>
                        </li>
                        <li class="flex items-start space-x-2.5">
                            <span class="w-5 h-5 rounded-full bg-blue-50 text-muhammadiyah font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>
                            <span><strong>Verifikasi Konselor:</strong> Tim kemahasiswaan membuka laporan menggunakan kunci akses khusus dan meninjau permasalahan.</span>
                        </li>
                        <li class="flex items-start space-x-2.5">
                            <span class="w-5 h-5 rounded-full bg-blue-50 text-muhammadiyah font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>
                            <span><strong>Pendampingan / Solusi:</strong> Petugas menghubungi kontak mahasiswa untuk penjadwalan sesi konseling atau penyelesaian aduan.</span>
                        </li>
                    </ol>
                </div>

                <div class="bg-blue-50/70 rounded-xl border border-blue-100 p-5">
                    <h4 class="font-bold text-muhammadiyah text-sm mb-1.5">Jaminan Privasi Mahasiswa</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Untuk menjaga kerahasiaan cerita dan identitas Anda, sistem ini menerapkan pengamanan kriptografi pada database. Isi laporan tidak dapat dibaca oleh pihak luar maupun pengelola jaringan tanpa otorisasi konselor.
                    </p>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 text-slate-500 text-xs py-5 mt-12">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-2">
            <span>&copy; <?php echo date("Y"); ?> Unit Layanan Konseling & Pengaduan Akademik.</span>
            <span>Sistem Informasi Kemahasiswaan</span>
        </div>
    </footer>

</body>
</html>