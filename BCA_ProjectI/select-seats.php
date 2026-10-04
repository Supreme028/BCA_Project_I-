<?php
// ==============================================================
// select-seats.php
// Interactive Cinema Seat Selection Map
// ==============================================================

require_once 'config/db.php';

// Check if user is logged in before allowing seat selection
$showtime_id = intval($_GET['showtime_id'] ?? 0);

if ($showtime_id <= 0) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    // Redirect to login while remembering intended showtime
    header("Location: login.php?redirect=" . urlencode("select-seats.php?showtime_id=" . $showtime_id));
    exit;
}

// 1. Fetch showtime, movie, and screen layout details
$sql = "
    SELECT 
        s.id AS showtime_id, s.show_date, s.show_time, s.ticket_price,
        m.id AS movie_id, m.title AS movie_title, m.poster, m.genre, m.language,
        sc.id AS screen_id, sc.name AS screen_name, sc.total_rows, sc.total_columns
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN screens sc ON s.screen_id = sc.id
    WHERE s.id = ? LIMIT 1
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $showtime_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$show = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    header("Location: index.php");
    exit;
}
mysqli_stmt_close($stmt);

// 2. Fetch list of already booked seats for this showtime
$booked_sql = "SELECT seat_number FROM seat_bookings WHERE showtime_id = ?";
$b_stmt = mysqli_prepare($conn, $booked_sql);
mysqli_stmt_bind_param($b_stmt, "i", $showtime_id);
mysqli_stmt_execute($b_stmt);
$b_res = mysqli_stmt_get_result($b_stmt);

$booked_seats = [];
while ($b_row = mysqli_fetch_assoc($b_res)) {
    $booked_seats[] = $b_row['seat_number'];
}
mysqli_stmt_close($b_stmt);

$page_title = "Select Seats - " . $show['movie_title'];
require_once 'includes/header.php';
?>

