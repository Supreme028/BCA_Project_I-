<?php
// ==============================================================
// admin/login.php
// Administrator Authentication Portal
// ==============================================================

require_once __DIR__ . '/../config/db.php';

// If already authenticated as administrator, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $sql = "SELECT id, username, password, fullname FROM admins WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($admin = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $admin['password'])) {
                // Initialize admin session variables
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name']     = $admin['fullname'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No administrator account found with that username.";
        }
        mysqli_stmt_close($stmt);
    }
}

$admin_page_title = "Admin Login - CineBook";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-md-5 col-lg-4">
        <div class="admin-card p-4 shadow">
            <div class="text-center mb-4">
                <div class="p-3 bg-warning text-dark d-inline-block rounded-circle mb-2">
                    <i class="fas fa-user-shield fa-2x"></i>
                </div>
                <h4 class="font-weight-bold text-light mb-1">Admin Portal</h4>
                <p class="text-muted small">Enter your staff credentials to access control panel</p>
            </div>

            <!-- Error message alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username" class="small text-muted font-weight-bold">USERNAME</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="e.g. admin" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="small text-muted font-weight-bold">PASSWORD</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-warning btn-block font-weight-bold mt-4 py-2">
                    <i class="fas fa-sign-in-alt mr-1"></i> Sign In to Dashboard
                </button>
            </form>

            <!-- Quick Viva Testing Credentials Helper -->
            <div class="mt-4 p-2 rounded bg-dark border border-secondary text-center small text-muted">
                <i class="fas fa-info-circle text-warning mr-1"></i> Default Demo Login:<br>
                Username: <strong class="text-light">admin</strong> | Password: <strong class="text-light">admin123</strong>
            </div>

            <div class="text-center mt-3 pt-2">
                <a href="../index.php" class="text-muted small">
                    <i class="fas fa-arrow-left mr-1"></i> Return to Public Website
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
