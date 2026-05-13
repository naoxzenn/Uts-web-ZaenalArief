<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['booking_draft'])) {
    header('Location: booking.php');
    exit;
}
require_once 'config/koneksi.php';

$pageTitle = 'Pilih Paket';
$baseUrl = '';
$draft = $_SESSION['booking_draft'];
$errors = [];

// Ambil detail lapangan
$stmt = mysqli_prepare($conn, "SELECT * FROM courts WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $draft['court_id']);
mysqli_stmt_execute($stmt);
$court = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$court) {
    header('Location: booking.php');
    exit;
}

// Hitung durasi (jam)
$mulai  = strtotime($draft['jam_mulai']);
$selesai = strtotime($draft['jam_selesai']);
$durasi_jam = ($selesai - $mulai) / 3600;

// Harga per_match tetap
define('HARGA_PER_MATCH', 250000);
define('HARGA_SEWA_RAKET', 50000);

$total = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paket       = $_POST['paket'] ?? 'per_jam';
    $sewa_raket  = isset($_POST['sewa_raket']) ? 1 : 0;
    $catatan     = trim($_POST['catatan'] ?? '');

    // Hitung total
    if ($paket === 'per_jam') {
        $total = $court['harga_per_jam'] * $durasi_jam;
    } else {
        $total = HARGA_PER_MATCH;
    }
    if ($sewa_raket) $total += HARGA_SEWA_RAKET;

    // Simpan booking ke database
    $user_id  = $_SESSION['user_id'];
    $stmt2 = mysqli_prepare($conn,
        "INSERT INTO bookings (user_id, court_id, tanggal_booking, jam_mulai, jam_selesai, total_harga, paket, sewa_raket, catatan, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    mysqli_stmt_bind_param($stmt2, 'iisssdiss',
        $user_id, $draft['court_id'],
        $draft['tanggal'], $draft['jam_mulai'], $draft['jam_selesai'],
        $total, $paket, $sewa_raket, $catatan
    );

    if (mysqli_stmt_execute($stmt2)) {
        $booking_id = mysqli_insert_id($conn);
        $_SESSION['booking_draft'] = null;
        unset($_SESSION['booking_draft']);
        header("Location: rincian_pembayaran.php?booking_id=$booking_id");
        exit;
    } else {
        $errors[] = 'Gagal menyimpan booking. Coba lagi.';
    }
}

// Preview harga
$paket_preview = $_POST['paket'] ?? 'per_jam';
$raket_preview = isset($_POST['sewa_raket']) ? 1 : 0;
if ($paket_preview === 'per_jam') {
    $total_preview = $court['harga_per_jam'] * $durasi_jam;
} else {
    $total_preview = HARGA_PER_MATCH;
}
if ($raket_preview) $total_preview += HARGA_SEWA_RAKET;
?>
<?php include 'includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>Pilih Paket Bermain</h1>
        <p>Tentukan paket dan opsi tambahan sesuai kebutuhan Anda</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 680px; margin: 0 auto;">

            <!-- Ringkasan booking -->
            <div class="card">
                <h2>Ringkasan Booking</h2>
                <div class="detail-box">
                    <div class="detail-row">
                        <span class="label">Lapangan</span>
                        <span class="value"><?= htmlspecialchars($court['nama_lapangan']) ?> (<?= $court['tipe_lapangan'] ?>)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Tanggal</span>
                        <span class="value"><?= date('d F Y', strtotime($draft['tanggal'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Jam</span>
                        <span class="value"><?= $draft['jam_mulai'] ?> – <?= $draft['jam_selesai'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Durasi</span>
                        <span class="value"><?= $durasi_jam ?> jam</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Harga/Jam</span>
                        <span class="value">Rp <?= number_format($court['harga_per_jam'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div>• <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Form Paket -->
            <div class="card">
                <h2>Pilih Paket &amp; Tambahan</h2>
                <form method="POST" action="pilih_paket.php" id="form-paket">

                    <div class="form-group">
                        <label for="paket">Jenis Paket</label>
                        <select id="paket" name="paket" onchange="hitungTotal()" required>
                            <option value="per_jam" <?= ($_POST['paket'] ?? '') !== 'per_match' ? 'selected' : '' ?>>
                                Per Jam – Rp <?= number_format($court['harga_per_jam'], 0, ',', '.') ?>/jam
                                (Total: Rp <?= number_format($court['harga_per_jam'] * $durasi_jam, 0, ',', '.') ?>)
                            </option>
                            <option value="per_match" <?= ($_POST['paket'] ?? '') === 'per_match' ? 'selected' : '' ?>>
                                Per Match – Harga tetap Rp <?= number_format(HARGA_PER_MATCH, 0, ',', '.') ?>
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="sewa_raket" name="sewa_raket" value="1"
                                   onchange="hitungTotal()"
                                   <?= isset($_POST['sewa_raket']) ? 'checked' : '' ?>>
                            Sewa Raket (+Rp <?= number_format(HARGA_SEWA_RAKET, 0, ',', '.') ?>)
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="catatan">Catatan (opsional)</label>
                        <textarea id="catatan" name="catatan" rows="3"
                                  placeholder="Contoh: minta bola ekstra, dll."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                    </div>

                    <!-- Total Harga Preview -->
                    <div class="detail-box" id="preview-total">
                        <div class="detail-row total">
                            <span class="label">Estimasi Total</span>
                            <span class="value" id="total-display">
                                Rp <?= number_format($total_preview, 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 8px;">
                        <a href="booking.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary" id="btn-konfirmasi" style="flex: 1;">
                            Konfirmasi Booking &rarr;
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>

<script>
const hargaPerJam    = <?= $court['harga_per_jam'] ?>;
const durasiJam      = <?= $durasi_jam ?>;
const hargaPerMatch  = <?= HARGA_PER_MATCH ?>;
const hargaRaket     = <?= HARGA_SEWA_RAKET ?>;

function hitungTotal() {
    const paket = document.getElementById('paket').value;
    const raket = document.getElementById('sewa_raket').checked;

    let total = paket === 'per_jam' ? hargaPerJam * durasiJam : hargaPerMatch;
    if (raket) total += hargaRaket;

    document.getElementById('total-display').innerText = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

<?php include 'includes/footer.php'; ?>
