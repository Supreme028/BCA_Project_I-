<?php
// ==============================================================
// register.php
// Customer Registration Page
// ==============================================================

// Include database configuration (also starts session)
require_once 'config/db.php';
require_once 'config/email.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname         = trim($_POST['fullname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Form Validations
    if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists in database
        $check_sql = "SELECT id, is_verified FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($existing_user = mysqli_fetch_assoc($result)) {
            if ($existing_user['is_verified']) {
                $error = "An account with this email already exists. Please log in.";
            } else {
                // Resend verification email for unverified account
                $token = generateVerificationToken();
                if (saveVerificationToken($conn, $existing_user['id'], $token)) {
                    sendVerificationEmail($conn, $email, $fullname, $token);
                    $success = "A new verification email has been sent. Please check your inbox.";
                } else {
                    $error = "Failed to resend verification email. Please try again.";
                }
            }
        } else {
            // Hash password securely with bcrypt
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $token = generateVerificationToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Insert new user into database with verification token
            $insert_sql = "INSERT INTO users (fullname, email, phone, password, verification_token, token_expiry) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ssssss", $fullname, $email, $phone, $hashed_password, $token, $expiry);

            if (mysqli_stmt_execute($insert_stmt)) {
                $user_id = mysqli_insert_id($conn);
                
                // Send verification email
                $email_sent = sendVerificationEmail($conn, $email, $fullname, $token);
                
                if ($email_sent) {
                    // Registration successful -> redirect to verify email page
                    header("Location: verify-email.php?sent=1&email=" . urlencode($email));
                    exit;
                } else {
                    // Email failed but user created - show message to check spam or request resend
                    $success = "Registration successful! However, we couldn't send the verification email. Please check your spam folder or <a href='resend-verification.php?email=" . urlencode($email) . "'>request a new verification email</a>.";
                }
            } else {
                $error = "Registration failed. Please try again. Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// Page title for includes/header.php
$page_title = "Register - CineBook";
require_once 'includes/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card p-4 text-light">
                <div class="text-center mb-4">
                    <h3 class="font-weight-bold text-warning">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </h3>
                    <p class="text-muted small">Join CineBook to book your favorite movies online</p>
                </div>

                <!-- Display Error Alert if any -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Display Success Alert if any -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success py-2 small" role="alert">
                        <i class="fas fa-check-circle mr-1"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form action="register.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="fullname" class="small text-muted font-weight-bold">FULL NAME</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" 
                               placeholder="e.g. John Doe" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="small text-muted font-weight-bold">EMAIL ADDRESS</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="small text-muted font-weight-bold">PHONE NUMBER</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="e.g. 9876543210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="small text-muted font-weight-bold">PASSWORD</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="At least 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="small text-muted font-weight-bold">CONFIRM PASSWORD</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter password" required>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold mt-4 py-2">
                        <i class="fas fa-check-circle mr-1"></i> Register
                    </button>
                </form>

                <div class="text-center mt-3 pt-3 border-top border-secondary small text-muted">
                    Already have an account? 
                    <a href="login.php" class="text-warning font-weight-bold">Log In here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
