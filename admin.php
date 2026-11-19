<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Helper: normalisasi nama file menjadi .JPG dan cari path yang tersedia
function resolveFotoToko(?string $fotoRaw): string {
    $clean = trim((string)$fotoRaw);
    if ($clean !== '' && preg_match('#^https?://#i', $clean)) {
        return $clean;
    }

    $candidates = [];
    if ($clean !== '') {
        $base = pathinfo($clean, PATHINFO_FILENAME);
        $ext  = pathinfo($clean, PATHINFO_EXTENSION);
        $candidates[] = $clean;
        if ($base !== '') {
            $candidates[] = $base . '.JPG';
            $candidates[] = $base . '.jpg';
            if ($ext !== '') {
                $candidates[] = $base . '.' . strtoupper($ext);
                $candidates[] = $base . '.' . strtolower($ext);
            }
        }
    }

    foreach (array_unique($candidates) as $nama) {
        if (file_exists('assets/img/' . $nama)) {
            return 'assets/img/' . $nama;
        }
        if (file_exists('assets/' . $nama)) {
            return 'assets/' . $nama;
        }
    }

    return 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=300&q=80';
}

// 1. TAMBAH DATA BARU
if (isset($_POST['simpan_baru'])) {
    $kode      = mysqli_real_escape_string($conn, $_POST['kode_titik']);
    $nama      = mysqli_real_escape_string($conn, $_POST['nama_toko']);
    $alamat    = mysqli_real_escape_string($conn, $_POST['alamat_lengkap']);
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $lat       = (float)$_POST['latitude'];
    $lng       = (float)$_POST['longitude'];
    $kategori  = mysqli_real_escape_string($conn, $_POST['kategori_usaha']);
    $layanan   = mysqli_real_escape_string($conn, $_POST['metode_layanan']);
    $harga     = mysqli_real_escape_string($conn, $_POST['kisaran_harga']);
    $jam       = mysqli_real_escape_string($conn, $_POST['jam_buka']);
    $hari      = mysqli_real_escape_string($conn, $_POST['hari_buka']);
    $best      = mysqli_real_escape_string($conn, $_POST['best_seller']);
    $bayar     = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas']);
    $kontak    = mysqli_real_escape_string($conn, $_POST['kontak_sosmed']);

    $nama_foto = 'placeholder.JPG';
    if (!empty($_FILES['foto_tempat']['name']) && $_FILES['foto_tempat']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir('assets/img')) mkdir('assets/img', 0777, true);
        $nama_foto = 'toko_' . time() . '.JPG';
        move_uploaded_file($_FILES['foto_tempat']['tmp_name'], 'assets/img/' . $nama_foto);
    }

    mysqli_query($conn, "INSERT INTO toko_bakery 
        (kode_titik, nama_toko, alamat_lengkap, kecamatan, latitude, longitude, kategori_usaha, metode_layanan, kisaran_harga, jam_buka, hari_buka, best_seller, metode_pembayaran, fasilitas, kontak_sosmed, foto_tempat)
        VALUES ('$kode', '$nama', '$alamat', '$kecamatan', '$lat', '$lng', '$kategori', '$layanan', '$harga', '$jam', '$hari', '$best', '$bayar', '$fasilitas', '$kontak', '$nama_foto')");
    header("Location: admin.php?pesan=ditambah");
    exit;
}

