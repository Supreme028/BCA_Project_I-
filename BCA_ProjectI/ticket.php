<?php
// ==============================================================
// ticket.php
// Printable Electronic Ticket / Booking Confirmation Page
// ==============================================================

require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$booking_id = intval($_GET['id'] ?? 0);
$user_id    = $_SESSION['user_id'];

if ($booking_id <= 0) {
    header("Location: index.php");
    exit;
}

// 1. Fetch booking header details
$sql = "
    SELECT 
        b.id AS booking_id, b.booking_number, b.total_amount, b.booking_date, b.status,
        u.fullname AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
        m.title AS movie_title, m.genre, m.language, m.duration,
        s.show_date, s.show_time, s.ticket_price,
        sc.name AS screen_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN screens sc ON s.screen_id = sc.id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$booking = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    die("Ticket not found or you do not have permission to view it. <a href='my-bookings.php'>View My Bookings</a>");
}
mysqli_stmt_close($stmt);

// 2. Fetch individual seats reserved under this booking
$seat_sql = "SELECT seat_number FROM seat_bookings WHERE booking_id = ? ORDER BY seat_number ASC";
$s_stmt = mysqli_prepare($conn, $seat_sql);
mysqli_stmt_bind_param($s_stmt, "i", $booking_id);
mysqli_stmt_execute($s_stmt);
$s_res = mysqli_stmt_get_result($s_stmt);

$seats = [];
while ($s_row = mysqli_fetch_assoc($s_res)) {
    $seats[] = $s_row['seat_number'];
}
mysqli_stmt_close($s_stmt);

$page_title = "Ticket " . $booking['booking_number'] . " - CineBook";
require_once 'includes/header.php';
?>

<div class="container my-4">
    <!-- Success Alert (hidden when printing) -->
    <div class="text-center mb-4 btn-no-print">
        <div class="d-inline-block p-3 rounded-circle bg-success text-white mb-2 shadow">
            <i class="fas fa-check fa-2x"></i>
        </div>
        <h3 class="font-weight-bold text-light">Booking Confirmed!</h3>
        <p class="text-muted">Your seats have been successfully reserved. Please present this ticket at the cinema counter.</p>
        
        <!-- Action Buttons -->
        <button onclick="window.print()" class="btn btn-warning font-weight-bold px-4 py-2 mr-2">
            <i class="fas fa-print mr-1"></i> Print / Save as PDF
        </button>
        <a href="my-bookings.php" class="btn btn-outline-light px-3 py-2 mr-2">
            <i class="fas fa-list mr-1"></i> My Bookings
        </a>
        <a href="index.php" class="btn btn-outline-secondary px-3 py-2">
            Book Another
        </a>
    </div>

    <!-- Printable Cinema Boarding-Pass Style Ticket -->
    <div class="ticket-container">
        <!-- Ticket Header -->
        <div class="ticket-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold text-warning mb-0">
                    <i class="fas fa-film mr-2"></i>CineBook
                </h4>
                <small class="text-muted text-uppercase tracking-wider">Electronic Admission Pass</small>
            </div>
            <div class="text-right">
                <span class="badge badge-success px-2 py-1 text-uppercase">
                    <?php echo htmlspecialchars($booking['status']); ?>
                </span>
                <div class="small text-muted mt-1">
                    Ref: <strong class="text-light"><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                </div>
            </div>
        </div>

        <!-- Ticket Body Details -->
        <div class="ticket-body">
            <div class="row mb-3">
                <div class="col-8">
                    <div class="text-muted small text-uppercase">Movie</div>
                    <h3 class="font-weight-bold text-dark mb-0">
                        <?php echo htmlspecialchars($booking['movie_title']); ?>
                    </h3>
                    <small class="text-muted">
                        <?php echo htmlspecialchars($booking['genre']); ?> &bull; 
                        <?php echo htmlspecialchars($booking['language']); ?> &bull; 
                        <?php echo (int)$booking['duration']; ?> mins
                    </small>
                </div>
                <div class="col-4 text-right">
                    <div class="text-muted small text-uppercase">Cinema Hall</div>
                    <h5 class="font-weight-bold text-primary mb-0">
                        <?php echo htmlspecialchars($booking['screen_name']); ?>
                    </h5>
                </div>
            </div>

            <hr class="ticket-divider">

            <div class="row mb-4">
                <div class="col-4">
                    <div class="text-muted small text-uppercase">Date</div>
                    <div class="font-weight-bold text-dark" style="font-size: 1.1rem;">
                        <?php echo date('d M Y', strtotime($booking['show_date'])); ?>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div class="text-muted small text-uppercase">Showtime</div>
                    <div class="font-weight-bold text-dark" style="font-size: 1.1rem;">
                        <?php echo date('h:i A', strtotime($booking['show_time'])); ?>
                    </div>
                </div>
                <div class="col-4 text-right">
                    <div class="text-muted small text-uppercase">Reserved Seats</div>
                    <div class="font-weight-bold text-success" style="font-size: 1.1rem;">
                        <?php echo htmlspecialchars(implode(', ', $seats)); ?>
                    </div>
                </div>
            </div>

            <div class="bg-light p-3 rounded mb-3 border">
                <div class="row align-items-center">
                    <div class="col-6">
                        <div class="small text-muted">Customer: <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong></div>
                        <div class="small text-muted">Phone: <?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                        <div class="small text-muted">Booked: <?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?></div>
                    </div>
                    <div class="col-6 text-right">
                        <div class="text-muted small">Total Amount Paid</div>
                        <div class="h4 font-weight-bold text-dark mb-0">
                            Rs. <?php echo number_format($booking['total_amount'], 2); ?>
                        </div>
                        <small class="text-muted">(<?php echo count($seats); ?> seat(s) &times; Rs. <?php echo number_format($booking['ticket_price'], 2); ?>)</small>
                    </div>
                </div>
            </div>

            <!-- Stylized Barcode Graphic -->
            <div class="text-center pt-2">
                <div style="letter-spacing: 5px; font-family: 'Courier New', monospace; font-weight: bold; color: #64748b;">
                    ||||||| | ||||| || |||||| | |||||||| ||| |||||
                </div>
                <small class="text-muted"><?php echo htmlspecialchars($booking['booking_number']); ?></small>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
