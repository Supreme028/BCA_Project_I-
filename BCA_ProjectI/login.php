<?php
// ==============================================================
// login.php
// Customer Login Page
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

// Check if user came from a successful registration
if (isset($_GET['registered'])) {
    $success = "Registration successful! You can now log in.";
}

// Check if user was redirected from booking flow
$redirect_to = $_GET['redirect'] ?? 'index.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $redirect_to = $_POST['redirect_to'] ?? 'index.php';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Query user record by email using prepared statement
        $sql = "SELECT id, fullname, email, password, is_verified FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Check if email is verified
            if (!$user['is_verified']) {
                $error = "Please verify your email address before logging in. <a href='resend-verification.php?email=" . urlencode($email) . "' class='text-warning font-weight-bold'>Resend verification email</a>";
            } elseif (password_verify($password, $user['password'])) {
                // Store user details in PHP session
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];

                // Prevent open redirect vulnerabilities
                $allowed_redirects = ['index.php', 'my-bookings.php'];
                if (strpos($redirect_to, 'select-seats.php') === 0 || in_array($redirect_to, $allowed_redirects)) {
                    header("Location: " . $redirect_to);
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with this email address.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Page title for includes/header.php
$page_title = "Login - CineBook";
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="auth-card p-4 text-light">
                <div class="text-center mb-4">
                    <h3 class="font-weight-bold text-warning">
                        <i class="fas fa-sign-in-alt mr-2"></i>Welcome Back
                    </h3>
                    <p class="text-muted small">Log in to book tickets and manage reservations</p>
                </div>

                <!-- Registration Success Banner -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success py-2 small" role="alert">
                        <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Error Alert -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="login.php" method="POST" autocomplete="off">
                    <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirect_to); ?>">

                    <div class="form-group">
                        <label for="email" class="small text-muted font-weight-bold">EMAIL ADDRESS</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="small text-muted font-weight-bold">PASSWORD</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold mt-4 py-2">
                        <i class="fas fa-arrow-right mr-1"></i> Log In
                    </button>
                </form>

                <div class="text-center mt-3 pt-3 border-top border-secondary small text-muted">
                    Don't have an account? 
                    <a href="register.php" class="text-warning font-weight-bold">Register now</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