// 2. UPDATE DATA
if (isset($_POST['update_data'])) {
    $id        = (int)$_POST['id_toko'];
    $kode      = mysqli_real_escape_string($conn, $_POST['kode_titik']);
    $nama      = mysqli_real_escape_string($conn, $_POST['nama_toko']);
    $alamat    = mysqli_real_escape_string($conn, $_POST['alamat_lengkap']);
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $lat       = (float)$_POST['latitude'];
    $lng       = (float)$_POST['longitude'];
    $kategori  = mysqli_real_escape_string($conn, $_POST['kategori_usaha']);
    $layanan   = mysqli_real_escape_string($conn, $_POST['metode_layanan']);
    $harga     = mysqli_real_escape_string($conn, $_POST['kisaran_harga']);
    $jam       = mysqli_real_escape_string($conn, $_POST['jam_buka']);
    $hari      = mysqli_real_escape_string($conn, $_POST['hari_buka']);
    $best      = mysqli_real_escape_string($conn, $_POST['best_seller']);
    $bayar     = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas']);
    $kontak    = mysqli_real_escape_string($conn, $_POST['kontak_sosmed']);

    $sqlFoto = "";
    if (!empty($_FILES['foto_tempat']['name']) && $_FILES['foto_tempat']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir('assets/img')) mkdir('assets/img', 0777, true);
        $nama_foto = 'toko_' . time() . '.JPG';
        move_uploaded_file($_FILES['foto_tempat']['tmp_name'], 'assets/img/' . $nama_foto);
        $sqlFoto = ", foto_tempat='$nama_foto'";
    }

    mysqli_query($conn, "UPDATE toko_bakery SET 
        kode_titik='$kode', nama_toko='$nama', alamat_lengkap='$alamat', kecamatan='$kecamatan',
        latitude='$lat', longitude='$lng', kategori_usaha='$kategori', metode_layanan='$layanan',
        kisaran_harga='$harga', jam_buka='$jam', hari_buka='$hari',
        best_seller='$best', metode_pembayaran='$bayar', fasilitas='$fasilitas', kontak_sosmed='$kontak' $sqlFoto
        WHERE id_toko=$id");
    header("Location: admin.php?pesan=diperbarui");
    exit;
}

// 3. HAPUS DATA
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM toko_bakery WHERE id_toko=$id");
    header("Location: admin.php?pesan=dihapus");
    exit;
}

$dataToko   = mysqli_query($conn, "SELECT * FROM toko_bakery ORDER BY id_toko ASC");
$totalTitik = mysqli_num_rows($dataToko);

$qUsers     = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='user'");
$totalUser  = mysqli_fetch_assoc($qUsers)['total'];

$pesanRaw = $_GET['pesan'] ?? '';
$isErrorPesan = in_array($pesanRaw, ['gagal_upload_spasial', 'format_geojson_tidak_valid'], true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — BAKES Kota Palu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            linen: '#F5F1EA',
            khaki: '#D7C9B8',
            camel: '#B2967D',
            cocoa: '#7D5A44',
            espresso: '#4A342A',
          }
        }
      }
    }
  </script>
