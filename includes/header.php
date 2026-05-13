<?php
if (!isset($pageTitle)) $pageTitle = 'MyPadel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - MyPadel</title>
    <meta name="description" content="Sistem booking lapangan padel online terpercaya. Pesan lapangan padel indoor dan outdoor dengan mudah.">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="navbar">
    <a class="brand" href="index.php">My<span>Padel</span></a>
    <nav>
        <a href="index.php">Beranda</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="dashboardadmin.php">Dashboard Admin</a>
            <?php else: ?>
                <a href="dashboarduser.php">Dashboard Saya</a>
                <a href="booking.php" class="btn-nav">Booking Sekarang</a>
            <?php endif; ?>
            <a href="logout.php">Keluar</a>
        <?php else: ?>
            <a href="login.php">Masuk</a>
            <a href="register.php" class="btn-nav">Daftar</a>
        <?php endif; ?>
    </nav>
</header>
