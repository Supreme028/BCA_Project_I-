<?php
// ==============================================================
// resend-verification.php
// Resend Verification Email Page
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
$email = $_GET['email'] ?? '';

// Handle form submission to resend verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if user exists and is not verified
        $sql = "SELECT id, fullname, is_verified FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if ($user['is_verified']) {
                $success = "This email is already verified. You can <a href='login.php' class='text-warning font-weight-bold'>log in here</a>.";
            } else {
                // Generate new token and send email
                $token = generateVerificationToken();
                if (saveVerificationToken($conn, $user['id'], $token)) {
                    if (sendVerificationEmail($conn, $email, $user['fullname'], $token)) {
                        $success = "A new verification email has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Please check your inbox.";
                    } else {
                        $error = "Failed to send verification email. Please try again later.";
                    }
                } else {
                    $error = "Failed to generate verification token. Please try again.";
                }
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = "If an unverified account exists with this email, a verification email has been sent.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Page title for includes/header.php
$page_title = "Resend Verification - CineBook";
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card p-4 text-light">
                <div class="text-center mb-4">
                    <h3 class="font-weight-bold text-warning">
                        <i class="fas fa-paper-plane mr-2"></i>Resend Verification Email
                    </h3>
                    <p class="text-muted small">Didn't receive the verification email? Enter your email to resend it.</p>
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

                <!-- Resend Form -->
                <form action="resend-verification.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email" class="small text-muted font-weight-bold">EMAIL ADDRESS</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold mt-4 py-2">
                        <i class="fas fa-paper-plane mr-1"></i> Resend Verification Email
                    </button>
                </form>

                <div class="text-center mt-3 pt-3 border-top border-secondary small text-muted">
                    <a href="login.php" class="text-warning font-weight-bold">Back to Login</a>
                    <span class="mx-2">|</span>
                    <a href="register.php" class="text-warning font-weight-bold">Register New Account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>