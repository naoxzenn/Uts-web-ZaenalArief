<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}
require_once 'config/koneksi.php';

$pageTitle = 'Rincian Pembayaran';
$baseUrl = '';
$errors = [];
$success = '';

$booking_id = (int) ($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    header('Location: dashboarduser.php');
    exit;
}

// Ambil data booking + lapangan
$stmt = mysqli_prepare(
    $conn,
    "SELECT b.*, c.nama_lapangan, c.tipe_lapangan, c.harga_per_jam, u.nama_lengkap, u.email
     FROM bookings b
     JOIN courts c ON b.court_id = c.id
     JOIN users u ON b.user_id = u.id
     WHERE b.id = ? AND b.user_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$booking) {
    header('Location: dashboarduser.php');
    exit;
}

// Cek apakah sudah ada pembayaran
$stmtP = mysqli_prepare($conn, "SELECT * FROM payments WHERE booking_id = ?");
mysqli_stmt_bind_param($stmtP, 'i', $booking_id);
mysqli_stmt_execute($stmtP);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtP));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$payment) {
    $metode = $_POST['metode_bayar'] ?? '';
    $jumlah = (float) ($booking['total_harga']);
    $bukti = '';

    if (empty($metode)) {
        $errors[] = 'Pilih metode pembayaran.';
    }

    if ($metode === 'Transfer') {
        // Upload bukti transfer
        if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Bukti transfer wajib diupload untuk metode Transfer.';
        } else {
            $file = $_FILES['bukti_transfer'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Format file bukti tidak valid. Gunakan JPG, PNG, atau PDF.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 2MB.';
            } else {
                $uploadDir = 'uploads/bukti_transfer/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0755, true);
                $newName = 'bukti_' . $booking_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $bukti = $newName;
                } else {
                    $errors[] = 'Gagal mengupload file. Coba lagi.';
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt2 = mysqli_prepare(
            $conn,
            "INSERT INTO payments (booking_id, jumlah_bayar, metode_bayar, bukti_transfer)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt2, 'idss', $booking_id, $jumlah, $metode, $bukti);
        if (mysqli_stmt_execute($stmt2)) {
            // Update status booking ke confirmed jika cash
            if ($metode === 'Cash') {
                mysqli_query($conn, "UPDATE bookings SET status='confirmed' WHERE id=$booking_id");
            }
            $success = 'Pembayaran berhasil dicatat!';
            // Refresh data
            mysqli_stmt_execute($stmtP);
            $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtP));
        } else {
            $errors[] = 'Gagal menyimpan pembayaran.';
        }
    }
}

