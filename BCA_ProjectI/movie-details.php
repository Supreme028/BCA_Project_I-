<?php
// ==============================================================
// movie-details.php
// Movie Details & Available Showtimes Selection
// ==============================================================

require_once 'config/db.php';

// Get and sanitize movie ID from query string
$movie_id = intval($_GET['id'] ?? 0);

if ($movie_id <= 0) {
    header("Location: index.php");
    exit;
}

// 1. Fetch movie details
$movie_sql = "SELECT * FROM movies WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $movie_sql);
mysqli_stmt_bind_param($stmt, "i", $movie_id);
mysqli_stmt_execute($stmt);
$movie_result = mysqli_stmt_get_result($stmt);

if (!$movie = mysqli_fetch_assoc($movie_result)) {
    mysqli_stmt_close($stmt);
    header("Location: index.php");
    exit;
}
mysqli_stmt_close($stmt);

// 2. Fetch upcoming showtimes for this movie joined with screens table
$showtime_sql = "
    SELECT s.id AS showtime_id, s.show_date, s.show_time, s.ticket_price, sc.name AS screen_name
    FROM showtimes s
    JOIN screens sc ON s.screen_id = sc.id
    WHERE s.movie_id = ? AND s.show_date >= CURDATE()
    ORDER BY s.show_date ASC, s.show_time ASC
";
$stmt = mysqli_prepare($conn, $showtime_sql);
mysqli_stmt_bind_param($stmt, "i", $movie_id);
mysqli_stmt_execute($stmt);
$showtimes_result = mysqli_stmt_get_result($stmt);

// Group showtimes by date for organized tab/display
$grouped_showtimes = [];
while ($row = mysqli_fetch_assoc($showtimes_result)) {
    $date_key = $row['show_date'];
    $grouped_showtimes[$date_key][] = $row;
}
mysqli_stmt_close($stmt);

// Determine poster image
$poster_path = 'uploads/posters/' . $movie['poster'];
$poster_url = (!empty($movie['poster']) && file_exists($poster_path)) 
    ? $poster_path 
    : "https://placehold.co/400x560/1e293b/f8fafc?text=" . urlencode($movie['title']);

$page_title = $movie['title'] . " - CineBook";
require_once 'includes/header.php';
?>

<div class="container my-4">
    <!-- Back to browsing link -->
    <a href="index.php" class="btn btn-outline-secondary btn-sm mb-4 text-light">
        <i class="fas fa-arrow-left mr-1"></i> Back to Movies
    </a>

    <div class="row">
        <!-- Left: Movie Poster & Quick Info -->
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="movie-card">
                <div class="movie-poster-wrapper">
                    <img src="<?php echo htmlspecialchars($poster_url); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                </div>
                <div class="p-3">
                    <span class="badge badge-warning text-dark font-weight-bold px-2 py-1 mb-2 d-inline-block">
                        <?php echo htmlspecialchars($movie['genre']); ?>
                    </span>
                    <ul class="list-unstyled text-muted small mb-0">
                        <li class="mb-1">
                            <strong class="text-light"><i class="far fa-clock mr-1"></i> Duration:</strong> 
                            <?php echo (int)$movie['duration']; ?> mins
                        </li>
                        <li class="mb-1">
                            <strong class="text-light"><i class="fas fa-language mr-1"></i> Language:</strong> 
                            <?php echo htmlspecialchars($movie['language']); ?>
                        </li>
                        <li class="mb-1">
                            <strong class="text-light"><i class="far fa-calendar-alt mr-1"></i> Release:</strong> 
                            <?php echo date('d M Y', strtotime($movie['release_date'])); ?>
                        </li>
                        <li>
                            <strong class="text-light"><i class="fas fa-info-circle mr-1"></i> Status:</strong> 
                            <span class="text-success text-capitalize"><?php echo str_replace('_', ' ', $movie['status']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right: Movie Synopsis & Showtime Schedule -->
        <div class="col-md-8 col-lg-9">
            <div class="bg-dark p-4 rounded border border-secondary mb-4 text-light">
                <h2 class="font-weight-bold text-warning mb-2"><?php echo htmlspecialchars($movie['title']); ?></h2>
                <h6 class="text-muted text-uppercase mb-3 font-weight-bold">Synopsis</h6>
                <p class="lead text-light" style="font-size: 1.05rem; line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                </p>
            </div>

            <!-- Showtimes Section -->
            <div class="bg-dark p-4 rounded border border-secondary text-light">
                <h4 class="font-weight-bold text-warning mb-3">
                    <i class="fas fa-clock mr-2"></i>Select Show Date & Time
                </h4>

                <?php if (!empty($grouped_showtimes)): ?>
                    <?php foreach ($grouped_showtimes as $date => $shows): 
                        $is_today = ($date === date('Y-m-d'));
                        $date_label = $is_today ? 'Today, ' . date('d M Y', strtotime($date)) : date('l, d M Y', strtotime($date));
                    ?>
                        <div class="mb-4 pb-3 border-bottom border-secondary">
                            <h6 class="font-weight-bold text-light mb-3">
                                <i class="far fa-calendar-check text-warning mr-2"></i>
                                <?php echo htmlspecialchars($date_label); ?>
                            </h6>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($shows as $s): ?>
                                    <a href="select-seats.php?showtime_id=<?php echo $s['showtime_id']; ?>" 
                                       class="btn btn-outline-warning mr-3 mb-2 p-2 px-3 text-left">
                                        <div class="font-weight-bold" style="font-size: 1.1rem;">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo date('h:i A', strtotime($s['show_time'])); ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($s['screen_name']); ?>
                                        </div>
                                        <div class="small text-success font-weight-bold">
                                            Rs. <?php echo number_format($s['ticket_price'], 2); ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-secondary py-3 text-center mb-0">
                        <i class="fas fa-calendar-times mr-1"></i> No upcoming showtimes are scheduled for this movie yet. Please check back later!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
