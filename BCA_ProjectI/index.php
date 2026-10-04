<?php
// ==============================================================
// index.php
// Public Homepage: Movies grouped by status
// ==============================================================

$page_title = "CineBook - Movie Ticket Booking";
require_once 'includes/header.php';

$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre'] ?? '');

// Build filter conditions
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $where   .= " AND title LIKE ?";
    $params[] = "%" . $search . "%";
    $types   .= "s";
}
if (!empty($genre)) {
    $where   .= " AND genre LIKE ?";
    $params[] = "%" . $genre . "%";
    $types   .= "s";
}

// Fetch all movies once and separate by status in PHP
$sql  = "SELECT * FROM movies $where ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$now_showing  = [];
$coming_soon  = [];
$ended        = [];

while ($movie = mysqli_fetch_assoc($result)) {
    $poster_path = 'uploads/posters/' . $movie['poster'];
    $movie['poster_url'] = (!empty($movie['poster']) && file_exists($poster_path))
        ? $poster_path
        : "https://placehold.co/400x560/1e293b/f8fafc?text=" . urlencode($movie['title']);

    if ($movie['status'] === 'now_showing')  $now_showing[] = $movie;
    elseif ($movie['status'] === 'coming_soon') $coming_soon[] = $movie;
    else                                         $ended[]       = $movie;
}
mysqli_stmt_close($stmt);

// Fetch distinct genres for filter
$genre_result = mysqli_query($conn, "SELECT DISTINCT genre FROM movies WHERE genre != '' ORDER BY genre ASC");

// Helper function to render a movie card
function renderMovieCard($movie, $status_label = '') { ?>
    <div class="col-6 col-md-4 col-lg-3 mb-4">
        <div class="movie-card h-100 d-flex flex-column">
            <div class="movie-poster-wrapper">
                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>"
                     alt="<?php echo htmlspecialchars($movie['title']); ?>">
                <?php if ($status_label): ?>
                    <div class="poster-status-badge <?php echo $status_label['class']; ?>">
                        <?php echo $status_label['text']; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-3 d-flex flex-column flex-grow-1">
                <div class="mb-1">
                    <span class="badge badge-genre px-2 py-1">
                        <?php echo htmlspecialchars($movie['genre']); ?>
                    </span>
                </div>
                <h5 class="font-weight-bold text-light mt-1 mb-1 text-truncate"
                    title="<?php echo htmlspecialchars($movie['title']); ?>">
                    <?php echo htmlspecialchars($movie['title']); ?>
                </h5>
                <p class="text-muted small mb-2">
                    <i class="far fa-clock mr-1"></i><?php echo (int)$movie['duration']; ?> mins
                    &nbsp;|&nbsp;
                    <i class="fas fa-globe mr-1"></i><?php echo htmlspecialchars($movie['language']); ?>
                </p>
                <p class="text-secondary small mb-3 flex-grow-1"
                   style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    <?php echo htmlspecialchars($movie['description']); ?>
                </p>
                <?php if ($movie['status'] === 'ended'): ?>
                    <button class="btn btn-secondary btn-block btn-sm" disabled>
                        <i class="fas fa-times-circle mr-1"></i>Show Ended
                    </button>
                <?php elseif ($movie['status'] === 'coming_soon'): ?>
                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>"
                       class="btn btn-outline-warning btn-block btn-sm font-weight-bold">
                        <i class="fas fa-bell mr-1"></i>View Details
                    </a>
                <?php else: ?>
                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>"
                       class="btn btn-warning btn-block btn-sm font-weight-bold">
                        <i class="fas fa-ticket-alt mr-1"></i>Book Tickets
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php }

