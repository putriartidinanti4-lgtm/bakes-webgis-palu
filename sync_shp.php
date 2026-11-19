<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// =========================================================================
// 1. EKSPOR DATA MYSQL (toko_bakery) KE GEOJSON (UNTUK ARCGIS / QGIS -> .SHP)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'export_geojson') {
    $res = mysqli_query($conn, "SELECT * FROM toko_bakery ORDER BY id_toko ASC");
    $features = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $lat = (float)$row['latitude'];
        $lng = (float)$row['longitude'];

        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$lng, $lat] // Format standar GeoJSON: [Longitude, Latitude]
            ],
            'properties' => [
                'id_toko'           => (int)$row['id_toko'],
                'kode_titik'        => $row['kode_titik'],
                'nama_toko'         => $row['nama_toko'],
                'alamat_lengkap'    => $row['alamat_lengkap'],
                'kecamatan'         => $row['kecamatan'],
                'kategori_usaha'    => $row['kategori_usaha'],
                'metode_layanan'    => $row['metode_layanan'],
                'kisaran_harga'     => $row['kisaran_harga'],
                'jam_buka'          => $row['jam_buka'],
                'hari_buka'         => $row['hari_buka'],
                'best_seller'       => $row['best_seller'],
                'metode_pembayaran' => $row['metode_pembayaran'],
                'fasilitas'         => $row['fasilitas'],
                'kontak_sosmed'     => $row['kontak_sosmed'],
                'foto_tempat'       => $row['foto_tempat']
            ]
        ];
    }

    $geojson = [
        'type' => 'FeatureCollection',
        'name' => 'Sebaran_Bakery_Pastry_Kota_Palu',
        'crs'  => [
            'type' => 'name',
            'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84']
        ],
        'features' => $features
    ];

    header('Content-Type: application/geo+json; charset=utf-8');
    header('Content-Disposition: attachment; filename="BAKES_30_Titik_Palu_' . date('Ymd') . '.geojson"');
    echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================================
