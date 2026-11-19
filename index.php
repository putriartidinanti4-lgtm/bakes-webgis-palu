<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] === 'user' && !isset($_GET['peta']) && !isset($_GET['fokus']) && !isset($_GET['rute'])) {
    header("Location: dashboard_user.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM toko_bakery ORDER BY id_toko ASC");
$dataToko = [];
while ($row = mysqli_fetch_assoc($result)) {
    $dataToko[] = [
        'id'          => (int)$row['id_toko'],
        'kode'        => $row['kode_titik'],
        'nama'        => $row['nama_toko'],
        'alamat'      => $row['alamat_lengkap'],
        'kecamatan'   => $row['kecamatan'],
        'lat'         => (float)$row['latitude'],
        'lng'         => (float)$row['longitude'],
        'kategori'    => $row['kategori_usaha'],
        'layanan'     => $row['metode_layanan'],
        'harga'       => $row['kisaran_harga'],
        'jam_buka'    => $row['jam_buka'],
        'hari_buka'   => $row['hari_buka'],
        'best_seller' => $row['best_seller'],
        'pembayaran'  => $row['metode_pembayaran'],
        'fasilitas'   => $row['fasilitas'],
        'kontak'      => $row['kontak_sosmed'],
        'foto'        => 'assets/' . $row['foto_tempat']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BAKES — Peta Interaktif Bakery & Pastry Kota Palu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
  <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

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
  <style>
    /* MEMPERBESAR UKURAN POP-UP PETA */
    .leaflet-popup-content-wrapper {
      background-color: #F5F1EA;
      color: #4A342A;
      border-radius: 20px;
      border: 1px solid #B2967D;
      padding: 0;
      overflow: hidden;
      box-shadow: 0 12px 30px rgba(74, 52, 42, 0.3);
    }
    .leaflet-popup-content {
      margin: 0;
      width: 400px !important;
    }
    .leaflet-routing-container {
      background-color: #F5F1EA !important;
      color: #4A342A !important;
      border: 1px solid #B2967D !important;
      border-radius: 16px !important;
      font-size: 14px !important;
      max-height: 280px;
      overflow-y: auto;
      margin-top: 56px !important;
      margin-right: 16px !important;
      padding: 12px 14px !important;
      box-shadow: 0 6px 18px rgba(74, 52, 42, 0.25) !important;
    }
    #resizer {
      width: 8px;
      cursor: col-resize;
      background-color: #D7C9B8;
      transition: background-color 0.2s;
      position: relative;
      z-index: 30;
      flex-shrink: 0;
    }
    #resizer:hover, #resizer.active {
      background-color: #7D5A44;
    }
  </style>
</head>
<body class="bg-linen text-espresso h-screen flex flex-col overflow-hidden">

  <!-- NAVBAR ATAS -->
  <header class="bg-gradient-to-br from-cocoa via-espresso to-[#5C4033] text-linen px-8 md:px-12 py-7 flex flex-wrap items-center justify-between gap-6 shadow-md border-b border-camel/40 sticky top-0 z-20">
    <a href="<?= $_SESSION['role'] === 'admin' ? 'admin.php' : 'dashboard_user.php' ?>" class="flex items-center gap-4 hover:opacity-90 transition">
      <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-camel/65 via-cocoa/55 to-transparent flex items-center justify-center overflow-hidden drop-shadow-md">
        <img src="assets/logo_icon.png" alt="Logo Icon" class="w-14 h-14 object-contain">
      </div>
      <div>
        <h1 class="font-bold text-xl md:text-2xl leading-tight">BAKES WebGIS <span class="text-khaki">Kota Palu</span></h1>
        <p class="text-sm md:text-base text-khaki font-medium">Bakery & Pastry Geographic Exploration System</p>
      </div>
    </a>

    <div class="flex items-center gap-5">
      <span class="hidden sm:inline-block text-base md:text-lg font-medium text-khaki tracking-wide">
        Halo, <b class="text-linen font-bold text-lg md:text-xl"><?= htmlspecialchars($_SESSION['user_nama']) ?></b>
      </span>
      <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="admin.php" class="bg-cocoa hover:bg-camel hover:text-espresso text-linen text-base md:text-lg font-bold px-7 py-4 rounded-2xl transition shadow-md">
          ← Kembali ke Admin
        </a>
      <?php else: ?>
        <a href="dashboard_user.php" class="bg-cocoa hover:bg-camel hover:text-espresso text-linen text-base md:text-lg font-bold px-7 py-4 rounded-2xl transition shadow-md flex items-center gap-2.5">
          ← Kembali ke Dashboard
        </a>
      <?php endif; ?>
      <a href="?logout=1" class="border border-khaki/60 hover:bg-cocoa text-linen text-base md:text-lg font-semibold px-6 py-4 rounded-2xl transition">
        Keluar
      </a>
    </div>
  </header>

  <!-- KONTEN UTAMA -->
  <div class="flex-1 flex flex-col md:flex-row overflow-hidden relative">

    <!-- SIDEBAR DAFTAR & PENCARIAN (DIPERBESAR LEBAR 500px DAN HURUFNYA) -->
    <aside id="sidebar" style="width: 500px; min-width: 360px; max-width: 750px;" class="bg-linen flex flex-col h-full z-10">
      <div class="p-7 border-b border-camel/30 bg-khaki/30 space-y-5">
        <div class="flex items-center justify-between">
          <h2 class="font-extrabold text-lg md:text-xl uppercase tracking-wider text-espresso">Daftar Toko Bakery</h2>
          <span id="badgeJumlah" class="bg-cocoa text-linen text-sm font-bold px-4 py-1.5 rounded-full shadow-sm">
            0 Lokasi
          </span>
        </div>

        <input type="text" id="inputCari" onkeyup="filterToko()"
          placeholder="Cari nama toko, alamat, atau produk..."
          class="w-full px-6 py-4 rounded-2xl bg-white border border-camel text-base md:text-lg focus:outline-none focus:ring-2 focus:ring-cocoa shadow-sm font-medium">

        <div class="grid grid-cols-2 gap-4">
          <select id="filterKecamatan" onchange="filterToko()"
            class="w-full px-5 py-3.5 rounded-2xl bg-white border border-camel text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-cocoa shadow-sm font-medium">
            <option value="">Semua Kecamatan</option>
            <option value="Mantikulore">Mantikulore</option>
            <option value="Palu Selatan">Palu Selatan</option>
            <option value="Palu Timur">Palu Timur</option>
            <option value="Palu Barat">Palu Barat</option>
            <option value="Ulujadi">Ulujadi</option>
          </select>

          <select id="filterKategori" onchange="filterToko()"
            class="w-full px-5 py-3.5 rounded-2xl bg-white border border-camel text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-cocoa shadow-sm font-medium">
            <option value="">Semua Kategori</option>
            <option value="Bakery">Bakery</option>
            <option value="Pastry">Pastry</option>
            <option value="Bakery & Pastry">Bakery & Pastry</option>
          </select>
        </div>
      </div>

      <div id="listTokoContainer" class="flex-1 overflow-y-auto p-5 space-y-4"></div>
    </aside>

    <!-- GARIS PEMBATAS UNTUK TARIK/GESER -->
    <div id="resizer"></div>

    <!-- AREA PETA INTERAKTIF -->
    <main class="flex-1 relative h-full">
      <div id="map" class="w-full h-full z-0"></div>

      <button id="btnHapusRute" onclick="hapusRute()"
        class="hidden absolute bottom-6 left-4 z-[1000] bg-linen hover:bg-khaki text-espresso font-bold text-sm px-6 py-4 rounded-2xl shadow-xl border-2 border-cocoa flex items-center gap-3 transition transform hover:scale-105">
        <span class="bg-espresso text-linen w-7 h-7 rounded-full flex items-center justify-center text-xs font-extrabold">✕</span>
        <span>Tutup Rute Navigasi</span>
      </button>

      <div id="infoRuteAktif" class="hidden absolute top-4 left-16 z-[999] bg-espresso text-linen border border-camel px-6 py-3.5 rounded-2xl shadow-xl text-base font-medium">
        🧭 Jalur rute menuju: <b id="namaTujuanRute" class="text-khaki font-bold"></b>
      </div>

      <div id="badgeSebaranPeta" class="absolute top-4 right-4 z-[999] bg-linen/95 backdrop-blur border border-camel px-5 py-2.5 rounded-2xl shadow-md text-sm md:text-base text-espresso font-medium">
        Sebaran <b class="font-bold">30 Titik Bakery & Pastry</b> Kota Palu
      </div>
    </main>
  </div>

  <script>
    const resizer = document.getElementById('resizer');
    const sidebar = document.getElementById('sidebar');

    let isResizing = false;

    resizer.addEventListener('mousedown', function (e) {
      isResizing = true;
      resizer.classList.add('active');
      document.body.style.cursor = 'col-resize';
      e.preventDefault();
    });

    document.addEventListener('mousemove', function (e) {
      if (!isResizing) return;
      let newWidth = e.clientX;
      if (newWidth < 360) newWidth = 360;
      if (newWidth > 750) newWidth = 750;
      sidebar.style.width = newWidth + 'px';
      
      if (typeof map !== 'undefined') {
        map.invalidateSize();
      }
    });

    document.addEventListener('mouseup', function () {
      if (isResizing) {
        isResizing = false;
        resizer.classList.remove('active');
        document.body.style.cursor = 'default';
      }
    });

    const dataBakery = <?= json_encode($dataToko, JSON_UNESCAPED_UNICODE); ?>;

    const map = L.map('map').setView([-0.8920, 119.8780], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap | BAKES Kelompok 6'
    }).addTo(map);

    const bakeryIcon = L.divIcon({
      className: 'custom-pin',
      html: `<div style="background:#4A342A; border:2px solid #F5F1EA; width:34px; height:34px; border-radius:50% 50% 50% 0; transform:rotate(-45deg); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.4);">
               <span style="transform:rotate(45deg); font-size:15px;">🥐</span>
             </div>`,
      iconSize: [34, 34],
      iconAnchor: [17, 34],
      popupAnchor: [0, -36]
    });

    let markersLayer = L.layerGroup().addTo(map);
    let markerObjects = {};
    let routingControl = null;

    function renderData(data) {
      const container = document.getElementById('listTokoContainer');
      const badge     = document.getElementById('badgeJumlah');
      container.innerHTML = '';
      markersLayer.clearLayers();
      markerObjects = {};

      badge.textContent = `${data.length} Lokasi`;

      data.forEach(toko => {
        const popupHTML = `
          <div>
            <img src="${toko.foto}" onerror="this.src='https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&q=80'"
              class="w-full h-52 object-cover border-b border-camel/40" alt="${toko.nama}">
            <div class="p-6 space-y-2.5 text-sm md:text-base">
              <div class="flex items-center justify-between gap-2">
                <span class="bg-khaki text-espresso font-bold px-3.5 py-1.5 rounded-xl text-xs md:text-sm">${toko.kode} • ${toko.kategori}</span>
                <span class="text-cocoa font-extrabold text-xs md:text-sm">${toko.kecamatan}</span>
              </div>
              <h3 class="font-extrabold text-xl text-espresso pt-1">${toko.nama}</h3>
              <p class="text-espresso/90 font-medium">📍 ${toko.alamat}</p>
              <p><b>🥐 Produk Utama:</b> ${toko.best_seller}</p>
              <p><b>💰 Estimasi Harga:</b> ${toko.harga}</p>
              <p><b>🕒 Operasional:</b> ${toko.jam_buka} (${toko.hari_buka})</p>
              <p><b>🍽️ Layanan:</b> ${toko.layanan}</p>
              <p><b>💳 Pembayaran:</b> ${toko.pembayaran}</p>
              <p><b>🅿️ Fasilitas:</b> ${toko.fasilitas}</p>
              ${toko.kontak ? `<p><b>📞 Kontak/IG:</b> ${toko.kontak}</p>` : ''}

              <div class="pt-3.5 flex flex-col gap-3">
                <button onclick="tampilkanRute(${toko.lat}, ${toko.lng}, '${toko.nama.replace(/'/g, "\\'")}', ${toko.id})"
                  class="w-full bg-cocoa hover:bg-espresso text-linen font-bold py-3.5 rounded-2xl transition shadow-md text-center text-base">
                  🧭 Rute dari Lokasi Saya
                </button>
                <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(toko.nama + ' ' + toko.alamat + ' Palu')}" target="_blank"
                  class="w-full bg-khaki hover:bg-camel text-espresso font-bold py-3 rounded-2xl transition text-center block text-sm md:text-base">
                  Buka Pin di Google Maps ↗
                </a>
              </div>
            </div>
          </div>
        `;

        const marker = L.marker([toko.lat, toko.lng], { icon: bakeryIcon })
          .bindPopup(popupHTML)
          .addTo(markersLayer);

        markerObjects[toko.id] = marker;

        const card = document.createElement('div');
        card.className = "p-5 rounded-2xl bg-white hover:bg-khaki/30 border border-camel/50 cursor-pointer transition shadow-sm space-y-2";
        card.innerHTML = `
          <div class="flex justify-between items-start gap-3">
            <h4 class="font-extrabold text-lg text-espresso">${toko.nama}</h4>
            <span class="shrink-0 text-xs bg-camel/50 px-3 py-1 rounded-xl font-bold">${toko.kode}</span>
          </div>
          <p class="text-sm md:text-base text-espresso/85 font-medium flex items-center gap-1.5">
            <span>📍</span> <span class="line-clamp-1">${toko.alamat}</span>
          </p>
          <div class="flex items-center justify-between pt-1 text-xs md:text-sm text-cocoa font-bold">
            <span>🕒 ${toko.jam_buka}</span>
            <span>${toko.kategori} →</span>
          </div>
        `;
        card.onclick = () => {
          map.flyTo([toko.lat, toko.lng], 16, { duration: 1.2 });
          marker.openPopup();
        };
        container.appendChild(card);
      });
    }

    function filterToko() {
      const keyword  = document.getElementById('inputCari').value.toLowerCase();
      const kec      = document.getElementById('filterKecamatan').value;
      const kategori = document.getElementById('filterKategori').value;

      const hasil = dataBakery.filter(t => {
        const cocokTeks = t.nama.toLowerCase().includes(keyword) ||
                          t.alamat.toLowerCase().includes(keyword) ||
                          t.best_seller.toLowerCase().includes(keyword);
        const cocokKec  = kec === "" || t.kecamatan === kec;
        const cocokKat  = kategori === "" || t.kategori === kategori;
        return cocokTeks && cocokKec && cocokKat;
      });

      renderData(hasil);
    }

    function gambarJalurRute(asalLat, asalLng, destLat, destLng, namaToko, tokoId) {
      if (routingControl) {
        map.removeControl(routingControl);
      }

      routingControl = L.Routing.control({
        waypoints: [
          L.latLng(asalLat, asalLng),
          L.latLng(destLat, destLng)
        ],
        lineOptions: {
          styles: [{ color: '#4A342A', weight: 6, opacity: 0.95 }]
        },
        routeWhileDragging: false,
        addWaypoints: false,
        fitSelectedRoutes: true
      }).addTo(map);

      document.getElementById('btnHapusRute').classList.remove('hidden');
      document.getElementById('infoRuteAktif').classList.remove('hidden');
      document.getElementById('namaTujuanRute').textContent = namaToko;

      if (tokoId && markerObjects[tokoId]) {
        markerObjects[tokoId].openPopup();
      }
    }

    function tampilkanRute(destLat, destLng, namaToko, tokoId = null) {
      gambarJalurRute(-0.8917, 119.8707, destLat, destLng, namaToko, tokoId);

      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            gambarJalurRute(pos.coords.latitude, pos.coords.longitude, destLat, destLng, namaToko, tokoId);
          },
          () => {},
          { timeout: 5000 }
        );
      }
    }

    function hapusRute() {
      if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
      }
      document.getElementById('btnHapusRute').classList.add('hidden');
      document.getElementById('infoRuteAktif').classList.add('hidden');
    }

    renderData(dataBakery);

    map.whenReady(() => {
      const urlParams = new URLSearchParams(window.location.search);
      const fokusId = urlParams.get('fokus');
      const ruteId  = urlParams.get('rute');

      if (fokusId && markerObjects[fokusId]) {
        const target = dataBakery.find(t => t.id == fokusId);
        if (target) {
          map.setView([target.lat, target.lng], 16);
          markerObjects[fokusId].openPopup();
        }
      }

      if (ruteId && markerObjects[ruteId]) {
        const target = dataBakery.find(t => t.id == ruteId);
        if (target) {
          tampilkanRute(target.lat, target.lng, target.nama, target.id);
        }
      }
    });
  </script>
</body>
</html>