<?php
// ==============================================================
// admin/dashboard.php
// Administrator Executive Overview & Analytics Dashboard
// ==============================================================

$admin_page_title = "Dashboard - CineBook Admin";
require_once __DIR__ . '/includes/header.php';

// 1. Calculate Total Revenue
$rev_query = mysqli_query($conn, "SELECT SUM(total_amount) AS total_revenue FROM bookings WHERE status = 'confirmed'");
$rev_row   = mysqli_fetch_assoc($rev_query);
$total_revenue = (float)($rev_row['total_revenue'] ?? 0);

// 2. Count Total Bookings
$book_query = mysqli_query($conn, "SELECT COUNT(id) AS total_bookings FROM bookings");
$book_row   = mysqli_fetch_assoc($book_query);
$total_bookings = (int)($book_row['total_bookings'] ?? 0);

// 3. Count Total Movies in Catalog
$movie_query = mysqli_query($conn, "SELECT COUNT(id) AS total_movies FROM movies");
$movie_row   = mysqli_fetch_assoc($movie_query);
$total_movies = (int)($movie_row['total_movies'] ?? 0);

// 4. Count Upcoming Scheduled Showtimes
$show_query = mysqli_query($conn, "SELECT COUNT(id) AS upcoming_shows FROM showtimes WHERE show_date >= CURDATE()");
$show_row   = mysqli_fetch_assoc($show_query);
$upcoming_shows = (int)($show_row['upcoming_shows'] ?? 0);

// 5. Fetch Recent 5 Bookings for the overview table
$recent_sql = "
    SELECT 
        b.id AS booking_id, b.booking_number, b.total_amount, b.booking_date, b.status,
        u.fullname AS customer_name,
        m.title AS movie_title,
        s.show_date, s.show_time,
        GROUP_CONCAT(sb.seat_number ORDER BY sb.seat_number ASC SEPARATOR ', ') AS seats
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    LEFT JOIN seat_bookings sb ON b.id = sb.booking_id
    GROUP BY b.id
    ORDER BY b.id DESC
    LIMIT 5
";
$recent_result = mysqli_query($conn, $recent_sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="font-weight-bold text-light mb-1">
            <i class="fas fa-tachometer-alt text-warning mr-2"></i>Administration Dashboard
        </h3>
        <p class="text-muted small mb-0">System performance indicators, cinema revenue, and recent ticket activity</p>
    </div>
    <div>
        <a href="movies.php" class="btn btn-warning btn-sm font-weight-bold mr-2">
            <i class="fas fa-plus mr-1"></i> Add Movie
        </a>
        <a href="showtimes.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-calendar-plus mr-1"></i> Add Showtime
        </a>
    </div>
</div>

<!-- 4 Top KPI Metric Cards -->
<div class="row mb-4">
    <!-- Total Revenue Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="admin-card p-3 d-flex align-items-center">
            <div class="p-3 bg-success rounded-circle text-white mr-3">
                <i class="fas fa-dollar-sign fa-lg"></i>
            </div>
            <div>
                <div class="text-muted small font-weight-bold text-uppercase">Total Revenue</div>
                <h4 class="font-weight-bold text-warning mb-0">Rs. <?php echo number_format($total_revenue, 2); ?></h4>
            </div>
        </div>
    </div>

    <!-- Total Bookings Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="admin-card p-3 d-flex align-items-center">
            <div class="p-3 bg-primary rounded-circle text-white mr-3">
                <i class="fas fa-ticket-alt fa-lg"></i>
            </div>
            <div>
                <div class="text-muted small font-weight-bold text-uppercase">Total Bookings</div>
                <h4 class="font-weight-bold text-light mb-0"><?php echo $total_bookings; ?></h4>
            </div>
        </div>
    </div>

    <!-- Active Movies Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="admin-card p-3 d-flex align-items-center">
            <div class="p-3 bg-warning rounded-circle text-dark mr-3">
                <i class="fas fa-film fa-lg"></i>
            </div>
            <div>
                <div class="text-muted small font-weight-bold text-uppercase">Movies Catalog</div>
                <h4 class="font-weight-bold text-light mb-0"><?php echo $total_movies; ?></h4>
            </div>
        </div>
    </div>

    <!-- Upcoming Showtimes Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="admin-card p-3 d-flex align-items-center">
            <div class="p-3 bg-info rounded-circle text-white mr-3">
                <i class="fas fa-clock fa-lg"></i>
            </div>
            <div>
                <div class="text-muted small font-weight-bold text-uppercase">Upcoming Shows</div>
                <h4 class="font-weight-bold text-light mb-0"><?php echo $upcoming_shows; ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-weight-bold text-light mb-0">
            <i class="fas fa-receipt text-warning mr-2"></i>Recent Booking Transactions
        </h5>
        <a href="bookings.php" class="btn btn-outline-warning btn-sm">
            View All Bookings <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>

    <?php if (mysqli_num_rows($recent_result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>REF #</th>
                        <th>CUSTOMER</th>
                        <th>MOVIE</th>
                        <th>DATE & TIME</th>
                        <th>SEATS</th>
                        <th>AMOUNT</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($b = mysqli_fetch_assoc($recent_result)): ?>
                        <tr>
                            <td class="font-weight-bold text-warning align-middle">
                                <?php echo htmlspecialchars($b['booking_number']); ?>
                            </td>
                            <td class="align-middle text-light">
                                <?php echo htmlspecialchars($b['customer_name']); ?>
                            </td>
                            <td class="align-middle font-weight-bold text-light">
                                <?php echo htmlspecialchars($b['movie_title']); ?>
                            </td>
                            <td class="align-middle small text-muted">
                                <div><?php echo date('d M Y', strtotime($b['show_date'])); ?></div>
                                <div><?php echo date('h:i A', strtotime($b['show_time'])); ?></div>
                            </td>
                            <td class="align-middle text-success font-weight-bold">
                                <?php echo htmlspecialchars($b['seats'] ?? 'N/A'); ?>
                            </td>
                            <td class="align-middle text-light font-weight-bold">
                                Rs. <?php echo number_format($b['total_amount'], 2); ?>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-success px-2 py-1 text-uppercase">
                                    <?php echo htmlspecialchars($b['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-4 text-muted">
            <i class="fas fa-ticket-alt fa-2x mb-2"></i>
            <p class="mb-0">No bookings recorded yet in the database.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
