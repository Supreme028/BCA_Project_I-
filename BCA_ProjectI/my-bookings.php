<?php
// ==============================================================
// my-bookings.php
// Customer's Personal Booking History
// ==============================================================

require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all bookings for the current user, joined with movies, showtimes, and grouped seats
$sql = "
    SELECT 
        b.id AS booking_id, b.booking_number, b.total_amount, b.booking_date, b.status,
        m.title AS movie_title, m.poster,
        s.show_date, s.show_time,
        sc.name AS screen_name,
        GROUP_CONCAT(sb.seat_number ORDER BY sb.seat_number ASC SEPARATOR ', ') AS seats
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN screens sc ON s.screen_id = sc.id
    LEFT JOIN seat_bookings sb ON b.id = sb.booking_id
    WHERE b.user_id = ?
    GROUP BY b.id
    ORDER BY b.id DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$page_title = "My Bookings - CineBook";
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-weight-bold text-light mb-1">
                <i class="fas fa-ticket-alt text-warning mr-2"></i>My Bookings
            </h3>
            <p class="text-muted small mb-0">View your ticket reservations and print copies anytime</p>
        </div>
        <a href="index.php" class="btn btn-warning btn-sm font-weight-bold">
            <i class="fas fa-plus mr-1"></i> Book More
        </a>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>REF #</th>
                            <th>MOVIE</th>
                            <th>SHOWTIME</th>
                            <th>SCREEN</th>
                            <th>SEATS</th>
                            <th>AMOUNT</th>
                            <th>STATUS</th>
                            <th class="text-right">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="font-weight-bold text-warning align-middle">
                                    <?php echo htmlspecialchars($row['booking_number']); ?>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        <?php echo date('d M Y', strtotime($row['booking_date'])); ?>
                                    </div>
                                </td>
                                <td class="font-weight-bold text-light align-middle">
                                    <?php echo htmlspecialchars($row['movie_title']); ?>
                                </td>
                                <td class="align-middle">
                                    <div><?php echo date('d M Y', strtotime($row['show_date'])); ?></div>
                                    <div class="small text-muted"><?php echo date('h:i A', strtotime($row['show_time'])); ?></div>
                                </td>
                                <td class="align-middle text-muted small">
                                    <?php echo htmlspecialchars($row['screen_name']); ?>
                                </td>
                                <td class="align-middle font-weight-bold text-success">
                                    <?php echo htmlspecialchars($row['seats'] ?? 'N/A'); ?>
                                </td>
                                <td class="align-middle font-weight-bold text-light">
                                    Rs. <?php echo number_format($row['total_amount'], 2); ?>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-success px-2 py-1 text-uppercase">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="align-middle text-right">
                                    <a href="ticket.php?id=<?php echo $row['booking_id']; ?>" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-eye mr-1"></i> View Ticket
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-5 bg-dark rounded border border-secondary">
            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
            <h4 class="text-light">No bookings yet</h4>
            <p class="text-muted">You haven't booked any movie tickets yet. Pick a movie and reserve your favorite seats now!</p>
            <a href="index.php" class="btn btn-warning font-weight-bold px-4">Browse Movies</a>
        </div>
    <?php endif; ?>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once 'includes/footer.php'; 
?>
