<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'config/koneksi.php';

$pageTitle = 'Dashboard Admin';
$baseUrl = '';
$msg = '';

// ---- AKSI ----
// Update status booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $bid    = (int)$_POST['booking_id'];
        $status = $_POST['status'];
        $allowed = ['pending', 'confirmed', 'cancelled'];
        if (in_array($status, $allowed)) {
            $s = mysqli_prepare($conn, "UPDATE bookings SET status=? WHERE id=?");
            mysqli_stmt_bind_param($s, 'si', $status, $bid);
            mysqli_stmt_execute($s);
            $msg = 'Status booking #' . $bid . ' berhasil diubah ke ' . $status . '.';
        }
    } elseif ($_POST['action'] === 'verifikasi_payment') {
        $pid = (int)$_POST['payment_id'];
        $sv  = $_POST['status_verifikasi'];
        $allowed_sv = ['menunggu', 'terverifikasi', 'ditolak'];
        if (in_array($sv, $allowed_sv)) {
            $s = mysqli_prepare($conn, "UPDATE payments SET status_verifikasi=? WHERE id=?");
            mysqli_stmt_bind_param($s, 'si', $sv, $pid);
            mysqli_stmt_execute($s);
            // Auto confirm booking jika terverifikasi
            if ($sv === 'terverifikasi') {
                $bp = mysqli_query($conn, "SELECT booking_id FROM payments WHERE id=$pid");
                $bp = mysqli_fetch_assoc($bp);
                if ($bp) {
                    mysqli_query($conn, "UPDATE bookings SET status='confirmed' WHERE id={$bp['booking_id']}");
                }
            }
            $msg = 'Verifikasi pembayaran #' . $pid . ' berhasil diperbarui.';
        }
    } elseif ($_POST['action'] === 'tambah_lapangan') {
        $nama  = trim($_POST['nama_lapangan']);
        $tipe  = $_POST['tipe_lapangan'];
        $harga = (float)$_POST['harga_per_jam'];
        $desk  = trim($_POST['deskripsi']);
        if ($nama && in_array($tipe, ['Indoor', 'Outdoor']) && $harga > 0) {
            $s = mysqli_prepare($conn,
                "INSERT INTO courts (nama_lapangan, tipe_lapangan, harga_per_jam, deskripsi) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($s, 'ssds', $nama, $tipe, $harga, $desk);
            mysqli_stmt_execute($s);
            $msg = 'Lapangan baru berhasil ditambahkan.';
        }
    } elseif ($_POST['action'] === 'toggle_court') {
        $cid = (int)$_POST['court_id'];
        $cs  = $_POST['status'];
        $s = mysqli_prepare($conn, "UPDATE courts SET status=? WHERE id=?");
        mysqli_stmt_bind_param($s, 'si', $cs, $cid);
        mysqli_stmt_execute($s);
        $msg = 'Status lapangan berhasil diubah.';
    }
}

// ---- AMBIL DATA ----
// Statistik
$totalBooking    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings"))[0];
$totalConfirmed  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status='confirmed'"))[0];
$totalPending    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status='pending'"))[0];
$totalCancelled  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status='cancelled'"))[0];
$totalPendapatan = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total_harga),0) FROM bookings b JOIN payments p ON p.booking_id=b.id WHERE p.status_verifikasi='terverifikasi'"))[0];
$totalUsers      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='customer'"))[0];