$durasi = (strtotime($booking['jam_selesai']) - strtotime($booking['jam_mulai'])) / 3600;
?>
<?php include 'includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>Rincian Pembayaran</h1>
        <p>Booking #<?= $booking_id ?> – <?= htmlspecialchars($booking['nama_lapangan']) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 680px; margin: 0 auto;">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Detail Booking -->
            <div class="card">
                <h2>Detail Booking</h2>
                <div class="detail-box">
                    <div class="detail-row">
                        <span class="label">ID Booking</span>
                        <span class="value">#<?= $booking['id'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Nama</span>
                        <span class="value"><?= htmlspecialchars($booking['nama_lengkap']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Lapangan</span>
                        <span class="value"><?= htmlspecialchars($booking['nama_lapangan']) ?>
                            (<?= $booking['tipe_lapangan'] ?>)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Tanggal</span>
                        <span class="value"><?= date('d F Y', strtotime($booking['tanggal_booking'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Jam</span>
                        <span class="value"><?= $booking['jam_mulai'] ?> – <?= $booking['jam_selesai'] ?>
                            (<?= $durasi ?> jam)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Paket</span>
                        <span class="value"><?= $booking['paket'] === 'per_jam' ? 'Per Jam' : 'Per Match' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Sewa Raket</span>
                        <span class="value"><?= $booking['sewa_raket'] ? 'Ya (+Rp 50.000)' : 'Tidak' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status Booking</span>
                        <span class="value">
                            <span class="status-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
                        </span>
                    </div>
                    <?php if ($booking['catatan']): ?>
                        <div class="detail-row">
                            <span class="label">Catatan</span>
                            <span class="value"><?= htmlspecialchars($booking['catatan']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row total">
                        <span class="label">Total Pembayaran</span>
                        <span class="value">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Status Pembayaran -->
            <?php if ($payment): ?>
                <div class="card">
                    <h2>Status Pembayaran</h2>
                    <div class="detail-box">
                        <div class="detail-row">
                            <span class="label">Metode</span>
                            <span class="value"><?= $payment['metode_bayar'] ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Jumlah Dibayar</span>
                            <span class="value">Rp <?= number_format($payment['jumlah_bayar'], 0, ',', '.') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Waktu Bayar</span>
                            <span class="value"><?= date('d F Y H:i', strtotime($payment['waktu_bayar'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Verifikasi</span>
                            <span class="value">
                                <?php
                                $sv = $payment['status_verifikasi'];
                                $cls = $sv === 'terverifikasi' ? 'confirmed' : ($sv === 'ditolak' ? 'cancelled' : 'pending');
                                ?>
                                <span class="status-<?= $cls ?>"><?= ucfirst($sv) ?></span>
                            </span>
                        </div>
                        <?php if ($payment['bukti_transfer']): ?>
                            <div class="detail-row">
                                <span class="label">Bukti Transfer</span>
                                <span class="value">
                                    <a href="uploads/bukti_transfer/<?= htmlspecialchars($payment['bukti_transfer']) ?>"
                                        target="_blank" class="btn btn-sm btn-secondary">Lihat Bukti</a>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-info" style="margin-top: 12px;">
                        Pembayaran Anda sedang dalam proses verifikasi admin. Terima kasih!
                    </div>
                </div>
            <?php else: ?>
                <!-- Form Upload Pembayaran -->
                <div class="card">
                    <h2>Konfirmasi Pembayaran</h2>
                    <form method="POST" action="rincian_pembayaran.php?booking_id=<?= $booking_id ?>"
                        enctype="multipart/form-data" id="form-payment">

                        <div class="form-group">
                            <label for="metode_bayar">Metode Pembayaran</label>
                            <select id="metode_bayar" name="metode_bayar" required onchange="toggleBukti(this.value)">
                                <option value="">-- Pilih Metode --</option>
                                <option value="Transfer" <?= ($_POST['metode_bayar'] ?? '') === 'Transfer' ? 'selected' : '' ?>>Qris</option>
                                <option value="Cash" <?= ($_POST['metode_bayar'] ?? '') === 'Cash' ? 'selected' : '' ?>>Cash
                                    (Bayar di Tempat)</option>
                            </select>
                        </div>

                        <div id="area-bukti"
                            style="display: <?= ($_POST['metode_bayar'] ?? '') === 'Transfer' ? 'block' : 'none' ?>;">
                            <div class="upload-area" style="text-align: center;">
                                <div
                                    style="display: inline-block; padding: 10px; background: #fff; border: 1px solid #eee; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
                                    <img src="assets/qris.jpg" alt="QRIS Merchant"
                                        style="width: 250px; height: auto; border-radius: 5px;">
                                </div>

                                <p style="font-size: 12px; color: #888; margin-top: 10px;">*Pastikan nama merchant sesuai
                                    sebelum membayar</p>
                            </div>

                            <div style="border-top: 2px dashed #eee; margin: 25px 0;"></div>

                            <div class="form-group">
                                <label for="bukti_transfer">Upload Bukti Transfer</label>
                                <div class="upload-area">
                                    <input type="file" id="bukti_transfer" name="bukti_transfer"
                                        accept=".jpg,.jpeg,.png,.pdf">
                                    <p style="margin-top: 8px;">Format: JPG, PNG, PDF – Maks. 2MB</p>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <a href="dashboarduser.php" class="btn btn-secondary">Nanti Saja</a>
                            <button type="submit" class="btn btn-success" id="btn-bayar" style="flex: 1;">
                                Konfirmasi Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 10px;">
                <a href="dashboarduser.php" class="btn btn-secondary">Lihat Riwayat Booking</a>
            </div>
        </div>
    </div>
</section>

<script>
    function toggleBukti(val) {
        document.getElementById('area-bukti').style.display = val === 'Transfer' ? 'block' : 'none';
    }
</script>

<?php include 'includes/footer.php'; ?>