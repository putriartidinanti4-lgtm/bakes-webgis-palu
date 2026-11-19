<?php
session_start();
require 'koneksi.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
        exit;
    } else {
        header("Location: dashboard_user.php");
        exit;
    }
}

$error     = "";
$sukses    = "";
$activeTab = "login_user";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? 'login_user';
    $activeTab = $aksi;

    if ($aksi === 'login_admin') {
        $email = mysqli_real_escape_string($conn, trim($_POST['email_admin']));
        $pass  = trim($_POST['password_admin']);

        $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND role='admin'");
        if ($query && mysqli_num_rows($query) === 1) {
            $data = mysqli_fetch_assoc($query);
            if ($pass === $data['password'] || password_verify($pass, $data['password'])) {
                $_SESSION['user_id']    = $data['id_user'];
                $_SESSION['user_email'] = $data['email'];
                $_SESSION['user_nama']  = $data['nama'];
                $_SESSION['role']       = 'admin';
                header("Location: admin.php");
                exit;
            }
        }
        $error = "Email atau kata sandi admin salah!";

    } elseif ($aksi === 'signup_user') {
        $nama       = mysqli_real_escape_string($conn, trim($_POST['nama_daftar']));
        $email      = mysqli_real_escape_string($conn, strtolower(trim($_POST['email_daftar'])));
        $pass       = $_POST['password_daftar'];
        $konfirmasi = $_POST['konfirmasi_password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
            $error = "Pendaftaran wajib menggunakan alamat email Gmail (@gmail.com).";
        } elseif (strlen($pass) < 6) {
            $error = "Kata sandi minimal terdiri dari 6 karakter.";
        } elseif ($pass !== $konfirmasi) {
            $error = "Konfirmasi kata sandi tidak cocok!";
        } else {
            $cek = mysqli_query($conn, "SELECT id_user FROM users WHERE email='$email'");
            if ($cek && mysqli_num_rows($cek) > 0) {
                $error = "Email Gmail ini sudah terdaftar. Silakan langsung masuk pada tab Masuk.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $simpan = mysqli_query($conn, "INSERT INTO users (nama, email, password, role) VALUES ('$nama', '$email', '$hash', 'user')");
                if ($simpan) {
                    $sukses    = "Akun berhasil dibuat! Silakan masuk menggunakan Gmail dan kata sandi Anda.";
                    $activeTab = "login_user";
                } else {
                    $error = "Terjadi kesalahan saat menyimpan akun: " . mysqli_error($conn);
                }
            }
        }

    } else {
        $email = mysqli_real_escape_string($conn, strtolower(trim($_POST['email_user'])));
        $pass  = $_POST['password_user'];

        $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND role='user'");
        if ($query && mysqli_num_rows($query) === 1) {
            $data = mysqli_fetch_assoc($query);
            if (password_verify($pass, $data['password']) || $pass === $data['password']) {
                $_SESSION['user_id']    = $data['id_user'];
                $_SESSION['user_email'] = $data['email'];
                $_SESSION['user_nama']  = $data['nama'];
                $_SESSION['role']       = 'user';
                header("Location: dashboard_user.php");
                exit;
            } else {
                $error = "Kata sandi yang Anda masukkan salah!";
            }
        } else {
            $error = "Akun Gmail belum terdaftar. Silakan klik tab Daftar Akun terlebih dahulu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk & Daftar — BAKES WebGIS Kota Palu</title>
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
<body class="bg-espresso min-h-screen flex flex-col items-center justify-center p-6 font-sans text-espresso relative overflow-x-hidden">

  <!-- LATAR BELAKANG FOTO -->
  <div class="absolute inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?w=1920&q=90" 
         class="w-full h-full object-cover" alt="Bakery Background">
    <div class="absolute inset-0 bg-espresso/50"></div>
  </div>

  <!-- KOTAK POP-UP LOGIN (SEDIKIT TRANSPARAN) -->
  <div class="relative z-10 w-full max-w-3xl bg-linen/90 backdrop-blur-md border border-camel/70 rounded-3xl shadow-2xl overflow-hidden my-6">
    
    <!-- Header Gradasi Cokelat Hangat -->
    <div class="bg-gradient-to-br from-cocoa via-espresso to-[#5C4033] text-linen p-10 text-center space-y-4 border-b border-camel/40">
      <div class="w-28 h-28 mx-auto flex items-center justify-center drop-shadow-xl">
        <img src="assets/logo_icon.png" alt="Logo Icon" class="w-full h-full object-contain">
      </div>
      <div>
        <h1 class="text-3xl md:text-5xl font-bold tracking-wide">BAKES WebGIS</h1>
        <p class="text-sm md:text-base text-khaki mt-2 font-medium">Pemetaan Toko Bakery & Pastry Kota Palu</p>
      </div>
    </div>

    <!-- 3 Tab Switcher -->
    <div class="grid grid-cols-3 bg-khaki/70 p-3.5 mx-8 md:mx-12 mt-8 rounded-2xl gap-3 border border-camel/40">
      <button type="button" id="btnLoginUser" onclick="switchTab('login_user')"
        class="py-4 md:py-4.5 text-base md:text-xl font-extrabold rounded-xl bg-cocoa text-linen shadow transition">
        Masuk
      </button>
      <button type="button" id="btnSignupUser" onclick="switchTab('signup_user')"
        class="py-4 md:py-4.5 text-base md:text-xl font-bold rounded-xl text-espresso hover:bg-khaki transition">
        Daftar Akun
      </button>
      <button type="button" id="btnLoginAdmin" onclick="switchTab('login_admin')"
        class="py-4 md:py-4.5 text-base md:text-xl font-bold rounded-xl text-espresso hover:bg-khaki transition">
        Admin
      </button>
    </div>

    <!-- Notifikasi Error / Sukses -->
    <?php if (!empty($error)): ?>
      <div class="mx-8 md:mx-12 mt-6 p-4 bg-red-100 border border-red-400 text-red-800 text-sm md:text-base rounded-2xl shadow-sm font-medium">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($sukses)): ?>
      <div class="mx-8 md:mx-12 mt-6 p-4 bg-emerald-100 border border-emerald-400 text-emerald-900 text-sm md:text-base rounded-2xl shadow-sm font-medium">
        <?= htmlspecialchars($sukses) ?>
      </div>
    <?php endif; ?>

    <!-- 1. FORM LOGIN PENGGUNA -->
    <form id="formLoginUser" method="POST" class="p-8 md:p-12 space-y-7">
      <input type="hidden" name="aksi" value="login_user">
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-3 text-espresso/90">Alamat Gmail</label>
        <input type="email" name="email_user" required placeholder="contoh@gmail.com"
          class="w-full px-6 py-5 md:py-6 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
      </div>
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-3 text-espresso/90">Kata Sandi</label>
        <div class="relative">
          <input type="password" id="passUser" name="password_user" required placeholder="Masukkan kata sandi Anda"
            class="w-full px-6 py-5 md:py-6 pr-16 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
          <button type="button" onclick="togglePassword('passUser', this)"
            class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1.5">
            <svg id="icon-passUser" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <button type="submit"
        class="w-full bg-cocoa hover:bg-espresso text-linen font-extrabold py-5 md:py-6 rounded-2xl shadow-lg transition text-lg md:text-xl tracking-wide">
        Masuk ke Dashboard Pengguna
      </button>

      <p class="text-center text-base md:text-lg text-cocoa pt-2 font-medium">
        Belum punya akun?
        <button type="button" onclick="switchTab('signup_user')" class="font-extrabold underline text-espresso hover:text-cocoa">
          Daftar dengan Gmail
        </button>
      </p>
    </form>

    <!-- 2. FORM SIGN UP PENGGUNA -->
    <form id="formSignupUser" method="POST" class="p-8 md:p-12 space-y-6 hidden">
      <input type="hidden" name="aksi" value="signup_user">
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-2.5 text-espresso/90">Nama Lengkap</label>
        <input type="text" name="nama_daftar" required placeholder="Masukkan nama lengkap"
          class="w-full px-6 py-5 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
      </div>
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-2.5 text-espresso/90">Alamat Gmail</label>
        <input type="email" name="email_daftar" required placeholder="namaanda@gmail.com"
          class="w-full px-6 py-5 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
      </div>
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-2.5 text-espresso/90">Buat Kata Sandi</label>
        <div class="relative">
          <input type="password" id="passDaftar" name="password_daftar" required minlength="6" placeholder="Minimal 6 karakter"
            class="w-full px-6 py-5 pr-16 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
          <button type="button" onclick="togglePassword('passDaftar', this)"
            class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1.5">
            <svg id="icon-passDaftar" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-2.5 text-espresso/90">Konfirmasi Kata Sandi</label>
        <div class="relative">
          <input type="password" id="passKonfirmasi" name="konfirmasi_password" required minlength="6" placeholder="Ulangi kata sandi di atas"
            class="w-full px-6 py-5 pr-16 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
          <button type="button" onclick="togglePassword('passKonfirmasi', this)"
            class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1.5">
            <svg id="icon-passKonfirmasi" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <button type="submit"
        class="w-full bg-cocoa hover:bg-espresso text-linen font-extrabold py-5 md:py-6 rounded-2xl shadow-lg transition text-lg md:text-xl tracking-wide mt-2">
        Daftar Akun Baru
      </button>
    </form>

    <!-- 3. FORM LOGIN ADMIN -->
    <form id="formLoginAdmin" method="POST" class="p-8 md:p-12 space-y-7 hidden">
      <input type="hidden" name="aksi" value="login_admin">
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-3 text-espresso/90">Email Admin</label>
        <input type="email" name="email_admin" required placeholder="admin@bakes.id"
          class="w-full px-6 py-5 md:py-6 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
      </div>
      <div>
        <label class="block text-base md:text-lg font-extrabold uppercase tracking-wider mb-3 text-espresso/90">Kata Sandi Admin</label>
        <div class="relative">
          <input type="password" id="passAdmin" name="password_admin" required placeholder="••••••••"
            class="w-full px-6 py-5 md:py-6 pr-16 rounded-2xl bg-white border border-camel/70 focus:outline-none focus:ring-2 focus:ring-cocoa text-lg md:text-xl shadow-sm font-medium">
          <button type="button" onclick="togglePassword('passAdmin', this)"
            class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1.5">
            <svg id="icon-passAdmin" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <button type="submit"
        class="w-full bg-espresso hover:bg-cocoa text-linen font-extrabold py-5 md:py-6 rounded-2xl shadow-lg transition text-lg md:text-xl tracking-wide">
        Masuk Dashboard Admin
      </button>
    </form>
  </div>

  <!-- KELOMPOK 6 DI LUAR DI BAWAH KOTAK LOGIN -->
  <div class="relative z-10 text-center mt-4 text-linen/90 bg-gradient-to-r from-cocoa/80 via-espresso/80 to-cocoa/80 backdrop-blur-sm px-6 py-3 rounded-2xl border border-camel/40 text-xs md:text-sm font-bold tracking-wide shadow-md">
    KELOMPOK 6 • TEKNIK INFORMATIKA • UNIVERSITAS TADULAKO
  </div>

  <script>
    function switchTab(tab) {
      const forms = {
        login_user:  document.getElementById('formLoginUser'),
        signup_user: document.getElementById('formSignupUser'),
        login_admin: document.getElementById('formLoginAdmin')
      };
      const btns = {
        login_user:  document.getElementById('btnLoginUser'),
        signup_user: document.getElementById('btnSignupUser'),
        login_admin: document.getElementById('btnLoginAdmin')
      };

      const activeClass   = "py-4 md:py-4.5 text-base md:text-xl font-extrabold rounded-xl bg-cocoa text-linen shadow transition";
      const inactiveClass = "py-4 md:py-4.5 text-base md:text-xl font-bold rounded-xl text-espresso hover:bg-khaki transition";

      Object.keys(forms).forEach(key => {
        if (key === tab) {
          forms[key].classList.remove('hidden');
          btns[key].className = activeClass;
        } else {
          forms[key].classList.add('hidden');
          btns[key].className = inactiveClass;
        }
      });
    }

    function togglePassword(fieldId, btn) {
      const input = document.getElementById(fieldId);
      const svgIcon = btn.querySelector('svg');
      
      if (input.type === "password") {
        input.type = "text";
        svgIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        `;
      } else {
        input.type = "password";
        svgIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
      }
    }

    switchTab('<?= $activeTab ?>');
  </script>
</body>
</html>