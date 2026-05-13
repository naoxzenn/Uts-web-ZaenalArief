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
    <link rel="stylesheet" href="<?= $baseUrl ?? '' ?>assets/style.css">
</head>
<body>

<header class="navbar">
    <a class="brand" href="<?= $baseUrl ?? '' ?>index.php">My<span>Padel</span></a>
    <nav>
        <a href="<?= $baseUrl ?? '' ?>index.php">Beranda</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="<?= $baseUrl ?? '' ?>dashboardadmin.php">Dashboard Admin</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?? '' ?>dashboarduser.php">Dashboard Saya</a>
                <a href="<?= $baseUrl ?? '' ?>booking.php" class="btn-nav">Booking Sekarang</a>
            <?php endif; ?>
            <a href="<?= $baseUrl ?? '' ?>logout.php">Keluar</a>
        <?php else: ?>
            <a href="<?= $baseUrl ?? '' ?>login.php">Masuk</a>
            <a href="<?= $baseUrl ?? '' ?>register.php" class="btn-nav">Daftar</a>
        <?php endif; ?>
    </nav>
</header>