function renderSection($title, $icon, $badge_class, $badge_text, $accent_color, $movies) {
    if (empty($movies)) return;
    $status_label = ['class' => $badge_class, 'text' => $badge_text];
    ?>
    <div class="movie-section mb-5">
        <!-- Section Header -->
        <div class="section-header d-flex align-items-center mb-4">
            <div class="section-accent" style="background:<?php echo $accent_color; ?>;"></div>
            <div class="d-flex align-items-center flex-grow-1">
                <i class="<?php echo $icon; ?> mr-2" style="color:<?php echo $accent_color; ?>;font-size:1.4rem;"></i>
                <h3 class="font-weight-bold text-light mb-0"><?php echo $title; ?></h3>
                <span class="ml-3 badge px-3 py-1" style="background:<?php echo $accent_color; ?>;color:#111;font-size:0.8rem;">
                    <?php echo count($movies); ?> movie<?php echo count($movies) !== 1 ? 's' : ''; ?>
                </span>
            </div>
        </div>
        <div class="row">
            <?php foreach ($movies as $movie): renderMovieCard($movie, $status_label); endforeach; ?>
        </div>
    </div>
    <?php
}
?>

<style>
.section-header { border-bottom: 1px solid #2d3748; padding-bottom: 0.75rem; margin-bottom: 1.5rem; }
.section-accent { width: 4px; height: 36px; border-radius: 4px; margin-right: 14px; flex-shrink: 0; }
.poster-status-badge {
    position: absolute; top: 10px; left: 10px;
    padding: 3px 10px; border-radius: 20px; font-size: 0.7rem;
    font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
}
.badge-now-showing  { background: #28a745; color: #fff; }
.badge-coming-soon  { background: #f59e0b; color: #111; }
.badge-ended        { background: #6c757d; color: #fff; }
.movie-section { border-bottom: 1px solid #1a202c; padding-bottom: 1rem; }
.movie-section:last-child { border-bottom: none; }
</style>

<!-- Hero Banner -->
<section class="jumbotron jumbotron-fluid bg-transparent text-light border-bottom border-secondary mb-4 py-4">
    <div class="container text-center">
        <h1 class="display-4 font-weight-bold text-warning">Book Movie Tickets Online</h1>
        <p class="lead text-muted">Select your favorite movie, pick your seats, and enjoy the show!</p>

        <!-- Filter & Search -->
        <form action="index.php" method="GET" class="form-inline justify-content-center mt-3">
            <div class="input-group mb-2 mr-sm-2 col-md-5 px-0">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-dark border-secondary text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                <input type="text" name="search" class="form-control bg-dark border-secondary text-light"
                       placeholder="Search movies by title..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <select name="genre" class="form-control bg-dark border-secondary text-light mb-2 mr-sm-2">
                <option value="">All Genres</option>
                <?php while ($g = mysqli_fetch_assoc($genre_result)): ?>
                    <option value="<?php echo htmlspecialchars($g['genre']); ?>"
                        <?php if ($genre === $g['genre']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($g['genre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-warning mb-2 font-weight-bold">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
            <?php if (!empty($search) || !empty($genre)): ?>
                <a href="index.php" class="btn btn-outline-light mb-2 ml-2">
                    <i class="fas fa-times mr-1"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</section>

<!-- Movie Sections -->
<div class="container mb-5">

    <?php
    $total = count($now_showing) + count($coming_soon) + count($ended);
    if ($total === 0):
    ?>
        <div class="text-center py-5">
            <i class="fas fa-film fa-3x text-muted mb-3"></i>
            <h4 class="text-light">No movies found</h4>
            <p class="text-muted">Try changing your search keywords or genre filter.</p>
            <a href="index.php" class="btn btn-warning">View All Movies</a>
        </div>
    <?php else: ?>

        <!-- NOW SHOWING -->
        <?php renderSection(
            'Now Showing',
            'fas fa-play-circle',
            'badge-now-showing',
            'Now Showing',
            '#28a745',
            $now_showing
        ); ?>

        <!-- COMING SOON -->
        <?php renderSection(
            'Coming Soon',
            'fas fa-hourglass-half',
            'badge-coming-soon',
            'Coming Soon',
            '#f59e0b',
            $coming_soon
        ); ?>

        <!-- ENDED -->
        <?php renderSection(
            'Ended',
            'fas fa-history',
            'badge-ended',
            'Ended',
            '#6c757d',
            $ended
        ); ?>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