// 2. IMPOR GEOJSON (HASIL FEATURES TO JSON / EKSPOR .SHP ARCGIS) KE MYSQL
// =========================================================================
if (isset($_POST['import_geojson'])) {
    if (empty($_FILES['file_spasial']['name']) || $_FILES['file_spasial']['error'] !== UPLOAD_ERR_OK) {
        header("Location: admin.php?pesan=gagal_upload_spasial");
        exit;
    }

    $raw  = file_get_contents($_FILES['file_spasial']['tmp_name']);
    $data = json_decode($raw, true);

    if (!is_array($data) || !isset($data['features']) || !is_array($data['features'])) {
        header("Location: admin.php?pesan=format_geojson_tidak_valid");
        exit;
    }

    $berhasil = 0;
    foreach ($data['features'] as $idx => $feat) {
        $p = $feat['properties'] ?? [];

        // Ambil koordinat dari geometry.coordinates [lng, lat] jika ada,
        // atau fallback ke atribut latitude/longitude / X/Y bila berasal dari XY Table
        $lng = null;
        $lat = null;

        if (!empty($feat['geometry']['coordinates']) && is_array($feat['geometry']['coordinates']) && count($feat['geometry']['coordinates']) >= 2) {
            $lng = (float)$feat['geometry']['coordinates'][0];
            $lat = (float)$feat['geometry']['coordinates'][1];
        } else {
            $latVal = $p['latitude'] ?? $p['Latitude'] ?? $p['lat'] ?? $p['Y'] ?? $p['y'] ?? $p['POINT_Y'] ?? null;
            $lngVal = $p['longitude'] ?? $p['Longitude'] ?? $p['lng'] ?? $p['lon'] ?? $p['X'] ?? $p['x'] ?? $p['POINT_X'] ?? null;
            if ($latVal !== null && $lngVal !== null && $latVal !== '' && $lngVal !== '') {
                $lat = (float)$latVal;
                $lng = (float)$lngVal;
            }
        }

        if ($lat === null || $lng === null) {
            continue;
        }

        // Dukungan berbagai nama field (termasuk potongan 10 karakter dari ArcGIS .dbf / OBJECTID)
        $nomorUrut = $p['OBJECTID'] ?? $p['FID'] ?? $p['Id'] ?? ($idx + 1);
        $kodeRaw   = $p['kode_titik'] ?? $p['kode_titi'] ?? $p['Kode_Titik'] ?? $p['Kode'] ?? $p['kode'] ?? ('Titik ' . $nomorUrut);
        $namaRaw   = $p['nama_toko'] ?? $p['nama_tok'] ?? $p['Nama_Toko'] ?? $p['Nama'] ?? $p['nama'] ?? ('Toko Bakery ' . $nomorUrut);
        $almtRaw   = $p['alamat_lengkap'] ?? $p['alamat_len'] ?? $p['Alamat_Len'] ?? $p['Alamat'] ?? $p['alamat'] ?? '-';
        $kecRaw    = $p['kecamatan'] ?? $p['kecamata'] ?? $p['Kecamatan'] ?? $p['kec'] ?? 'Mantikulore';
        $katRaw    = $p['kategori_usaha'] ?? $p['kategori_u'] ?? $p['Kategori_U'] ?? $p['Kategori'] ?? $p['kategori'] ?? 'Bakery';
        $layRaw    = $p['metode_layanan'] ?? $p['metode_lay'] ?? $p['Metode_Lay'] ?? $p['Layanan'] ?? 'Takeaway';
        $hrgRaw    = $p['kisaran_harga'] ?? $p['kisaran_ha'] ?? $p['Kisaran_Ha'] ?? $p['Harga'] ?? '-';
        $jamRaw    = $p['jam_buka'] ?? $p['jam_buk'] ?? $p['Jam_Buka'] ?? '08.00 - 22.00 WITA';
        $hariRaw   = $p['hari_buka'] ?? $p['hari_buk'] ?? $p['Hari_Buka'] ?? 'Setiap Hari';
        $bestRaw   = $p['best_seller'] ?? $p['best_selle'] ?? $p['Best_Selle'] ?? $p['Produk'] ?? '-';
        $byrRaw    = $p['metode_pembayaran'] ?? $p['metode_pem'] ?? $p['Metode_Pem'] ?? $p['Pembayaran'] ?? 'Cash, Qris, & Transfer';
        $fasRaw    = $p['fasilitas'] ?? $p['fasilita'] ?? $p['Fasilitas'] ?? '-';
        $kntRaw    = $p['kontak_sosmed'] ?? $p['kontak_sos'] ?? $p['Kontak_Sos'] ?? $p['Kontak'] ?? '-';
        $fotoRaw   = $p['foto_tempat'] ?? $p['foto_tempa'] ?? $p['Foto_Tempa'] ?? $p['Foto'] ?? ('titik_' . $nomorUrut . '.JPG');

        $kode      = mysqli_real_escape_string($conn, trim((string)$kodeRaw));
        $nama      = mysqli_real_escape_string($conn, trim((string)$namaRaw));
        $alamat    = mysqli_real_escape_string($conn, trim((string)$almtRaw));
        $kecamatan = mysqli_real_escape_string($conn, trim((string)$kecRaw));
        $kategori  = mysqli_real_escape_string($conn, trim((string)$katRaw));
        $layanan   = mysqli_real_escape_string($conn, trim((string)$layRaw));
        $harga     = mysqli_real_escape_string($conn, trim((string)$hrgRaw));
        $jam       = mysqli_real_escape_string($conn, trim((string)$jamRaw));
        $hari      = mysqli_real_escape_string($conn, trim((string)$hariRaw));
        $best      = mysqli_real_escape_string($conn, trim((string)$bestRaw));
        $bayar     = mysqli_real_escape_string($conn, trim((string)$byrRaw));
        $fasilitas = mysqli_real_escape_string($conn, trim((string)$fasRaw));
        $kontak    = mysqli_real_escape_string($conn, trim((string)$kntRaw));
        $foto      = mysqli_real_escape_string($conn, trim((string)$fotoRaw));

        $cek = mysqli_query($conn, "SELECT id_toko FROM toko_bakery WHERE kode_titik='$kode' LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $existing = mysqli_fetch_assoc($cek);
            $idExist  = (int)$existing['id_toko'];
            mysqli_query($conn, "UPDATE toko_bakery SET
                nama_toko='$nama', alamat_lengkap='$alamat', kecamatan='$kecamatan',
                latitude='$lat', longitude='$lng', kategori_usaha='$kategori',
                metode_layanan='$layanan', kisaran_harga='$harga', jam_buka='$jam',
                hari_buka='$hari', best_seller='$best', metode_pembayaran='$bayar',
                fasilitas='$fasilitas', kontak_sosmed='$kontak', foto_tempat='$foto'
                WHERE id_toko=$idExist");
        } else {
            mysqli_query($conn, "INSERT INTO toko_bakery
                (kode_titik, nama_toko, alamat_lengkap, kecamatan, latitude, longitude, kategori_usaha, metode_layanan, kisaran_harga, jam_buka, hari_buka, best_seller, metode_pembayaran, fasilitas, kontak_sosmed, foto_tempat)
                VALUES ('$kode', '$nama', '$alamat', '$kecamatan', '$lat', '$lng', '$kategori', '$layanan', '$harga', '$jam', '$hari', '$best', '$bayar', '$fasilitas', '$kontak', '$foto')");
        }
        $berhasil++;
    }

    header("Location: admin.php?pesan=disinkronkan_(" . $berhasil . "_titik)");
    exit;
}

header("Location: admin.php");
exit;