# BAKES WebGIS (Bakery & Pastry Geographic Exploration System) — Kota Palu

Aplikasi **Sistem Informasi Geografis (SIG) berbasis web** untuk memetakan, menjelajahi, dan mengelola data **30 titik lokasi toko *bakery* dan *pastry*** di **Kota Palu**[cite: 13, 14]. Proyek ini disusun sebagai tugas besar mata kuliah **Sistem Informasi Geografis** oleh **Kelompok 6** (Kelas Teknik Informatika B, Jurusan Teknologi Informasi, Fakultas Teknik, Universitas Tadulako)[cite: 13, 14].

---

## Anggota Kelompok 6
* **Ikhtiar M** — `F5512520044`[cite: 13, 14]
* **Putri Arti Dinanti** — `F5512520048`[cite: 13, 14]
* **Nazwa Alifiah Bustaman** — `F5512520050`[cite: 13, 14]

**Dosen Pengampu:** Dr. Deny Wiria Nugraha, S.T., M.Eng.[cite: 13]

---

## Fitur Utama

### 1. Sisi Pengguna (`User` / Pengunjung)
* **Autentikasi Terpadu (`login.php`):** Pendaftaran akun baru berbasis Gmail (`@gmail.com`) dengan enkripsi kata sandi (`password_hash`), *login* terpisah berdasarkan peran (`user` dan `admin`), serta pengelolaan sesi (`$_SESSION`)[cite: 13].
* **Dasbor & Katalog (`dashboard_user.php`):** Menampilkan ringkasan total titik lokasi (30 titik) dan sebaran per kecamatan di Kota Palu (Palu Selatan, Palu Timur, Mantikulore, Palu Barat, dan Ulujadi), dilengkapi katalog kartu toko beserta foto tempat, kode titik, kategori usaha, alamat, produk utama (*best seller*), kisaran harga, jam buka, metode layanan, dan metode pembayaran[cite: 13].
* **Peta Digital Interaktif (`index.php`):** Menampilkan peta Kota Palu menggunakan **Leaflet.js** dan **OpenStreetMap** dengan *marker* 30 titik lokasi[cite: 13].
* **Pencarian & Penyaringan (*Filtering*):** Penyaringan lokasi berdasarkan kata kunci (nama toko, alamat, atau produk), wilayah kecamatan, dan kategori usaha (`Bakery`, `Pastry`, maupun `Bakery & Pastry`)[cite: 13].
* **Pop-up Detail & Navigasi Rute:** Menampilkan *pop-up* informasi lengkap beserta foto tempat berformat `.JPG`, penelusuran rute perjalanan dari lokasi pengguna menggunakan **Leaflet Routing Machine** dan **Geolocation**, serta tautan langsung ke **Google Maps**[cite: 13].

### 2. Sisi Administrator (`Admin` — `admin.php`)
* **Manajemen Data Spasial & Atribut (CRUD):** Tambah, ubah, dan hapus data lokasi serta atribut toko pada tabel `toko_bakery`, dilengkapi peta mini interaktif (`#miniMap`) untuk mengisi koordinat `latitude` dan `longitude` secara otomatis saat diklik, unggah foto `.JPG`, konfirmasi penghapusan, dan notifikasi status transaksi[cite: 13].
* **Integrasi ArcGIS (`.SHP`) & GeoJSON (`sync_shp.php`):** Ekspor data ke format **GeoJSON** dan impor berkas **GeoJSON** hasil olahan ArcGIS untuk disinkronkan dengan basis data MySQL[cite: 13].

---

## Teknologi yang Digunakan
* **Backend:** PHP Native (MySQLi) & MySQL/MariaDB[cite: 13]
* **Frontend:** HTML5, Tailwind CSS (CDN), dan JavaScript (ES6+)[cite: 13]
* **Pemetaan & GIS:** Leaflet.js, Leaflet Routing Machine, OpenStreetMap, ArcGIS Pro (WGS 1984 / EPSG:4326), dan format GeoJSON[cite: 13]
* **Lingkungan Pengembangan:** Laragon, Visual Studio Code, dan Git/GitHub[cite: 11, 13]

---

## Struktur Basis Data Utama
* **`users`:** Menyimpan data akun pengguna umum dan administrator (`id_user`, `nama`, `email`, `password`, `role`)[cite: 13].
* **`toko_bakery`:** Menyimpan data spasial dan atribut 30 titik lokasi toko *bakery* & *pastry* di Kota Palu (`id_toko`, `kode_titik`, `nama_toko`, `alamat_lengkap`, `kecamatan`, `latitude`, `longitude`, `kategori_usaha`, `metode_layanan`, `kisaran_harga`, `jam_buka`, `hari_buka`, `best_seller`, `metode_pembayaran`, `fasilitas`, `kontak_sosmed`, `foto_tempat`)[cite: 13].

---

## Cara Menjalankan di Lingkungan Lokal (Laragon)
1. Tempatkan folder proyek di `C:\laragon\www\BAKES`.
2. Jalankan **Laragon** (aktifkan layanan Apache/PHP dan MySQL)[cite: 13].
3. Buat basis data di MySQL dan sesuaikan konfigurasi koneksinya pada berkas `koneksi.php`[cite: 9, 13].
4. Impor/sinkronkan data 30 titik lokasi melalui `admin.php` / `sync_shp.php` menggunakan berkas GeoJSON yang tersedia (`BAKES_30_Titik_Palu_20261119.geojson`)[cite: 9, 13].
5. Akses aplikasi melalui peramban di alamat lokal sesuai konfigurasi Laragon Anda[cite: 13].
