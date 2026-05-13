<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/koneksi.php';

$pageTitle = 'Daftar Akun';
$baseUrl = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telp     = trim($_POST['nomor_telepon'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['konfirmasi_password'] ?? '';

    // Validasi
    if (empty($nama))  $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email)) $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (empty($pass))  $errors[] = 'Password wajib diisi.';
    elseif (strlen($pass) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($pass !== $pass2) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        // Cek email sudah terdaftar
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Email sudah terdaftar, gunakan email lain.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role) VALUES (?, ?, ?, ?, 'customer')");
            mysqli_stmt_bind_param($stmt2, 'ssss', $nama, $email, $hash, $telp);
            if (mysqli_stmt_execute($stmt2)) {
                $success = 'Registrasi berhasil! Silakan <a href="login.php">login</a>.';
            } else {
                $errors[] = 'Terjadi kesalahan. Coba lagi.';
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Buat Akun Baru</h2>
        <p class="sub">Daftar sekarang dan mulai booking lapangan padel favoritmu.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div>• <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="register.php" id="form-register">
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Budi Santoso"
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" placeholder="email@contoh.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="nomor_telepon">Nomor Telepon</label>
                <input type="text" id="nomor_telepon" name="nomor_telepon" placeholder="08xxxxxxxxxx"
                       value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>

            <div class="form-group">
                <label for="konfirmasi_password">Konfirmasi Password</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btn-register">Daftar Sekarang</button>
        </form>
        <?php endif; ?>

        <div class="footer-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
