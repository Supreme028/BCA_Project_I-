<?php
// ==============================================================
// config/email.php
// Email Utility Functions
// ==============================================================

/**
 * Send verification email to user
 * 
 * @param mysqli $conn Database connection
 * @param string $email User's email address
 * @param string $fullname User's full name
 * @param string $token Verification token
 * @return bool True if email sent successfully, false otherwise
 */
function sendVerificationEmail($conn, $email, $fullname, $token) {
    $verify_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify-email.php?token=" . $token;
    
    $subject = "Verify Your Email Address - CineBook";
    
    $message = "
    <html>
    <head>
        <title>Email Verification - CineBook</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='color: #000; margin: 0; font-size: 28px;'>CineBook</h1>
            <p style='color: #333; margin: 10px 0 0;'>Movie Ticket Booking</p>
        </div>
        <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #dee2e6; border-top: none;'>
            <h2 style='color: #333; margin-top: 0;'>Verify Your Email Address</h2>
            <p>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
            <p>Thank you for registering with CineBook! To complete your registration and start booking movies, please verify your email address by clicking the button below:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . $verify_url . "' style='background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%); color: #000; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Verify Email Address</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all; color: #666; font-size: 14px;'>" . $verify_url . "</p>
            <p><strong>This link will expire in 24 hours.</strong></p>
            <hr style='border: none; border-top: 1px solid #dee2e6; margin: 20px 0;'>
            <p style='color: #666; font-size: 12px; margin: 0;'>If you didn't create an account with CineBook, please ignore this email.</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: CineBook <noreply@cinebook.local>\r\n";
    $headers .= "Reply-To: noreply@cinebook.local\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Use PHP's built-in mail function
    // Note: For production, consider using PHPMailer with SMTP
    return mail($email, $subject, $message, $headers);
}

/**
 * Generate a secure random verification token
 * 
 * @return string 64-character hex token
 */
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Save verification token to database
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $token Verification token
 * @return bool True if saved successfully
 */
function saveVerificationToken($conn, $user_id, $token) {
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $sql = "UPDATE users SET verification_token = ?, token_expiry = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $token, $expiry, $user_id);
    return mysqli_stmt_execute($stmt);
}

/**
 * Verify user's email using token
 * 
 * @param mysqli $conn Database connection
 * @param string $token Verification token
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function verifyEmailToken($conn, $token) {
    // Find user with matching token that hasn't expired
    $sql = "SELECT id, fullname, email, is_verified FROM users WHERE verification_token = ? AND token_expiry > NOW() LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if ($user['is_verified']) {
            return ['success' => true, 'message' => 'Email is already verified. You can now log in.', 'user' => $user];
        }
        
        // Mark user as verified and clear token
        $update_sql = "UPDATE users SET is_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            return ['success' => true, 'message' => 'Email verified successfully! You can now log in.', 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'Verification failed. Please try again.', 'user' => null];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid or expired verification link. Please register again.', 'user' => null];
}
?>