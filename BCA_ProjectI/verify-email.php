<?php
// ==============================================================
// verify-email.php
// Email Verification Page
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
$email_sent = false;
$email = '';

// Check if user came from successful registration
if (isset($_GET['sent'])) {
    $email_sent = true;
    $email = $_GET['email'] ?? '';
}

// Handle verification token from URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $result = verifyEmailToken($conn, $token);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Page title for includes/header.php
$page_title = "Verify Email - CineBook";
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card p-4 text-light">
                <div class="text-center mb-4">
                    <h3 class="font-weight-bold text-warning">
                        <i class="fas fa-envelope-open-text mr-2"></i>Verify Your Email
                    </h3>
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
                        <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Email Sent Message -->
                <?php if ($email_sent && empty($error) && empty($success)): ?>
                    <div class="alert alert-info py-2 small" role="alert">
                        <i class="fas fa-info-circle mr-1"></i> 
                        We've sent a verification email to <strong><?php echo htmlspecialchars($email); ?></strong>.
                        Please check your inbox (and spam folder) and click the verification link.
                        <br><br>
                        <small>Didn't receive the email? <a href="resend-verification.php?email=<?php echo urlencode($email); ?>" class="text-warning font-weight-bold">Resend verification email</a></small>
                    </div>
                <?php endif; ?>

                <!-- Verification Link Entered Manually -->
                <?php if (!$email_sent && empty($_GET['token']) && empty($error) && empty($success)): ?>
                    <p class="text-muted small text-center mb-4">
                        Enter the verification token from your email, or click the link in the email directly.
                    </p>
                    
                    <form action="verify-email.php" method="GET" autocomplete="off">
                        <div class="form-group">
                            <label for="token" class="small text-muted font-weight-bold">VERIFICATION TOKEN</label>
                            <input type="text" class="form-control" id="token" name="token" 
                                   placeholder="Enter 64-character token from email" required>
                        </div>
                        <button type="submit" class="btn btn-warning btn-block font-weight-bold mt-4 py-2">
                            <i class="fas fa-check-circle mr-1"></i> Verify Email
                        </button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-3 pt-3 border-top border-secondary small text-muted">
                    <a href="login.php" class="text-warning font-weight-bold">Back to Login</a>
                    <span class="mx-2">|</span>
                    <a href="register.php" class="text-warning font-weight-bold">Register Again</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>