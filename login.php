<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/koneksi.php';

$pageTitle = 'Masuk';
$baseUrl = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['nama']      = $user['nama_lengkap'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: dashboardadmin.php');
            } else {
                header('Location: dashboarduser.php');
            }
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Masuk ke Akun Anda</h2>
        <p class="sub">Silakan masuk untuk melakukan booking lapangan.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="alert alert-success">Registrasi berhasil! Silakan login.</div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="form-login">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" placeholder="email@contoh.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btn-login">Masuk</button>
        </form>

        <div class="footer-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>

        <div class="footer-link" style="margin-top: 10px; font-size: 12px; color: #aaa;">
            Demo admin: <strong>admin@MyPadel.com</strong> / <strong>password</strong>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
