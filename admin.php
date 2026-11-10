<?php
session_start();

// Panggil file konfigurasi dan fungsi
include 'config.php';

// Proses Masuk dengan Kunci Dekripsi
if (isset($_POST['masuk_dengan_kunci'])) {
    $_SESSION['admin_symmetric_key'] = trim($_POST['secret_key']);
}

// Proses Keluar / Ganti Kunci
if (isset($_GET['aksi']) && $_GET['aksi'] === 'keluar') {
    unset($_SESSION['admin_symmetric_key']);
    header("Location: admin.php");
    exit;
}

$active_key = isset($_SESSION['admin_symmetric_key']) ? $_SESSION['admin_symmetric_key'] : null;
$pesan_update = "";

// Proses Simpan Catatan Konselor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_konseling']) && $active_key) {
    $id_laporan   = intval($_POST['id_laporan']);
    $catatan_baru = enkripsiData(trim($_POST['catatan_konselor']), $active_key);
    $status_baru  = enkripsiData(trim($_POST['status_penanganan']), $active_key);

    $stmt = mysqli_prepare($conn, "UPDATE pengaduan_konseling SET catatan_konselor = ?, status_penanganan = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $catatan_baru, $status_baru, $id_laporan);
    if (mysqli_stmt_execute($stmt)) {
        $pesan_update = "Catatan tindak lanjut berhasil disimpan dan dienkripsi kembali.";
    }
}

// Ambil dan Dekripsi Data Laporan
$daftar_laporan = [];
$kunci_valid = true;

