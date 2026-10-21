<?php 
// ==============================
// 🔹 DATABASE CONNECTION (Hostinger)
// ==============================
// Credentials live in includes/config.php (not in Git). Copy config.example.php to config.php on the server.
require __DIR__ . '/config.php';
$servername = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$dbname     = DB_NAME;

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ==============================
// 🔹 FORM SUBMISSION
// ==============================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = htmlspecialchars(trim($_POST['first-name'] ?? ''));
    $lastname  = htmlspecialchars(trim($_POST['last-name'] ?? ''));
    $email     = htmlspecialchars(trim($_POST['email'] ?? ''));
    $subject   = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message   = htmlspecialchars(trim($_POST['message'] ?? ''));

    // Validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($subject) || empty($message)) {
        exit("All fields are required!");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Invalid email format!");
    }

    // ==============================
    // 🔹 SAVE TO DATABASE
    // ==============================
    $stmt = $conn->prepare("
        INSERT INTO contact_messages (first_name, last_name, email, subject, message)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $subject, $message);

    if (!$stmt->execute()) {
        exit("⚠️ Failed to save message. Please try again.");
    }

    // ==============================
    // 🔹 SEND EMAIL TO ADMIN
    // ==============================
    $to_admin       = "info@aeroedgemedia.com";
    $email_subject  = "📬 New Contact Message: " . $subject;
    $email_body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>New Contact Form Message</h2>
            <p><strong>First Name:</strong> {$firstname}</p>
            <p><strong>Last Name:</strong> {$lastname}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Message:</strong><br>{$message}</p>
            <hr>
            <small style='color:#777;'>This message was automatically sent from your website contact form.</small>
        </body>
        </html>
    ";

    $headers_admin  = "MIME-Version: 1.0\r\n";
    $headers_admin .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers_admin .= "From: Aero Edge Media <no-reply@aeroedgemedia.com>\r\n";
    $headers_admin .= "Reply-To: {$email}\r\n";

    mail($to_admin, $email_subject, $email_body, $headers_admin);

    // ==============================
    // 🔹 SEND THANK-YOU EMAIL TO USER
    // ==============================
    $to_user      = $email;
    $subject_user = "🎉 Thank You for Reaching Out – Aero Edge Media";
    $message_user = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Thank You, {$firstname}!</h2>
            <p>We’ve received your message regarding <strong>{$subject}</strong>.</p>
            <p>Our team will get back to you as soon as possible.</p>
            <br>
            <p>Best regards,<br><strong>The Aero Edge Media Team</strong></p>
            <hr>
            <p style='font-size: 12px; color: #777;'>You are receiving this email because you contacted us through our website.</p>
        </body>
        </html>
    ";

    $headers_user  = "MIME-Version: 1.0\r\n";
    $headers_user .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers_user .= "From: Aero Edge Media <no-reply@aeroedgemedia.com>\r\n";

    mail($to_user, $subject_user, $message_user, $headers_user);

    echo "✅ Thank you {$firstname}, your message has been sent successfully!";
    
    $stmt->close();
    $conn->close();

} else {
    echo "Invalid request method!";
}
?>
