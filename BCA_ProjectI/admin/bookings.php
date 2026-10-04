<?php
// ==============================================================
// admin/bookings.php
// Master Bookings Ledger & Transaction Records
// ==============================================================

$admin_page_title = "All Bookings - CineBook Admin";
require_once __DIR__ . '/includes/header.php';

// Search filter query parameter
$search = trim($_GET['search'] ?? '');

// Base query for all bookings with customer, movie, screen, and seat list
$sql = "
    SELECT 
        b.id AS booking_id, b.booking_number, b.total_amount, b.booking_date, b.status,
        u.fullname AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
        m.title AS movie_title,
        s.show_date, s.show_time,
        sc.name AS screen_name,
        GROUP_CONCAT(sb.seat_number ORDER BY sb.seat_number ASC SEPARATOR ', ') AS seats
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN screens sc ON s.screen_id = sc.id
    LEFT JOIN seat_bookings sb ON b.id = sb.booking_id
";

$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " WHERE (b.booking_number LIKE ? OR u.fullname LIKE ? OR u.phone LIKE ? OR m.title LIKE ?)";
    $search_param = "%" . $search . "%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types  = "ssss";
}

$sql .= " GROUP BY b.id ORDER BY b.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Calculate totals for filtered result set
$total_revenue_filtered = 0;
$total_count_filtered   = mysqli_num_rows($result);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="font-weight-bold text-light mb-1">
            <i class="fas fa-receipt text-warning mr-2"></i>Master Bookings Ledger
        </h3>
        <p class="text-muted small mb-0">Complete record of cinema reservations, customers, and ticket sales</p>
    </div>
    
    <!-- Search Bar Form -->
    <form action="bookings.php" method="GET" class="form-inline">
        <div class="input-group">
            <input type="text" name="search" class="form-control form-control-sm bg-dark border-secondary text-light" 
                   placeholder="Search ref #, name, phone..." value="<?php echo htmlspecialchars($search); ?>">
            <div class="input-group-append">
                <button class="btn btn-warning btn-sm font-weight-bold" type="submit">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search)): ?>
                    <a href="bookings.php" class="btn btn-outline-secondary btn-sm text-light">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Bookings Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>REF #</th>
                    <th>CUSTOMER INFO</th>
                    <th>MOVIE & SCREEN</th>
                    <th>SHOW DATE & TIME</th>
                    <th>SEATS</th>
                    <th>AMOUNT</th>
                    <th>BOOKED AT</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_count_filtered > 0): ?>
                    <?php while ($b = mysqli_fetch_assoc($result)): 
                        $total_revenue_filtered += (float)$b['total_amount'];
                    ?>
                        <tr>
                            <td class="align-middle font-weight-bold text-warning">
                                <?php echo htmlspecialchars($b['booking_number']); ?>
                            </td>
                            <td class="align-middle small">
                                <div class="font-weight-bold text-light"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                                <div class="text-muted"><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($b['customer_phone']); ?></div>
                                <div class="text-muted"><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($b['customer_email']); ?></div>
                            </td>
                            <td class="align-middle small">
                                <div class="font-weight-bold text-light"><?php echo htmlspecialchars($b['movie_title']); ?></div>
                                <div class="text-muted"><?php echo htmlspecialchars($b['screen_name']); ?></div>
                            </td>
                            <td class="align-middle small">
                                <div><?php echo date('d M Y', strtotime($b['show_date'])); ?></div>
                                <div class="text-warning"><?php echo date('h:i A', strtotime($b['show_time'])); ?></div>
                            </td>
                            <td class="align-middle font-weight-bold text-success">
                                <?php echo htmlspecialchars($b['seats'] ?? 'N/A'); ?>
                            </td>
                            <td class="align-middle font-weight-bold text-light">
                                Rs. <?php echo number_format($b['total_amount'], 2); ?>
                            </td>
                            <td class="align-middle small text-muted">
                                <?php echo date('d M Y, h:i A', strtotime($b['booking_date'])); ?>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-success px-2 py-1 text-uppercase">
                                    <?php echo htmlspecialchars($b['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p class="mb-0">No booking records match your criteria.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary Footer Row -->
    <?php if ($total_count_filtered > 0): ?>
        <div class="p-3 border-top border-secondary d-flex justify-content-between align-items-center bg-dark">
            <span class="text-muted small">
                Showing <strong><?php echo $total_count_filtered; ?></strong> reservation(s)
            </span>
            <span class="text-light small">
                Filtered Revenue Total: <strong class="text-warning h6 mb-0 ml-1">Rs. <?php echo number_format($total_revenue_filtered, 2); ?></strong>
            </span>
        </div>
    <?php endif; ?>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
