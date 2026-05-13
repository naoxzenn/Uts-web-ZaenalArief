<?php
session_start();
require_once 'config/koneksi.php';

$pageTitle = 'Beranda';
$baseUrl = '';

$result = mysqli_query($conn, "SELECT * FROM courts WHERE status='aktif' ORDER BY tipe_lapangan, nama_lapangan");
$courts = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<?php include 'includes/header.php'; ?>

<section class="page-header">               
    <div class="container">
        <h1>Selamat Datang di MyPadel</h1>
        <p>Booking lapangan padel indoor &amp; outdoor dengan mudah dan cepat</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'logout'): ?>
                <div class="alert alert-success">Anda berhasil keluar. Sampai jumpa!</div>
            <?php elseif ($_GET['msg'] === 'cancelled'): ?>
                <div class="alert alert-warning">Booking Anda telah dibatalkan.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Info singkat -->
        <div class="summary-grid" style="margin-bottom: 28px;">
            <div class="summary-box">
                <div class="number"><?= count($courts) ?></div>
                <div class="label">Lapangan Tersedia</div>
            </div>
            <div class="summary-box">
                <div class="number">Rp 100rb</div>
                <div class="label">Harga Mulai Dari</div>
            </div>
            <div class="summary-box">
                <div class="number">Indoor &amp; Outdoor</div>
                <div class="label">Tipe Lapangan</div>
            </div>
            <div class="summary-box">
                <div class="number">24/7</div>
                <div class="label">Booking Online</div>
            </div>
        </div>

        <!-- Daftar Lapangan -->
        <div class="card">
            <h2>Pilih Lapangan Anda</h2>

            <?php if (empty($courts)): ?>
                <div class="alert alert-info">Belum ada lapangan tersedia saat ini.</div>
            <?php else: ?>
                <div class="court-grid">
                    <?php foreach ($courts as $court): ?>
                        <div class="court-card">
                            <div class="court-card-header">
                                <h3><?= htmlspecialchars($court['nama_lapangan']) ?></h3>
                                <span class="badge badge-<?= strtolower($court['tipe_lapangan']) ?>">
                                    <?= $court['tipe_lapangan'] ?>
                                </span>
                            </div>
                            <div class="court-card-body">
                                <div class="court-price">
                                    Rp <?= number_format($court['harga_per_jam'], 0, ',', '.') ?>
                                    <small>/jam</small>
                                </div>
                                <p class="court-desc"><?= htmlspecialchars($court['deskripsi'] ?? '-') ?></p>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                                    <a href="booking.php?court_id=<?= $court['id'] ?>" class="btn btn-primary btn-block">
                                        Booking Sekarang
                                    </a>
                                <?php elseif (!isset($_SESSION['user_id'])): ?>
                                    <a href="login.php" class="btn btn-secondary btn-block">
                                        Login untuk Booking
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-secondary btn-block" style="cursor:default;">
                                        Mode Admin
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cara Booking -->
        <div class="card">
            <h2>Cara Booking</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; text-align: center;">
                <div style="padding: 16px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">1️⃣</div>
                    <strong style="display:block; margin-bottom: 4px;">Daftar / Login</strong>
                    <p style="font-size: 13px; color: #666;">Buat akun atau login ke sistem</p>
                </div>
                <div style="padding: 16px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">2️⃣</div>
                    <strong style="display:block; margin-bottom: 4px;">Pilih Lapangan</strong>
                    <p style="font-size: 13px; color: #666;">Pilih lapangan yang tersedia</p>
                </div>
                <div style="padding: 16px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">3️⃣</div>
                    <strong style="display:block; margin-bottom: 4px;">Pilih Paket</strong>
                    <p style="font-size: 13px; color: #666;">Tentukan waktu dan paket bermain</p>
                </div>
                <div style="padding: 16px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">4️⃣</div>
                    <strong style="display:block; margin-bottom: 4px;">Bayar</strong>
                    <p style="font-size: 13px; color: #666;">Transfer atau bayar tunai di tempat</p>
                </div>
            </div>
        </div>

    </div>
</section>

<?php include 'includes/footer.php'; ?>