// Semua Booking
$bookings = mysqli_fetch_all(mysqli_query($conn,
    "SELECT b.*, c.nama_lapangan, u.nama_lengkap, u.email, u.nomor_telepon,
            p.metode_bayar, p.status_verifikasi, p.id AS payment_id, p.bukti_transfer
     FROM bookings b
     JOIN courts c ON b.court_id = c.id
     JOIN users u ON b.user_id = u.id
     LEFT JOIN payments p ON p.booking_id = b.id
     ORDER BY b.created_at DESC
"), MYSQLI_ASSOC);

// Semua Lapangan
$courts = mysqli_fetch_all(mysqli_query($conn,
    "SELECT * FROM courts ORDER BY nama_lapangan"), MYSQLI_ASSOC);

// Semua User
$users = mysqli_fetch_all(mysqli_query($conn,
    "SELECT u.*, COUNT(b.id) AS total_booking FROM users u
     LEFT JOIN bookings b ON b.user_id = u.id
     WHERE u.role = 'customer'
     GROUP BY u.id ORDER BY u.created_at DESC"
), MYSQLI_ASSOC);
?>
<?php include 'includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>Dashboard Administrator</h1>
        <p>Kelola seluruh data booking, pembayaran, lapangan, dan pengguna</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="summary-grid">
            <div class="summary-box">
                <div class="number"><?= $totalBooking ?></div>
                <div class="label">Total Booking</div>
            </div>
            <div class="summary-box">
                <div class="number"><?= $totalConfirmed ?></div>
                <div class="label">Confirmed</div>
            </div>
            <div class="summary-box">
                <div class="number"><?= $totalPending ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="summary-box">
                <div class="number"><?= $totalCancelled ?></div>
                <div class="label">Cancelled</div>
            </div>
            <div class="summary-box">
                <div class="number" style="font-size:18px;">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></div>
                <div class="label">Pendapatan Verified</div>
            </div>
            <div class="summary-box">
                <div class="number"><?= $totalUsers ?></div>
                <div class="label">Total Customer</div>
            </div>
        </div>

        <!-- TABS -->
        <div class="tabs" id="admin-tabs">
            <button class="tab-btn active" onclick="showTab('tab-booking', this)">Booking</button>
            <button class="tab-btn" onclick="showTab('tab-payment', this)">Pembayaran</button>
            <button class="tab-btn" onclick="showTab('tab-lapangan', this)">Lapangan</button>
            <button class="tab-btn" onclick="showTab('tab-users', this)">Pengguna</button>
        </div>

        <!-- TAB: BOOKING -->
        <div id="tab-booking" class="tab-content active">
            <div class="card">
                <h2>Semua Data Booking</h2>
                <div class="table-responsive">
                    <table id="tabel-booking">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Ubah Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $i => $b): ?>
                                <tr>
                                    <td><?= $b['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['nama_lengkap']) ?></strong><br>
                                        <small><?= htmlspecialchars($b['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($b['nama_lapangan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?></td>
                                    <td><?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?></td>
                                    <td>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                                    <td><span class="status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                                    <td>
                                        <form method="POST" style="display:flex; gap:4px; align-items:center;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <select name="status" style="padding:4px 6px; font-size:13px; border:1px solid #ccc; border-radius:3px;">
                                                <option value="pending" <?= $b['status']==='pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="confirmed" <?= $b['status']==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="cancelled" <?= $b['status']==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Ubah</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PEMBAYARAN -->
        <div id="tab-payment" class="tab-content">
            <div class="card">
                <h2>Data Pembayaran</h2>
                <div class="table-responsive">
                    <table id="tabel-payment">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Lapangan</th>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Bukti</th>
                                <th>Status Verif</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $hasPay = array_filter($bookings, fn($b) => $b['payment_id']);
                            foreach ($hasPay as $b):
                                $sv = $b['status_verifikasi'];
                                $sc = $sv === 'terverifikasi' ? 'confirmed' : ($sv === 'ditolak' ? 'cancelled' : 'pending');
                            ?>
                                <tr>
                                    <td>#<?= $b['id'] ?></td>
                                    <td><?= htmlspecialchars($b['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($b['nama_lapangan']) ?></td>
                                    <td><?= $b['metode_bayar'] ?></td>
                                    <td>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($b['bukti_transfer']): ?>
                                            <a href="uploads/bukti_transfer/<?= htmlspecialchars($b['bukti_transfer']) ?>"
                                               target="_blank" class="btn btn-sm btn-secondary">Lihat</a>
                                        <?php else: ?>
                                            <span style="color:#aaa;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status-<?= $sc ?>"><?= ucfirst($sv) ?></span></td>
                                    <td>
                                        <form method="POST" style="display:flex; gap:4px; align-items:center;">
                                            <input type="hidden" name="action" value="verifikasi_payment">
                                            <input type="hidden" name="payment_id" value="<?= $b['payment_id'] ?>">
                                            <select name="status_verifikasi" style="padding:4px 6px; font-size:13px; border:1px solid #ccc; border-radius:3px;">
                                                <option value="menunggu" <?= $sv==='menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                                <option value="terverifikasi" <?= $sv==='terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                                                <option value="ditolak" <?= $sv==='ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-success">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($hasPay)): ?>
                                <tr><td colspan="8" style="text-align:center; color:#aaa; padding:16px;">Belum ada data pembayaran.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: LAPANGAN -->
        <div id="tab-lapangan" class="tab-content">
            <!-- Form Tambah Lapangan -->
            <div class="card">
                <h2>Tambah Lapangan Baru</h2>
                <form method="POST" id="form-tambah-lapangan">
                    <input type="hidden" name="action" value="tambah_lapangan">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama_lapangan">Nama Lapangan</label>
                            <input type="text" id="nama_lapangan" name="nama_lapangan"
                                   placeholder="Contoh: Lapangan E" required>
                        </div>
                        <div class="form-group">
                            <label for="tipe_lapangan">Tipe</label>
                            <select id="tipe_lapangan" name="tipe_lapangan" required>
                                <option value="Indoor">Indoor</option>
                                <option value="Outdoor">Outdoor</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="harga_per_jam">Harga per Jam (Rp)</label>
                            <input type="number" id="harga_per_jam" name="harga_per_jam"
                                   placeholder="150000" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>
                            <input type="text" id="deskripsi" name="deskripsi" placeholder="Deskripsi singkat">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" id="btn-tambah-lapangan">+ Tambah Lapangan</button>
                </form>
            </div>

            <!-- Daftar Lapangan -->
            <div class="card">
                <h2>Daftar Lapangan</h2>
                <div class="table-responsive">
                    <table id="tabel-lapangan">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Lapangan</th>
                                <th>Tipe</th>
                                <th>Harga/Jam</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courts as $c): ?>
                                <tr>
                                    <td><?= $c['id'] ?></td>
                                    <td><?= htmlspecialchars($c['nama_lapangan']) ?></td>
                                    <td><span class="badge badge-<?= strtolower($c['tipe_lapangan']) ?>"><?= $c['tipe_lapangan'] ?></span></td>
                                    <td>Rp <?= number_format($c['harga_per_jam'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($c['deskripsi'] ?? '-') ?></td>
                                    <td>
                                        <span class="<?= $c['status']==='aktif' ? 'status-confirmed' : 'status-cancelled' ?>">
                                            <?= ucfirst($c['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_court">
                                            <input type="hidden" name="court_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="status"
                                                   value="<?= $c['status']==='aktif' ? 'nonaktif' : 'aktif' ?>">
                                            <button type="submit" class="btn btn-sm <?= $c['status']==='aktif' ? 'btn-warning' : 'btn-success' ?>">
                                                <?= $c['status']==='aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PENGGUNA -->
        <div id="tab-users" class="tab-content">
            <div class="card">
                <h2>Daftar Pengguna (Customer)</h2>
                <div class="table-responsive">
                    <table id="tabel-users">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Total Booking</th>
                                <th>Bergabung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= htmlspecialchars($u['nomor_telepon'] ?? '-') ?></td>
                                    <td><?= $u['total_booking'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="6" style="text-align:center; color:#aaa;">Belum ada customer terdaftar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
function showTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
