<?php
/**
 * setup.php - Script setup awal database
 * Jalankan SEKALI saja: http://localhost/MyPadel/setup.php
 * Hapus atau rename file ini setelah selesai setup!
 */

// Konfigurasi database (sesuaikan jika berbeda)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'MyPadel';

$errors = [];
$success = [];

// Koneksi tanpa nama database dulu
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("<div style='font-family:Arial; color:red; padding:20px;'>
        <h2>❌ Koneksi MySQL Gagal</h2>
        <p>" . mysqli_connect_error() . "</p>
        <p>Pastikan MySQL server sudah berjalan dan konfigurasi di setup.php sudah benar.</p>
    </div>");
}

// Buat database
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, $dbName);

// Buat tabel
$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_lengkap VARCHAR(150) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nomor_telepon VARCHAR(20),
        role ENUM('admin','customer') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS courts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_lapangan VARCHAR(100) NOT NULL,
        tipe_lapangan ENUM('Indoor','Outdoor') NOT NULL,
        harga_per_jam DECIMAL(10,2) NOT NULL,
        deskripsi TEXT,
        status ENUM('aktif','nonaktif') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        court_id INT NOT NULL,
        tanggal_booking DATE NOT NULL,
        jam_mulai TIME NOT NULL,
        jam_selesai TIME NOT NULL,
        total_harga DECIMAL(10,2) NOT NULL,
        paket ENUM('per_jam','per_match') DEFAULT 'per_jam',
        sewa_raket TINYINT(1) DEFAULT 0,
        catatan TEXT,
        status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        waktu_bayar DATETIME DEFAULT CURRENT_TIMESTAMP,
        jumlah_bayar DECIMAL(10,2) NOT NULL,
        metode_bayar ENUM('Transfer','Cash') NOT NULL,
        bukti_transfer VARCHAR(255),
        status_verifikasi ENUM('menunggu','terverifikasi','ditolak') DEFAULT 'menunggu',
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
];

foreach ($queries as $q) {
    if (!mysqli_query($conn, $q)) {
        $errors[] = "Query gagal: " . mysqli_error($conn);
    } else {
        $success[] = "Tabel berhasil dibuat/diperiksa.";
    }
}

// Cek apakah admin sudah ada
$adminCheck = mysqli_query($conn, "SELECT id FROM users WHERE email='admin@MyPadel.com'");
if (mysqli_num_rows($adminCheck) === 0) {
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role) VALUES (?, ?, ?, ?, 'admin')"
    );
    $adminName = 'Administrator';
    $adminEmail = 'admin@MyPadel.com';
    $adminPhone = '081234567890';
    mysqli_stmt_bind_param($stmt, 'ssss', $adminName, $adminEmail, $adminPass, $adminPhone);
    if (mysqli_stmt_execute($stmt)) {
        $success[] = "Akun admin berhasil dibuat. (Email: admin@MyPadel.com / Password: admin123)";
    } else {
        $errors[] = "Gagal membuat akun admin: " . mysqli_error($conn);
    }
} else {
    $success[] = "Akun admin sudah ada.";
}

// Cek apakah data lapangan sudah ada
$courtCheck = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM courts");
$courtRow = mysqli_fetch_assoc($courtCheck);
if ($courtRow['cnt'] == 0) {
    $lapangan = [
        ['Lapangan A', 'Indoor',  150000, 'Lapangan indoor ber-AC dengan lantai vinyl premium'],
        ['Lapangan B', 'Indoor',  150000, 'Lapangan indoor standar dengan pencahayaan LED'],
        ['Lapangan C', 'Outdoor', 100000, 'Lapangan outdoor dengan view taman yang indah'],
        ['Lapangan D', 'Outdoor', 100000, 'Lapangan outdoor dengan permukaan artificial grass'],
    ];
    foreach ($lapangan as $l) {
        $s = mysqli_prepare($conn,
            "INSERT INTO courts (nama_lapangan, tipe_lapangan, harga_per_jam, deskripsi) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($s, 'ssds', $l[0], $l[1], $l[2], $l[3]);
        mysqli_stmt_execute($s);
    }
    $success[] = "4 lapangan default berhasil ditambahkan.";
} else {
    $success[] = "Data lapangan sudah ada ({$courtRow['cnt']} lapangan).";
}

// Buat folder uploads
if (!is_dir('uploads/bukti_transfer')) {
    mkdir('uploads/bukti_transfer', 0755, true);
    $success[] = "Folder uploads/bukti_transfer berhasil dibuat.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup MyPadel</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 30px; color: #333; }
        .box { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 30px; max-width: 600px; margin: 0 auto; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .ok  { color: #27ae60; margin: 6px 0; }
        .err { color: #e74c3c; margin: 6px 0; }
        .alert { padding: 14px 18px; border-radius: 4px; margin: 16px 0; }
        .alert-success { background: #d5f5e3; border: 1px solid #a9dfbf; color: #1e8449; }
        .alert-danger  { background: #fadbd8; border: 1px solid #f1948a; color: #922b21; }
        a.btn { display:inline-block; background:#3498db; color:#fff; padding:10px 24px; border-radius:4px; text-decoration:none; margin-top:16px; }
        a.btn:hover { background:#2980b9; }
        .warn { background:#fef9e7; border:1px solid #f9e79f; color:#9a7d0a; padding:12px; border-radius:4px; margin-top:16px; font-size:13px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🏸 Setup MyPadel</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Ada kesalahan:</strong>
                <?php foreach ($errors as $e): ?><div class="err">✗ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>Setup berhasil:</strong>
                <?php foreach ($success as $s): ?><div class="ok">✓ <?= htmlspecialchars($s) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($errors)): ?>
            <p>✅ Setup database selesai! Semua tabel dan data awal sudah siap.</p>
            <div class="warn">
                ⚠️ <strong>PENTING:</strong> Hapus atau rename file <code>setup.php</code> ini setelah setup selesai untuk keamanan!
            </div>
            <a href="index.php" class="btn">Buka Aplikasi →</a>
        <?php else: ?>
            <p>❌ Setup tidak selesai. Perbaiki error di atas dan coba lagi.</p>
        <?php endif; ?>
    </div>
</body>
</html>