</head>
<body class="bg-linen text-espresso min-h-screen flex flex-col font-sans text-lg">

  <!-- NAVBAR ATAS -->
  <header class="bg-gradient-to-br from-cocoa via-espresso to-[#5C4033] text-linen px-8 md:px-12 py-7 flex flex-wrap items-center justify-between gap-6 shadow-md border-b border-camel/40 sticky top-0 z-20">
    <div class="flex items-center gap-4">
      <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-camel/65 via-cocoa/55 to-transparent flex items-center justify-center overflow-hidden drop-shadow-md">
        <img src="assets/logo_icon.png" alt="Logo Icon" class="w-14 h-14 object-contain">
      </div>
      <div>
        <h1 class="font-bold text-xl md:text-2xl leading-tight">BAKES WebGIS <span class="text-khaki">Kota Palu</span></h1>
        <p class="text-sm md:text-base text-khaki font-medium">Bakery & Pastry Geographic Exploration System</p>
      </div>
    </div>

    <div class="flex items-center gap-5">
      <span class="hidden sm:inline-block text-base md:text-lg font-medium text-khaki tracking-wide">
        Admin, <b class="text-linen font-bold text-lg md:text-xl"><?= htmlspecialchars($_SESSION['user_nama']) ?></b>
      </span>
      <a href="index.php?peta=1" class="bg-cocoa hover:bg-camel hover:text-espresso text-linen text-base md:text-lg font-bold px-7 py-4 rounded-2xl transition shadow-md flex items-center gap-2.5">
        Lihat Peta Pengguna
      </a>
      <a href="index.php?logout=1" class="border border-khaki/60 hover:bg-cocoa text-linen text-base md:text-lg font-semibold px-6 py-4 rounded-2xl transition">
        Keluar
      </a>
    </div>
  </header>

  <main class="w-full px-6 md:px-12 py-9 space-y-8 flex-1">

    <?php if ($pesanRaw !== ''): ?>
      <?php if ($isErrorPesan): ?>
        <div class="p-5 bg-red-100 border border-red-400 text-red-900 text-lg font-semibold rounded-2xl flex justify-between items-center shadow-sm">
          <span>Proses data spasial gagal: <b><?= htmlspecialchars(str_replace('_', ' ', $pesanRaw)) ?></b>. Pastikan berkas GeoJSON valid.</span>
          <a href="admin.php" class="underline font-bold ml-4">Tutup</a>
        </div>
      <?php else: ?>
        <div class="p-5 bg-emerald-100 border border-emerald-400 text-emerald-900 text-lg font-semibold rounded-2xl flex justify-between items-center shadow-sm">
          <span>Data titik lokasi bakery berhasil <b><?= htmlspecialchars(str_replace('_', ' ', $pesanRaw)) ?></b> di database MySQL.</span>
          <a href="admin.php" class="underline font-bold ml-4">Tutup</a>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-khaki/55 border border-camel/50 p-7 rounded-2xl shadow-sm flex flex-col justify-between gap-2">
        <p class="text-base md:text-lg font-extrabold uppercase tracking-wider text-cocoa">Total Titik Terintegrasi</p>
        <p class="text-4xl md:text-5xl font-extrabold mt-1"><?= $totalTitik ?> <span class="text-lg md:text-xl font-semibold text-espresso/80">/ 30 Toko</span></p>
        <p class="text-base md:text-lg text-espresso/85 mt-1">Data survei lengkap di MySQL</p>
      </div>

      <div class="bg-khaki/55 border border-camel/50 p-7 rounded-2xl shadow-sm flex flex-col justify-between gap-2">
        <p class="text-base md:text-lg font-extrabold uppercase tracking-wider text-cocoa">Pengguna Terdaftar</p>
        <p class="text-4xl md:text-5xl font-extrabold mt-1"><?= $totalUser ?> <span class="text-lg md:text-xl font-semibold text-espresso/80">Akun Gmail</span></p>
        <p class="text-base md:text-lg text-espresso/85 mt-1">Terdaftar via Sign Up</p>
      </div>

      <div class="bg-gradient-to-br from-cocoa via-espresso to-[#5C4033] text-linen p-7 rounded-2xl shadow-md flex flex-col justify-between border border-camel/40 gap-4">
        <div>
          <p class="text-base md:text-lg font-extrabold uppercase tracking-wider text-camel">Integrasi ArcGIS (.SHP)</p>
          <p class="text-base md:text-lg text-khaki mt-1.5 leading-snug"><?= $totalTitik ?> titik koordinat sinkron & siap ekspor/impor ke SHP.</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
          <a href="sync_shp.php?action=export_geojson"
            class="flex-1 text-center bg-cocoa hover:bg-camel hover:text-espresso text-linen text-sm md:text-base font-bold py-3 px-4 rounded-xl transition shadow-md">
            Unduh GeoJSON (.SHP)
          </a>
          <button type="button" onclick="document.getElementById('modalImportShp').classList.remove('hidden')"
            class="flex-1 bg-linen/15 hover:bg-linen hover:text-espresso border border-khaki/50 text-linen text-sm md:text-base font-bold py-3 px-4 rounded-xl transition">
            Impor SHP/GeoJSON
          </button>
        </div>
      </div>
    </div>

    <!-- Panel Tabel Data -->
    <div class="bg-white/95 border border-camel/50 rounded-3xl shadow-md overflow-hidden">
      <div class="p-6 md:p-8 bg-khaki/45 border-b border-camel/40 flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
          <h2 class="font-bold text-2xl md:text-3xl text-espresso">Daftar Sebaran 30 Titik & Informasi Bakery</h2>
          <p class="text-base md:text-lg text-cocoa font-medium">Kelola koordinat, foto tempat (.JPG), jam operasional, dan atribut toko</p>
        </div>

        <div class="flex flex-wrap items-center gap-3.5 w-full xl:w-auto">
          <input type="text" id="cariAdmin" onkeyup="filterTabelAdmin()" placeholder="Cari nama toko / kecamatan..."
            class="flex-1 sm:w-80 md:w-96 px-5 py-3.5 rounded-xl bg-white border border-camel text-base md:text-lg focus:outline-none focus:ring-2 focus:ring-cocoa">
          <button onclick="bukaModalTambah()"
            class="bg-cocoa hover:bg-espresso text-linen text-base md:text-lg font-bold px-7 py-3.5 rounded-xl shadow-md transition">
            + Tambah Titik Baru
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-base md:text-lg" id="tabelAdmin">
          <thead class="bg-espresso text-linen uppercase text-base md:text-lg tracking-wide">
            <tr>
              <th class="p-5 md:p-6">Foto Tempat</th>
              <th class="p-5 md:p-6">Kode</th>
              <th class="p-5 md:p-6">Nama Toko & Alamat</th>
              <th class="p-5 md:p-6">Kategori & Kecamatan</th>
              <th class="p-5 md:p-6">Koordinat</th>
              <th class="p-5 md:p-6">Operasional & Harga</th>
              <th class="p-5 md:p-6 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-camel/30 text-base md:text-lg">
            <?php while ($row = mysqli_fetch_assoc($dataToko)): ?>
            <?php $fotoSrc = resolveFotoToko($row['foto_tempat'] ?? ''); ?>
            <tr class="hover:bg-khaki/30 transition baris-toko align-middle">
              <td class="p-5">
                <img src="<?= htmlspecialchars($fotoSrc) ?>"
                     class="w-28 h-24 md:w-36 md:h-28 object-cover rounded-xl border border-camel/50 shadow-sm" alt="<?= htmlspecialchars($row['nama_toko']) ?>">
              </td>
              <td class="p-5 md:p-6 font-bold whitespace-nowrap text-lg md:text-xl"><?= htmlspecialchars($row['kode_titik']) ?></td>
              <td class="p-5 md:p-6 max-w-md space-y-1">
                <p class="font-bold text-lg md:text-xl text-espresso leading-snug"><?= htmlspecialchars($row['nama_toko']) ?></p>
                <p class="text-base md:text-lg text-espresso/85 leading-snug"><?= htmlspecialchars($row['alamat_lengkap']) ?></p>
              </td>
              <td class="p-5 md:p-6 whitespace-nowrap space-y-2">
                <span class="block bg-khaki px-3.5 py-1.5 rounded-xl font-bold text-sm md:text-base w-fit"><?= htmlspecialchars($row['kecamatan']) ?></span>
                <span class="block text-base md:text-lg text-cocoa font-semibold"><?= htmlspecialchars($row['kategori_usaha']) ?></span>
              </td>
              <td class="p-5 md:p-6 font-mono text-sm md:text-base whitespace-nowrap leading-relaxed">
                Lat: <?= $row['latitude'] ?><br>Lng: <?= $row['longitude'] ?>
              </td>
              <td class="p-5 md:p-6 whitespace-nowrap space-y-1.5">
                <p class="font-bold text-base md:text-lg"><?= htmlspecialchars($row['jam_buka']) ?></p>
                <p class="text-cocoa font-semibold text-base md:text-lg"><?= htmlspecialchars($row['kisaran_harga']) ?></p>
              </td>
              <td class="p-5 md:p-6 text-center whitespace-nowrap">
                <div class="inline-flex items-center gap-2.5">
                  <button onclick='bukaModalEdit(<?= json_encode($row) ?>)'
                    class="bg-camel/85 hover:bg-camel text-espresso font-bold text-base md:text-lg px-5 py-3 rounded-xl transition shadow-sm">
                    Edit
                  </button>
                  <a href="?hapus=<?= $row['id_toko'] ?>"
                     onclick="return confirm('Yakin ingin menghapus titik <?= htmlspecialchars($row['nama_toko']) ?>?')"
                     class="inline-block bg-red-700/90 hover:bg-red-800 text-white font-bold text-base md:text-lg px-5 py-3 rounded-xl transition shadow-sm">
                    Hapus
                  </a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <footer class="bg-gradient-to-br from-cocoa via-espresso to-[#5C4033] text-khaki text-center text-base md:text-lg py-6 mt-12 border-t border-camel/30 px-8 font-medium flex items-center justify-center gap-4">
    <img src="assets/logo_icon.png" alt="Logo Icon" class="h-10 object-contain filter brightness-0 invert opacity-75">
    &copy; 2026 BAKES WebGIS Admin Panel &mdash; Kota Palu
  </footer>

  <!-- MODAL FORM TAMBAH & EDIT -->
  <div id="modalForm" class="hidden fixed inset-0 bg-espresso/75 backdrop-blur-sm flex items-center justify-center p-4 md:p-6 z-50 overflow-y-auto">
    <div class="bg-linen border border-camel rounded-3xl w-full max-w-[95rem] p-7 md:p-10 shadow-2xl space-y-7 my-auto max-h-[94vh] overflow-y-auto">
      <div class="flex justify-between items-center border-b border-camel/40 pb-5">
        <div>
          <h3 id="modalJudul" class="font-bold text-2xl md:text-3xl text-espresso">Tambah Titik Bakery Baru</h3>
          <p class="text-base md:text-lg text-cocoa mt-1">Lengkapi data spasial koordinat serta atribut toko bakery & pastry</p>
        </div>
        <button onclick="tutupModal()" class="w-12 h-12 rounded-xl bg-khaki/70 hover:bg-camel text-espresso font-bold text-xl flex items-center justify-center transition">✕</button>
      </div>

      <form method="POST" enctype="multipart/form-data" class="space-y-7 text-base md:text-lg">
        <input type="hidden" name="id_toko" id="f_id">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">

          <!-- Kolom Kiri: Peta Mini & Koordinat -->
          <div class="lg:col-span-5 bg-khaki/35 p-6 rounded-2xl border border-camel/50 flex flex-col justify-between space-y-5">
            <div class="space-y-3 flex-1 flex flex-col">
              <span class="font-bold text-espresso text-base md:text-lg block">Klik pada peta mini untuk menentukan titik koordinat otomatis:</span>
              <div id="miniMap" class="w-full min-h-[300px] h-80 lg:h-full rounded-xl border border-camel z-0 flex-1"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="font-bold block mb-2 text-base md:text-lg">Latitude</label>
                <input type="text" name="latitude" id="f_lat" required class="w-full p-3.5 rounded-xl bg-white border border-camel font-mono text-base md:text-lg">
              </div>
              <div>
                <label class="font-bold block mb-2 text-base md:text-lg">Longitude</label>
                <input type="text" name="longitude" id="f_lng" required class="w-full p-3.5 rounded-xl bg-white border border-camel font-mono text-base md:text-lg">
              </div>
            </div>
          </div>

          <!-- Kolom Kanan: Atribut Toko -->
          <div class="lg:col-span-7 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="font-bold block mb-2">Kode Titik</label>
                <input type="text" name="kode_titik" id="f_kode" required placeholder="Titik 31"
                  class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
              <div class="sm:col-span-2">
                <label class="font-bold block mb-2">Nama Toko Bakery</label>
                <input type="text" name="nama_toko" id="f_nama" required placeholder="Nama Toko"
                  class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="sm:col-span-2">
                <label class="font-bold block mb-2">Alamat Lengkap</label>
                <input type="text" name="alamat_lengkap" id="f_alamat" required
                  class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
              <div>
                <label class="font-bold block mb-2">Kecamatan</label>
                <select name="kecamatan" id="f_kecamatan" class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
                  <option value="Mantikulore">Mantikulore</option>
                  <option value="Palu Selatan">Palu Selatan</option>
                  <option value="Palu Timur">Palu Timur</option>
                  <option value="Palu Barat">Palu Barat</option>
                  <option value="Ulujadi">Ulujadi</option>
                  <option value="Tatanga">Tatanga</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="font-bold block mb-2">Kategori Usaha</label>
                <select name="kategori_usaha" id="f_kategori" class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
                  <option value="Bakery">Bakery</option>
                  <option value="Pastry">Pastry</option>
                  <option value="Bakery & Pastry">Bakery & Pastry</option>
                </select>
              </div>
              <div>
                <label class="font-bold block mb-2">Metode Layanan</label>
                <select name="metode_layanan" id="f_layanan" class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
                  <option value="Takeaway">Takeaway</option>
                  <option value="Dine In & Takeaway">Dine In & Takeaway</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="font-bold block mb-2">Estimasi Harga</label>
                <input type="text" name="kisaran_harga" id="f_harga" required class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
              <div>
                <label class="font-bold block mb-2">Jam Operasional</label>
                <input type="text" name="jam_buka" id="f_jam" required class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
              <div>
                <label class="font-bold block mb-2">Hari Buka</label>
                <input type="text" name="hari_buka" id="f_hari" required value="Setiap Hari" class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="font-bold block mb-2">Jenis Produk Utama</label>
                <input type="text" name="best_seller" id="f_best" required class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
              <div>
                <label class="font-bold block mb-2">Metode Pembayaran</label>
                <input type="text" name="metode_pembayaran" id="f_bayar" required value="Cash, Qris, & Transfer" class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="font-bold block mb-2">Fasilitas Tambahan</label>
                <input type="text" name="fasilitas" id="f_fasilitas" required class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
              <div>
                <label class="font-bold block mb-2">Kontak & Media Sosial</label>
                <input type="text" name="kontak_sosmed" id="f_kontak" class="w-full p-3.5 rounded-xl bg-white border border-camel text-base md:text-lg">
              </div>
            </div>

            <div>
              <label class="font-bold block mb-2">Foto Tempat (.JPG) (Kosongkan saat edit jika tidak mengganti foto)</label>
              <input type="file" name="foto_tempat" accept=".jpg,.jpeg,image/jpeg" class="w-full p-3 rounded-xl bg-white border border-camel text-base md:text-lg">
            </div>
          </div>
        </div>

        <div class="pt-5 flex flex-wrap justify-end gap-4 border-t border-camel/40">
          <button type="button" onclick="tutupModal()" class="px-7 py-4 rounded-xl bg-khaki hover:bg-camel font-bold text-base md:text-lg transition">Batal</button>
          <button type="submit" id="btnSubmitForm" name="simpan_baru" class="px-9 py-4 rounded-xl bg-cocoa hover:bg-espresso text-linen font-bold text-base md:text-lg shadow-md transition">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL IMPOR GEOJSON / SHP -->
  <div id="modalImportShp" class="hidden fixed inset-0 bg-espresso/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-linen border border-camel rounded-3xl w-full max-w-xl p-7 shadow-2xl space-y-5">
      <div class="flex justify-between items-center border-b border-camel/40 pb-4">
        <h3 class="font-bold text-xl md:text-2xl text-espresso">Impor Data Spasial (.GeoJSON / .SHP)</h3>
        <button type="button" onclick="document.getElementById('modalImportShp').classList.add('hidden')" class="w-10 h-10 rounded-xl bg-khaki/70 hover:bg-camel font-bold">✕</button>
      </div>
      <p class="text-base text-espresso/85 leading-relaxed">
        Unggah berkas <b>.geojson</b> / <b>.json</b> hasil ekspor dari ArcGIS Pro atau QGIS (seperti <code>Sheet1_XYTable_FeaturesToJSO.geojson</code>). Titik dengan <code>kode_titik</code> yang sama akan diperbarui otomatis.
      </p>
      <form action="sync_shp.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="file" name="file_spasial" accept=".geojson,.json,application/geo+json,application/json" required
          class="w-full p-3 rounded-xl bg-white border border-camel text-base">
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" onclick="document.getElementById('modalImportShp').classList.add('hidden')" class="px-6 py-3 rounded-xl bg-khaki font-bold">Batal</button>
          <button type="submit" name="import_geojson" class="px-7 py-3 rounded-xl bg-cocoa hover:bg-espresso text-linen font-bold">Sinkronkan ke MySQL</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    let miniMap = L.map('miniMap').setView([-0.8950, 119.8780], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
    let miniMarker = null;

    miniMap.on('click', function(e) {
      const lat = e.latlng.lat.toFixed(6);
      const lng = e.latlng.lng.toFixed(6);
      document.getElementById('f_lat').value = lat;
      document.getElementById('f_lng').value = lng;

      if (miniMarker) miniMap.removeLayer(miniMarker);
      miniMarker = L.marker([lat, lng]).addTo(miniMap);
    });

    function bukaModalTambah() {
      document.getElementById('modalJudul').textContent = "Tambah Titik Bakery Baru";
      document.getElementById('btnSubmitForm').name = "simpan_baru";
      document.getElementById('btnSubmitForm').textContent = "Simpan";
      document.querySelector('#modalForm form').reset();
      if (miniMarker) miniMap.removeLayer(miniMarker);

      document.getElementById('modalForm').classList.remove('hidden');
      setTimeout(() => { miniMap.invalidateSize(); }, 200);
    }

    function bukaModalEdit(data) {
      document.getElementById('modalJudul').textContent = "Perbarui Data: " + data.nama_toko;
      document.getElementById('btnSubmitForm').name = "update_data";
      document.getElementById('btnSubmitForm').textContent = "Perbarui Data";

      document.getElementById('f_id').value        = data.id_toko;
      document.getElementById('f_kode').value      = data.kode_titik;
      document.getElementById('f_nama').value      = data.nama_toko;
      document.getElementById('f_alamat').value    = data.alamat_lengkap;
      document.getElementById('f_kecamatan').value = data.kecamatan;
      document.getElementById('f_kategori').value  = data.kategori_usaha;
      document.getElementById('f_layanan').value   = data.metode_layanan;
      document.getElementById('f_lat').value       = data.latitude;
      document.getElementById('f_lng').value       = data.longitude;
      document.getElementById('f_harga').value     = data.kisaran_harga;
      document.getElementById('f_jam').value       = data.jam_buka;
      document.getElementById('f_hari').value      = data.hari_buka;
      document.getElementById('f_best').value      = data.best_seller;
      document.getElementById('f_bayar').value     = data.metode_pembayaran;
      document.getElementById('f_fasilitas').value = data.fasilitas;
      document.getElementById('f_kontak').value    = data.kontak_sosmed;

      document.getElementById('modalForm').classList.remove('hidden');
      setTimeout(() => {
        miniMap.invalidateSize();
        miniMap.setView([data.latitude, data.longitude], 15);
        if (miniMarker) miniMap.removeLayer(miniMarker);
        miniMarker = L.marker([data.latitude, data.longitude]).addTo(miniMap);
      }, 200);
    }

    function tutupModal() {
      document.getElementById('modalForm').classList.add('hidden');
    }

    function filterTabelAdmin() {
      const kata = document.getElementById('cariAdmin').value.toLowerCase();
      const baris = document.querySelectorAll('.baris-toko');
      baris.forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(kata) ? '' : 'none';
      });
    }
  </script>
</body>
</html>