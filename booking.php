<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}
require_once 'config/koneksi.php';

$pageTitle = 'Booking Lapangan';
$baseUrl = '';
$errors = [];

// Ambil daftar lapangan aktif
$result = mysqli_query($conn, "SELECT * FROM courts WHERE status='aktif' ORDER BY nama_lapangan");
$courts = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Pre-select court dari index.php
$selectedCourt = (int)($_GET['court_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $court_id = (int)($_POST['court_id'] ?? 0);
    $tanggal  = trim($_POST['tanggal_booking'] ?? '');
    $jam_mulai  = trim($_POST['jam_mulai'] ?? '');
    $jam_selesai = trim($_POST['jam_selesai'] ?? '');

    // Validasi
    if (!$court_id) $errors[] = 'Pilih lapangan terlebih dahulu.';
    if (empty($tanggal)) $errors[] = 'Tanggal booking wajib diisi.';
    elseif ($tanggal < date('Y-m-d')) $errors[] = 'Tanggal booking tidak boleh di masa lalu.';
    if (empty($jam_mulai) || empty($jam_selesai)) $errors[] = 'Jam mulai dan jam selesai wajib diisi.';
    elseif ($jam_selesai <= $jam_mulai) $errors[] = 'Jam selesai harus lebih dari jam mulai.';

    if (empty($errors)) {
        // Cek konflik booking
        $stmt = mysqli_prepare($conn,
            "SELECT id FROM bookings 
             WHERE court_id = ? AND tanggal_booking = ? AND status != 'cancelled'
             AND NOT (jam_selesai <= ? OR jam_mulai >= ?)"
        );
        mysqli_stmt_bind_param($stmt, 'isss', $court_id, $tanggal, $jam_mulai, $jam_selesai);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Lapangan sudah dipesan pada waktu tersebut. Pilih waktu lain.';
        } else {
            // Simpan ke session, lanjut ke pilih_paket
            $_SESSION['booking_draft'] = [
                'court_id'   => $court_id,
                'tanggal'    => $tanggal,
                'jam_mulai'  => $jam_mulai,
                'jam_selesai'=> $jam_selesai,
            ];
            header('Location: pilih_paket.php');
            exit;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>Booking Lapangan</h1>
        <p>Isi data booking dan pilih waktu bermain Anda</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="card">
                <h2>Form Booking</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e): ?>
                            <div>• <?= htmlspecialchars($e) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="booking.php" id="form-booking">
                    <div class="form-group">
                        <label for="court_id">Pilih Lapangan</label>
                        <select id="court_id" name="court_id" required>
                            <option value="">-- Pilih Lapangan --</option>
                            <?php foreach ($courts as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?= ((isset($_POST['court_id']) && $_POST['court_id'] == $c['id']) || $selectedCourt == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nama_lapangan']) ?>
                                    (<?= $c['tipe_lapangan'] ?>) -
                                    Rp <?= number_format($c['harga_per_jam'], 0, ',', '.') ?>/jam
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_booking">Tanggal Booking</label>
                        <input type="date" id="tanggal_booking" name="tanggal_booking"
                               value="<?= htmlspecialchars($_POST['tanggal_booking'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="jam_mulai">Jam Mulai</label>
                            <input type="time" id="jam_mulai" name="jam_mulai"
                                   value="<?= htmlspecialchars($_POST['jam_mulai'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="jam_selesai">Jam Selesai</label>
                            <input type="time" id="jam_selesai" name="jam_selesai"
                                   value="<?= htmlspecialchars($_POST['jam_selesai'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div style="margin-top: 8px; display: flex; gap: 10px;">
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary" id="btn-lanjut" style="flex: 1;">
                            Lanjut Pilih Paket &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
