<?php
// ==============================================================
// admin/showtimes.php
// Showtime Scheduling: Link Movie to Hall, Date, Time & Price
// ==============================================================

$admin_page_title = "Manage Showtimes - CineBook Admin";
require_once __DIR__ . '/includes/header.php';

$message = "";
$error   = "";

// 1. Handle Showtime Deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $del_stmt = mysqli_prepare($conn, "DELETE FROM showtimes WHERE id = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $delete_id);
    if (mysqli_stmt_execute($del_stmt)) {
        $message = "Showtime deleted successfully.";
    } else {
        $error = "Failed to delete showtime: " . mysqli_error($conn);
    }
    mysqli_stmt_close($del_stmt);
}

// 2. Handle Add Showtime Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_showtime'])) {
    $movie_id     = intval($_POST['movie_id'] ?? 0);
    $screen_id    = intval($_POST['screen_id'] ?? 0);
    $show_date    = trim($_POST['show_date'] ?? '');
    $show_time    = trim($_POST['show_time'] ?? '');
    $ticket_price = floatval($_POST['ticket_price'] ?? 0);

    if ($movie_id <= 0 || $screen_id <= 0 || empty($show_date) || empty($show_time) || $ticket_price <= 0) {
        $error = "Please fill in all showtime scheduling fields properly.";
    } else {
        // Conflict Check: Screen collision at the same date and time
        $conflict_sql = "SELECT id FROM showtimes WHERE screen_id = ? AND show_date = ? AND show_time = ? LIMIT 1";
        $c_stmt = mysqli_prepare($conn, $conflict_sql);
        mysqli_stmt_bind_param($c_stmt, "iss", $screen_id, $show_date, $show_time);
        mysqli_stmt_execute($c_stmt);
        $c_res = mysqli_stmt_get_result($c_stmt);

        if (mysqli_num_rows($c_res) > 0) {
            $error = "Conflict detected! That cinema screen is already scheduled for another show at this exact date and time.";
        } else {
            // Insert showtime
            $insert_sql = "INSERT INTO showtimes (movie_id, screen_id, show_date, show_time, ticket_price) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "iissd", $movie_id, $screen_id, $show_date, $show_time, $ticket_price);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Showtime scheduled successfully!";
            } else {
                $error = "Failed to schedule showtime: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($c_stmt);
    }
}

// 3. Fetch all showtimes with joined Movie & Screen info
$showtimes_sql = "
    SELECT 
        s.id AS showtime_id, s.show_date, s.show_time, s.ticket_price,
        m.title AS movie_title,
        sc.name AS screen_name, (sc.total_rows * sc.total_columns) AS total_capacity,
        (SELECT COUNT(sb.id) FROM seat_bookings sb WHERE sb.showtime_id = s.id) AS booked_count
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN screens sc ON s.screen_id = sc.id
    ORDER BY s.show_date DESC, s.show_time ASC
";
$showtimes_result = mysqli_query($conn, $showtimes_sql);

// Fetch movies and screens for the modal dropdowns
$available_movies  = mysqli_query($conn, "SELECT id, title FROM movies WHERE status != 'ended' ORDER BY title ASC");
$available_screens = mysqli_query($conn, "SELECT id, name FROM screens ORDER BY id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="font-weight-bold text-light mb-1">
            <i class="fas fa-clock text-warning mr-2"></i>Showtimes Schedule
        </h3>
        <p class="text-muted small mb-0">Schedule screenings, assign cinema halls, and set seat pricing</p>
    </div>
    <button type="button" class="btn btn-warning font-weight-bold" data-toggle="modal" data-target="#addShowtimeModal">
        <i class="fas fa-calendar-plus mr-1"></i> Schedule New Showtime
    </button>
</div>

<!-- Feedback Alerts -->
<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show small py-2" role="alert">
        <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<!-- Showtimes Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>MOVIE</th>
                    <th>CINEMA HALL</th>
                    <th>DATE & TIME</th>
                    <th>PRICE (PER SEAT)</th>
                    <th>SEAT OCCUPANCY</th>
                    <th class="text-right">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($showtimes_result) > 0): ?>
                    <?php while ($s = mysqli_fetch_assoc($showtimes_result)): 
                        $is_past = ($s['show_date'] < date('Y-m-d'));
                    ?>
                        <tr class="<?php if ($is_past) echo 'opacity-75 text-muted'; ?>">
                            <td class="align-middle font-weight-bold text-light">
                                <?php echo htmlspecialchars($s['movie_title']); ?>
                            </td>
                            <td class="align-middle">
                                <?php echo htmlspecialchars($s['screen_name']); ?>
                            </td>
                            <td class="align-middle small">
                                <div><i class="far fa-calendar-alt mr-1"></i><?php echo date('d M Y', strtotime($s['show_date'])); ?></div>
                                <div class="text-warning"><i class="far fa-clock mr-1"></i><?php echo date('h:i A', strtotime($s['show_time'])); ?></div>
                            </td>
                            <td class="align-middle font-weight-bold text-success">
                                Rs. <?php echo number_format($s['ticket_price'], 2); ?>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-info px-2 py-1">
                                    <?php echo (int)$s['booked_count']; ?> / <?php echo (int)$s['total_capacity']; ?> Booked
                                </span>
                            </td>
                            <td class="align-middle text-right">
                                <a href="showtimes.php?delete_id=<?php echo $s['showtime_id']; ?>" 
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Delete this showtime? All associated bookings will be removed.');">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No showtimes scheduled yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Schedule Showtime -->
<div class="modal fade" id="addShowtimeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-dark text-light border border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-weight-bold text-warning">
                    <i class="fas fa-calendar-plus mr-2"></i>Schedule Showtime
                </h5>
                <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
            </div>
            
            <form action="showtimes.php" method="POST">
                <input type="hidden" name="add_showtime" value="1">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">SELECT MOVIE *</label>
                        <select name="movie_id" class="custom-select" required>
                            <option value="">-- Choose Movie --</option>
                            <?php while ($m = mysqli_fetch_assoc($available_movies)): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">SELECT SCREEN / HALL *</label>
                        <select name="screen_id" class="custom-select" required>
                            <option value="">-- Choose Screen --</option>
                            <?php while ($sc = mysqli_fetch_assoc($available_screens)): ?>
                                <option value="<?php echo $sc['id']; ?>"><?php echo htmlspecialchars($sc['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="small text-muted font-weight-bold">SHOW DATE *</label>
                            <input type="date" name="show_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="small text-muted font-weight-bold">SHOW TIME *</label>
                            <input type="time" name="show_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">TICKET PRICE (RS.) *</label>
                        <input type="number" name="ticket_price" class="form-control" placeholder="e.g. 250.00" step="0.01" min="1" required>
                    </div>
                </div>

                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">Save Showtime</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
