<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$qTotal = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM toko_bakery");
$totalToko = mysqli_fetch_assoc($qTotal)['jml'];

$qKec = mysqli_query($conn, "SELECT kecamatan, COUNT(*) AS jml FROM toko_bakery GROUP BY kecamatan ORDER BY jml DESC");
$statistikKec = [];
while ($r = mysqli_fetch_assoc($qKec)) {
    $statistikKec[] = $r;
}

$qToko = mysqli_query($conn, "SELECT * FROM toko_bakery ORDER BY id_toko ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Pengguna — BAKES WebGIS Kota Palu</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-linen text-espresso min-h-screen flex flex-col font-sans">

  <!-- NAVBAR ATAS MENGGUNAKAN GAMBAR LOGO DARI ASSETS -->
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
        Halo, <b class="text-linen font-bold text-lg md:text-xl"><?= htmlspecialchars($_SESSION['user_nama']) ?></b>
      </span>
      <a href="index.php?peta=1" class="bg-cocoa hover:bg-camel hover:text-espresso text-linen text-base md:text-lg font-bold px-7 py-4 rounded-2xl transition shadow-md flex items-center gap-2.5">
        Buka Peta
      </a>
      <a href="index.php?logout=1" class="border border-khaki/60 hover:bg-cocoa text-linen text-base md:text-lg font-semibold px-6 py-4 rounded-2xl transition">
        Keluar
      </a>
    </div>
  </header>

  <!-- KONTEN UTAMA -->
  <main class="w-full px-6 md:px-12 py-10 space-y-10 flex-1">

    <!-- BANNER SELAMAT DATANG -->
    <div class="bg-gradient-to-r from-espresso to-cocoa text-linen rounded-3xl p-10 md:p-14 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-8 border border-camel/40">
      <div class="space-y-4">
        <span class="bg-camel text-espresso text-sm md:text-base font-extrabold px-5 py-2 rounded-full shadow-sm tracking-wide">
          AKUN PENGUNJUNG TERVERIFIKASI
        </span>
        <h2 class="text-3xl md:text-5xl font-extrabold tracking-wide pt-1">Selamat Datang, <?= htmlspecialchars($_SESSION['user_nama']) ?>!</h2>
        <p class="text-base md:text-lg text-khaki max-w-5xl leading-relaxed">
          Jelajahi sebaran <b><?= $totalToko ?> titik toko bakery & pastry</b> di Kota Palu lengkap dengan informasi produk utama, rentang harga, jam operasional, metode pembayaran, serta navigasi rute langsung dari lokasi Anda.
        </p>
      </div>
      <a href="index.php?peta=1" class="shrink-0 bg-linen hover:bg-khaki text-espresso font-bold text-lg md:text-xl px-9 py-5 rounded-2xl shadow-xl transition flex items-center gap-3">
        Jelajahi Peta Sekarang →
      </a>
    </div>

    <!-- RINGKASAN SEBARAN PER KECAMATAN -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-5" id="containerKartuKecamatan">
      <button type="button" onclick="pilihKecamatanDariKartu('')" data-kec=""
        class="kartu-filter-kec text-left bg-espresso text-linen p-7 rounded-2xl shadow-md ring-2 ring-cocoa cursor-pointer transition transform hover:-translate-y-1 flex flex-col justify-between min-h-[170px]">
        <p class="text-sm uppercase tracking-wider text-camel font-bold">TOTAL LOKASI</p>
        <div>
          <p class="text-3xl md:text-4xl font-extrabold mt-3"><?= $totalToko ?> <span class="text-base font-normal">Titik</span></p>
          <p class="text-sm text-khaki mt-1 font-medium">Kota Palu</p>
        </div>
      </button>

      <?php foreach ($statistikKec as $kec): ?>
      <button type="button" onclick="pilihKecamatanDariKartu('<?= htmlspecialchars($kec['kecamatan'], ENT_QUOTES) ?>')"
        data-kec="<?= htmlspecialchars($kec['kecamatan']) ?>"
        class="kartu-filter-kec text-left bg-khaki/55 hover:bg-khaki border border-camel/50 text-espresso p-7 rounded-2xl shadow-sm cursor-pointer transition transform hover:-translate-y-1 flex flex-col justify-between min-h-[170px]">
        <p class="text-sm uppercase tracking-wider text-cocoa font-bold label-kec"><?= htmlspecialchars($kec['kecamatan']) ?></p>
        <div>
          <p class="text-3xl md:text-4xl font-extrabold mt-3"><?= $kec['jml'] ?> <span class="text-base font-normal">Toko</span></p>
          <p class="text-sm text-espresso/80 mt-1 font-semibold sub-kec">Terpetakan</p>
        </div>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- KATALOG TOKO BAKERY & PASTRY -->
    <div id="bagianKatalog" class="space-y-6 pt-2">
      <!-- KOTAK PENCARIAN & FILTER -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-khaki/30 p-8 md:p-10 rounded-3xl border border-camel/40 shadow-sm">
        <div>
          <h3 class="font-bold text-2xl md:text-4xl text-espresso" id="judulKatalog">Katalog 30 Toko Bakery & Pastry Kota Palu</h3>
          <p class="text-sm md:text-lg text-espresso/80 mt-2 font-medium">Klik kartu kecamatan di atas atau gunakan pencarian di bawah untuk menyaring lokasi</p>
        </div>

        <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
          <input type="text" id="cariKatalog" onkeyup="filterKatalog()" placeholder="Cari nama toko atau produk..."
            class="px-6 py-5 rounded-2xl bg-white border border-camel text-base md:text-lg focus:outline-none focus:ring-2 focus:ring-cocoa w-full md:w-96 shadow-sm">
          <select id="filterKecUser" onchange="sinkronDariDropdown()"
            class="px-6 py-5 rounded-2xl bg-white border border-camel text-base md:text-lg focus:outline-none focus:ring-2 focus:ring-cocoa shadow-sm w-full md:w-auto font-medium">
            <option value="">Semua Kecamatan</option>
            <option value="Mantikulore">Mantikulore</option>
            <option value="Palu Selatan">Palu Selatan</option>
            <option value="Palu Timur">Palu Timur</option>
            <option value="Palu Barat">Palu Barat</option>
            <option value="Ulujadi">Ulujadi</option>
          </select>
        </div>
      </div>

      <!-- GRID KATALOG TOKO -->
      <div id="gridKatalog" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
        <?php while ($t = mysqli_fetch_assoc($qToko)): ?>
        <div class="kartu-toko bg-white border border-camel/50 rounded-3xl overflow-hidden shadow-md hover:shadow-xl transition duration-300 flex flex-col justify-between"
             data-kecamatan="<?= htmlspecialchars($t['kecamatan']) ?>">
          <div>
            <div class="relative h-64 overflow-hidden bg-khaki/20">
              <img src="assets/<?= htmlspecialchars($t['foto_tempat']) ?>"
                   onerror="this.src='https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&q=80'"
                   class="w-full h-full object-cover transition duration-500 hover:scale-105" alt="<?= htmlspecialchars($t['nama_toko']) ?>">
              <span class="absolute top-4 left-4 bg-espresso/95 text-linen text-sm md:text-base font-bold px-4.5 py-2 rounded-xl shadow">
                <?= htmlspecialchars($t['kode_titik']) ?> • <?= htmlspecialchars($t['kategori_usaha']) ?>
              </span>
              <span class="absolute top-4 right-4 bg-linen/95 text-espresso text-sm md:text-base font-bold px-4.5 py-2 rounded-xl border border-camel/50 shadow">
                <?= htmlspecialchars($t['kecamatan']) ?>
              </span>
            </div>

            <div class="p-7 space-y-3.5 text-base md:text-lg">
              <h4 class="font-extrabold text-2xl text-espresso"><?= htmlspecialchars($t['nama_toko']) ?></h4>
              <p class="text-espresso/90 font-medium line-clamp-1">📍 <?= htmlspecialchars($t['alamat_lengkap']) ?></p>
              <div class="pt-3.5 space-y-3 border-t border-camel/30 text-espresso/95 font-medium">
                <p><b>🥐 Produk Utama:</b> <?= htmlspecialchars($t['best_seller']) ?></p>
                <p><b>💰 Kisaran Harga:</b> <?= htmlspecialchars($t['kisaran_harga']) ?></p>
                <p><b>🕒 Jam Buka:</b> <?= htmlspecialchars($t['jam_buka']) ?> (<?= htmlspecialchars($t['hari_buka']) ?>)</p>
                <p><b>🍽️ Layanan:</b> <?= htmlspecialchars($t['metode_layanan']) ?></p>
                <p><b>💳 Pembayaran:</b> <?= htmlspecialchars($t['metode_pembayaran']) ?></p>
              </div>
            </div>
          </div>

          <div class="p-7 pt-3">
            <a href="index.php?fokus=<?= $t['id_toko'] ?>"
               class="block w-full bg-cocoa hover:bg-espresso text-linen font-bold text-lg md:text-xl py-5 rounded-2xl text-center transition shadow-md">
              📍 Lihat di Peta
            </a>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="bg-espresso text-khaki text-center text-base md:text-lg py-6 mt-16 border-t border-camel/30 px-8 font-medium flex items-center justify-center gap-4">
    <img src="assets/logo_icon.png" alt="Logo Icon" class="h-10 object-contain filter brightness-0 invert opacity-75">
    &copy; 2026 BAKES WebGIS &mdash; Kota Palu
  </footer>

  <script>
    function perbaruiTampilanKartuAktif(kecAktif) {
      const semuaKartu = document.querySelectorAll('.kartu-filter-kec');
      semuaKartu.forEach(btn => {
        const isAktif = btn.dataset.kec === kecAktif;
        const label = btn.querySelector('.label-kec');
        const sub   = btn.querySelector('.sub-kec');

        if (isAktif) {
          btn.className = "kartu-filter-kec text-left bg-espresso text-linen p-7 rounded-2xl shadow-md ring-2 ring-cocoa cursor-pointer transition transform hover:-translate-y-1 flex flex-col justify-between min-h-[170px]";
          if (label) label.className = "text-sm uppercase tracking-wider text-camel font-bold label-kec";
          if (sub)   sub.className   = "text-sm text-khaki mt-1 font-medium sub-kec";
        } else {
          btn.className = "kartu-filter-kec text-left bg-khaki/55 hover:bg-khaki border border-camel/50 text-espresso p-7 rounded-2xl shadow-sm cursor-pointer transition transform hover:-translate-y-1 flex flex-col justify-between min-h-[170px]";
          if (label) label.className = "text-sm uppercase tracking-wider text-cocoa font-bold label-kec";
          if (sub)   sub.className   = "text-sm text-espresso/80 mt-1 font-semibold sub-kec";
        }
      });
    }

    function pilihKecamatanDariKartu(namaKec) {
      const dropdown = document.getElementById('filterKecUser');
      dropdown.value = namaKec;
      perbaruiTampilanKartuAktif(namaKec);
      filterKatalog();
      document.getElementById('bagianKatalog').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function sinkronDariDropdown() {
      const kec = document.getElementById('filterKecUser').value;
      perbaruiTampilanKartuAktif(kec);
      filterKatalog();
    }

    function filterKatalog() {
      const kata  = document.getElementById('cariKatalog').value.toLowerCase();
      const kec   = document.getElementById('filterKecUser').value;
      const kartu = document.querySelectorAll('.kartu-toko');
      let jumlahTampil = 0;

      kartu.forEach(item => {
        const cocokTeks = item.textContent.toLowerCase().includes(kata);
        const cocokKec  = kec === "" || item.dataset.kecamatan === kec;
        const tampil    = cocokTeks && cocokKec;
        item.style.display = tampil ? '' : 'none';
        if (tampil) jumlahTampil++;
      });

      const judul = document.getElementById('judulKatalog');
      judul.textContent = kec
        ? `Katalog ${jumlahTampil} Toko di Kecamatan ${kec}`
        : `Katalog ${jumlahTampil} Toko Bakery & Pastry Kota Palu`;
    }
  </script>
</body>
</html>