<div class="container my-4">
    <!-- Back to Movie Details link -->
    <a href="movie-details.php?id=<?php echo $show['movie_id']; ?>" class="btn btn-outline-secondary btn-sm mb-3 text-light">
        <i class="fas fa-arrow-left mr-1"></i> Back to Movie
    </a>

    <div class="row">
        <!-- Left: Cinema Screen & Interactive Seating Grid -->
        <div class="col-lg-8 mb-4">
            <div class="bg-dark p-4 rounded border border-secondary text-center text-light">
                <h4 class="font-weight-bold text-warning mb-1">
                    <?php echo htmlspecialchars($show['movie_title']); ?>
                </h4>
                <p class="text-muted small mb-3">
                    <?php echo htmlspecialchars($show['screen_name']); ?> &nbsp;|&nbsp;
                    <?php echo date('d M Y', strtotime($show['show_date'])); ?> at 
                    <?php echo date('h:i A', strtotime($show['show_time'])); ?>
                </p>

                <!-- Screen Indicator Bar -->
                <div class="cinema-screen"></div>

                <!-- Seat Grid Container -->
                <div class="seat-grid my-3">
                    <?php 
                    // Generate rows (A, B, C, etc.)
                    for ($r = 0; $r < $show['total_rows']; $r++): 
                        $row_letter = chr(65 + $r); // 65 is ASCII 'A'
                    ?>
                        <div class="seat-row">
                            <span class="row-label"><?php echo $row_letter; ?></span>
                            
                            <?php 
                            // Generate seats per row (1, 2, 3, etc.)
                            for ($c = 1; $c <= $show['total_columns']; $c++): 
                                $seat_code = $row_letter . $c;
                                $is_booked = in_array($seat_code, $booked_seats);
                            ?>
                                <?php if ($is_booked): ?>
                                    <div class="seat booked" title="Seat <?php echo $seat_code; ?> (Booked)">
                                        <?php echo $seat_code; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="seat available" 
                                         data-seat="<?php echo $seat_code; ?>" 
                                         onclick="selectSeat(this)" 
                                         title="Seat <?php echo $seat_code; ?> (Available)">
                                        <?php echo $seat_code; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <span class="row-label"><?php echo $row_letter; ?></span>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Seating Legend -->
                <div class="mt-4 pt-3 border-top border-secondary text-center">
                    <div class="seat-legend-item">
                        <span class="seat-legend-box" style="background-color: #334155; border: 1px solid #475569;"></span>
                        Available
                    </div>
                    <div class="seat-legend-item">
                        <span class="seat-legend-box" style="background-color: #22c55e;"></span>
                        Selected
                    </div>
                    <div class="seat-legend-item">
                        <span class="seat-legend-box" style="background-color: #ef4444; opacity: 0.6;"></span>
                        Booked
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Real-Time Booking Summary & Confirmation Widget -->
        <div class="col-lg-4">
            <div class="bg-dark p-4 rounded border border-secondary text-light sticky-top" style="top: 80px;">
                <h5 class="font-weight-bold text-warning border-bottom border-secondary pb-3 mb-3">
                    <i class="fas fa-receipt mr-2"></i>Booking Summary
                </h5>

                <div class="mb-3">
                    <div class="small text-muted">MOVIE</div>
                    <div class="font-weight-bold text-light"><?php echo htmlspecialchars($show['movie_title']); ?></div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted">CINEMA HALL</div>
                    <div><?php echo htmlspecialchars($show['screen_name']); ?></div>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <div class="small text-muted">DATE</div>
                        <div><?php echo date('d M Y', strtotime($show['show_date'])); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">TIME</div>
                        <div><?php echo date('h:i A', strtotime($show['show_time'])); ?></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted">PRICE PER TICKET</div>
                    <div class="font-weight-bold text-light">Rs. <?php echo number_format($show['ticket_price'], 2); ?></div>
                </div>

                <hr class="border-secondary">

                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Selected Seats:</span>
                    <span id="selected-seats-display" class="font-weight-bold text-warning">None</span>
                </div>

                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Total Tickets:</span>
                    <span id="ticket-count-display" class="font-weight-bold">0</span>
                </div>

                <div class="my-3 p-3 rounded bg-secondary d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold">Total Amount:</span>
                    <span id="total-amount-display" class="h4 font-weight-bold text-warning mb-0">Rs. 0.00</span>
                </div>

                <!-- Hidden Form to Submit Reservation to book-process.php -->
                <form action="book-process.php" method="POST" id="booking-form">
                    <input type="hidden" name="showtime_id" value="<?php echo $show['showtime_id']; ?>">
                    <input type="hidden" name="selected_seats" id="hidden_seats_input" value="">

                    <button type="submit" id="btn-confirm" class="btn btn-warning btn-block font-weight-bold py-2" disabled>
                        <i class="fas fa-check-circle mr-1"></i> Confirm Booking
                    </button>
                </form>

                <p class="text-muted small text-center mt-2 mb-0">
                    <i class="fas fa-shield-alt mr-1"></i> Instant electronic ticket generation
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Seat Selection JavaScript Logic -->
<script>
    const ticketPrice = <?php echo (float)$show['ticket_price']; ?>;
    let selectedSeats = [];

    function selectSeat(seatElement) {
        const seatCode = seatElement.getAttribute('data-seat');

        // Toggle selection
        if (selectedSeats.includes(seatCode)) {
            // Deselect seat
            selectedSeats = selectedSeats.filter(s => s !== seatCode);
            seatElement.classList.remove('selected');
        } else {
            // Select seat (limit max 8 seats per booking)
            if (selectedSeats.length >= 8) {
                alert("You can select a maximum of 8 seats per booking.");
                return;
            }
            selectedSeats.push(seatCode);
            seatElement.classList.add('selected');
        }

        // Keep seats alphabetically sorted (e.g., A1, A2, B3)
        selectedSeats.sort();

        // Update Summary UI
        const count = selectedSeats.length;
        const total = (count * ticketPrice).toFixed(2);

        document.getElementById('ticket-count-display').innerText = count;
        document.getElementById('selected-seats-display').innerText = count > 0 ? selectedSeats.join(', ') : 'None';
        document.getElementById('total-amount-display').innerText = 'Rs. ' + total;

        // Update hidden form input value
        document.getElementById('hidden_seats_input').value = selectedSeats.join(',');

        // Enable or disable confirmation button
        document.getElementById('btn-confirm').disabled = (count === 0);
    }
</script>

<?php require_once 'includes/footer.php'; ?>
