<?php
// ==============================================================
// book-process.php
// Backend Booking Handler & Concurrency-Safe Transaction
// ==============================================================

require_once 'config/db.php';

// 1. Guard check: User must be authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$user_id        = $_SESSION['user_id'];
$showtime_id    = intval($_POST['showtime_id'] ?? 0);
$selected_seats = trim($_POST['selected_seats'] ?? '');

if ($showtime_id <= 0 || empty($selected_seats)) {
    die("Invalid booking parameters. <a href='index.php'>Go back</a>");
}

// Convert comma-separated string into clean array (e.g. ['A1', 'A2'])
$seats_array = array_values(array_filter(array_map('trim', explode(',', $selected_seats))));
$seat_count  = count($seats_array);

if ($seat_count === 0 || $seat_count > 8) {
    die("Invalid seat count selected. Maximum 8 seats allowed. <a href='select-seats.php?showtime_id=$showtime_id'>Try again</a>");
}

// 3. Fetch showtime to confirm validity and retrieve ticket price
$show_sql = "SELECT ticket_price FROM showtimes WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $show_sql);
mysqli_stmt_bind_param($stmt, "i", $showtime_id);
mysqli_stmt_execute($stmt);
$show_result = mysqli_stmt_get_result($stmt);

if (!$show = mysqli_fetch_assoc($show_result)) {
    mysqli_stmt_close($stmt);
    die("Showtime not found. <a href='index.php'>Go back</a>");
}
mysqli_stmt_close($stmt);

$ticket_price = (float)$show['ticket_price'];
$total_amount = $seat_count * $ticket_price;

// 4. Begin Database Transaction for Concurrency Safety
mysqli_begin_transaction($conn);

try {
    // A. Check if ANY of the requested seats are already booked for this showtime
    // Build dynamic placeholders (?, ?, ...) for SQL IN clause
    $placeholders = implode(',', array_fill(0, $seat_count, '?'));
    $check_sql = "SELECT seat_number FROM seat_bookings WHERE showtime_id = ? AND seat_number IN ($placeholders) FOR UPDATE";
    
    $check_stmt = mysqli_prepare($conn, $check_sql);
    $types = "i" . str_repeat("s", $seat_count);
    $params = array_merge([$showtime_id], $seats_array);
    mysqli_stmt_bind_param($check_stmt, $types, ...$params);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        $taken_seats = [];
        while ($t_row = mysqli_fetch_assoc($check_result)) {
            $taken_seats[] = $t_row['seat_number'];
        }
        mysqli_stmt_close($check_stmt);
        mysqli_rollback($conn); // Cancel transaction
        
        die("Sorry! Seat(s) " . implode(', ', $taken_seats) . " was just booked by another customer. Please choose different seats. <a href='select-seats.php?showtime_id=$showtime_id'>Go back</a>");
    }
    mysqli_stmt_close($check_stmt);

    // B. Generate unique booking reference number (e.g. BK-7F9C21)
    $booking_number = 'BK-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    // C. Insert master booking record into bookings table
    $book_sql = "INSERT INTO bookings (booking_number, user_id, showtime_id, total_amount, status) VALUES (?, ?, ?, ?, 'confirmed')";
    $book_stmt = mysqli_prepare($conn, $book_sql);
    mysqli_stmt_bind_param($book_stmt, "siid", $booking_number, $user_id, $showtime_id, $total_amount);
    
    if (!mysqli_stmt_execute($book_stmt)) {
        throw new Exception("Error inserting booking record: " . mysqli_error($conn));
    }
    $booking_id = mysqli_insert_id($conn);
    mysqli_stmt_close($book_stmt);

    // D. Insert each individual seat into seat_bookings table
    $seat_sql = "INSERT INTO seat_bookings (booking_id, showtime_id, seat_number) VALUES (?, ?, ?)";
    $seat_stmt = mysqli_prepare($conn, $seat_sql);

    foreach ($seats_array as $seat_num) {
        mysqli_stmt_bind_param($seat_stmt, "iis", $booking_id, $showtime_id, $seat_num);
        if (!mysqli_stmt_execute($seat_stmt)) {
            throw new Exception("Error recording seat " . $seat_num . ": " . mysqli_error($conn));
        }
    }
    mysqli_stmt_close($seat_stmt);

    // E. Commit the entire transaction
    mysqli_commit($conn);

    // F. Redirect user to printable electronic ticket page
    header("Location: ticket.php?id=" . $booking_id);
    exit;

} catch (Exception $e) {
    // If any step failed, rollback all changes
    mysqli_rollback($conn);
    die("Booking failed: " . $e->getMessage() . " <a href='select-seats.php?showtime_id=$showtime_id'>Try again</a>");
}
?>