if ($active_key !== null) {
    $query = mysqli_query($conn, "SELECT * FROM pengaduan_konseling ORDER BY id DESC");
    while ($row = mysqli_fetch_assoc($query)) {
        $dek_nama     = dekripsiData($row['nama_mahasiswa'], $active_key);
        $dek_nim      = dekripsiData($row['nim'], $active_key);
        $dek_kontak   = dekripsiData($row['kontak'], $active_key);
        $dek_kategori = dekripsiData($row['kategori'], $active_key);
        $dek_keluhan  = dekripsiData($row['isi_keluhan'], $active_key);
        $dek_catatan  = dekripsiData($row['catatan_konselor'], $active_key);
        $dek_status   = dekripsiData($row['status_penanganan'], $active_key);

        if (!$dek_nama['berhasil']) {
            $kunci_valid = false;
        }

        $daftar_laporan[] = [
            'id'       => $row['id'],
            'tiket'    => $row['kode_tiket'],
            'tanggal'  => date("d M Y, H:i", strtotime($row['tanggal_lapor'])),
            'nama'     => $dek_nama['hasil'],
            'nim'      => $dek_nim['hasil'],
            'kontak'   => $dek_kontak['hasil'],
            'kategori' => $dek_kategori['hasil'],
            'keluhan'  => $dek_keluhan['hasil'],
            'catatan'  => $dek_catatan['hasil'],
            'status'   => $dek_status['hasil'],
            'terbuka'  => $dek_nama['berhasil']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Petugas Konseling | Sistem Informasi Kemahasiswaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-muhammadiyah { background-color: #005596; }
        .bg-muhammadiyah-dark { background-color: #003B6D; }
        .text-muhammadiyah { color: #005596; }
    </style>
</head>
<body class="bg-[#F4F7FA] text-slate-700 min-h-screen flex flex-col">

<?php if ($active_key === null): ?>
    <!-- HALAMAN INPUT KUNCI DEKRIPSI PETUGAS -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-muhammadiyah p-6 text-white border-b-4 border-amber-400">
                <div class="flex items-center space-x-2.5 mb-2">
                    <div class="w-8 h-8 rounded bg-white/15 flex items-center justify-center font-bold text-sm">BK</div>
                    <span class="text-xs font-medium text-blue-100">Sistem Informasi Kemahasiswaan</span>
                </div>
                <h1 class="text-xl font-bold">Otorisasi Kunci Konselor</h1>
                <p class="text-xs text-blue-100 mt-1">Masukkan kunci rahasia untuk membuka dan mendekripsi data pengaduan mahasiswa.</p>
            </div>

            <form method="POST" action="" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kunci Dekripsi (Secret Key)</label>
                    <input type="password" name="secret_key" required autofocus placeholder="Masukkan kunci akses..." class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:border-sky-700 focus:ring-2 focus:ring-sky-700/15 focus:outline-none transition">
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Sistem menggunakan enkripsi simetris. Apabila kunci yang dimasukkan tidak sesuai, data di dalam halaman admin tetap akan ditampilkan dalam keadaan terenkripsi.
                    </p>
                </div>

                <button type="submit" name="masuk_dengan_kunci" class="w-full bg-muhammadiyah hover:bg-[#00447a] text-white font-semibold py-2.5 rounded-lg text-sm transition shadow-sm">
                    Buka Dashboard Admin
                </button>

                <div class="text-center pt-2 border-t border-slate-100">
                    <a href="index.php" class="text-xs text-slate-500 hover:text-muhammadiyah transition">&larr; Kembali ke Halaman Pengaduan Mahasiswa</a>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- NAVBAR DASHBOARD ADMIN -->
    <header class="bg-muhammadiyah text-white border-b-4 border-amber-400">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center font-bold text-base">BK</div>
                <div>
                    <h1 class="font-bold text-base leading-tight">Dashboard Konselor & Pengelola Pengaduan</h1>
                    <p class="text-xs text-blue-100">Status Sesi: <?php echo $kunci_valid ? 'Data Terdekripsi (Terbuka)' : 'Kunci Tidak Sesuai (Data Terenkripsi)'; ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-2.5">
                <a href="index.php" class="bg-white/10 hover:bg-white/20 text-white px-3.5 py-2 rounded-lg text-xs font-medium transition">
                    Halaman Depan
                </a>
                <a href="admin.php?aksi=keluar" class="bg-amber-400 hover:bg-amber-500 text-slate-900 px-3.5 py-2 rounded-lg text-xs font-semibold transition">
                    Ganti Kunci / Keluar
                </a>
            </div>
        </div>
    </header>

    <!-- ISI DASHBOARD ADMIN -->
    <main class="max-w-7xl mx-auto px-4 py-7 flex-grow w-full">

        <?php if (!$kunci_valid): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3.5 rounded-xl mb-6 flex items-center justify-between flex-wrap gap-3 text-xs">
                <div>
                    <strong class="font-semibold block text-sm">Kunci Dekripsi Tidak Sesuai</strong>
                    <span>Kunci yang Anda masukkan salah. Seluruh informasi mahasiswa dan isi laporan di bawah ini tetap terkunci dalam format enkripsi database.</span>
                </div>
                <a href="admin.php?aksi=keluar" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg font-medium shrink-0">
                    Masukkan Ulang Kunci
                </a>
            </div>
        <?php endif; ?>

        <?php if ($pesan_update): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl mb-6 text-xs font-medium">
                <?php echo htmlspecialchars($pesan_update); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-slate-800 text-base">Daftar Laporan Masuk</h2>
                    <p class="text-xs text-slate-500">Menampilkan <?php echo count($daftar_laporan); ?> data pengaduan dan konseling mahasiswa</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold">
                            <th class="py-3.5 px-4">No. Tiket</th>
                            <th class="py-3.5 px-4">Identitas Mahasiswa</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Uraian Pengaduan</th>
                            <th class="py-3.5 px-4">Tindak Lanjut Konselor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 text-xs">
                        <?php foreach ($daftar_laporan as $item): ?>
                            <tr class="hover:bg-slate-50/70 align-top transition">
                                <!-- Tiket -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="font-semibold text-muhammadiyah block"><?php echo htmlspecialchars($item['tiket']); ?></span>
                                    <span class="text-slate-400 text-[11px]"><?php echo htmlspecialchars($item['tanggal']); ?></span>
                                </td>

                                <!-- Identitas Mahasiswa -->
                                <td class="py-4 px-4 max-w-[220px]">
                                    <?php if ($item['terbuka']): ?>
                                        <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($item['nama']); ?></p>
                                        <p class="text-slate-500 mt-0.5">NIM: <?php echo htmlspecialchars($item['nim']); ?></p>
                                        <p class="text-slate-500">Kontak: <?php echo htmlspecialchars($item['kontak']); ?></p>
                                    <?php else: ?>
                                        <div class="font-mono text-[11px] text-slate-500 break-all space-y-1">
                                            <div><?php echo htmlspecialchars($item['nama']); ?></div>
                                            <div class="text-slate-400"><?php echo htmlspecialchars($item['nim']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Kategori -->
                                <td class="py-4 px-4 max-w-[180px]">
                                    <?php if ($item['terbuka']): ?>
                                        <span class="bg-blue-50 text-muhammadiyah border border-blue-100 font-medium px-2.5 py-1 rounded-md inline-block">
                                            <?php echo htmlspecialchars($item['kategori']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="font-mono text-[11px] text-slate-500 break-all block">
                                            <?php echo htmlspecialchars($item['kategori']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Isi Keluhan -->
                                <td class="py-4 px-4 max-w-sm">
                                    <?php if ($item['terbuka']): ?>
                                        <p class="text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($item['keluhan'])); ?></p>
                                    <?php else: ?>
                                        <span class="font-mono text-[11px] text-slate-500 break-all block">
                                            <?php echo htmlspecialchars($item['keluhan']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Tindak Lanjut & Status -->
                                <td class="py-4 px-4 min-w-[260px]">
                                    <?php if ($item['terbuka']): ?>
                                        <form method="POST" action="" class="space-y-2.5">
                                            <input type="hidden" name="id_laporan" value="<?php echo $item['id']; ?>">
                                            <div>
                                                <select name="status_penanganan" class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs bg-white focus:border-sky-700 focus:outline-none">
                                                    <option value="Menunggu Verifikasi" <?php echo ($item['status'] === 'Menunggu Verifikasi') ? 'selected' : ''; ?>>Status: Menunggu Verifikasi</option>
                                                    <option value="Sedang Diproses" <?php echo ($item['status'] === 'Sedang Diproses') ? 'selected' : ''; ?>>Status: Sedang Diproses</option>
                                                    <option value="Selesai" <?php echo ($item['status'] === 'Selesai') ? 'selected' : ''; ?>>Status: Selesai Ditangani</option>
                                                </select>
                                            </div>
                                            <div>
                                                <textarea name="catatan_konselor" rows="2" placeholder="Tulis tanggapan atau jadwal konseling..." class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs focus:border-sky-700 focus:outline-none"><?php echo htmlspecialchars($item['catatan']); ?></textarea>
                                            </div>
                                            <button type="submit" name="update_konseling" class="bg-muhammadiyah hover:bg-[#00447a] text-white font-medium px-3.5 py-1.5 rounded-lg text-xs transition">
                                                Simpan Tanggapan
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="font-mono text-[11px] text-slate-500 break-all space-y-1">
                                            <div><?php echo htmlspecialchars($item['status']); ?></div>
                                            <div class="text-slate-400"><?php echo htmlspecialchars($item['catatan']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php endif; ?>

</body>
</html>