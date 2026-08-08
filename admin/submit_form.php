<?php
/**
 * Weda Gedara - SQLite Database Form Submission Handler
 * Location: admin/submit_form.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = __DIR__ . '/database.sqlite';

try {
    // 1. Initialize SQLite Database Connection
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Ensure Database Tables Exist
    $db->exec("CREATE TABLE IF NOT EXISTS appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT NOT NULL,
        consultation_type TEXT NOT NULL,
        preferred_time TEXT,
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS inquiries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT NOT NULL,
        inquiry_type TEXT NOT NULL,
        location TEXT,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS visitors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT UNIQUE,
        visit_time DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Handle AJAX visitor tracking request
    if (isset($_GET['track_visit'])) {
        header('Content-Type: application/json');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $db->prepare("INSERT OR IGNORE INTO visitors (ip_address) VALUES (:ip)");
        $stmt->execute([':ip' => $ip]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // 3. Handle POST Requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // A. Handle Appointment Booking Form
        if (isset($_POST['consultation_type'])) {
            $stmt = $db->prepare("INSERT INTO appointments (first_name, last_name, email, phone, consultation_type, preferred_time, message) VALUES (:first_name, :last_name, :email, :phone, :consultation_type, :preferred_time, :message)");
            
            $stmt->execute([
                ':first_name' => htmlspecialchars($_POST['first_name'] ?? ''),
                ':last_name' => htmlspecialchars($_POST['last_name'] ?? ''),
                ':email' => htmlspecialchars($_POST['email'] ?? ''),
                ':phone' => htmlspecialchars($_POST['phone'] ?? ''),
                ':consultation_type' => htmlspecialchars($_POST['consultation_type'] ?? ''),
                ':preferred_time' => htmlspecialchars($_POST['preferred_time'] ?? ''),
                ':message' => htmlspecialchars($_POST['message'] ?? '')
            ]);
            $title = "Appointment Requested";
            $message = "Your appointment slot request has been recorded successfully! We will contact you shortly to confirm your booking.";
        
        // B. Handle General Contact / Inquiry Form
        } else if (isset($_POST['inquiry_type'])) {
            $stmt = $db->prepare("INSERT INTO inquiries (first_name, last_name, email, phone, inquiry_type, location, message) VALUES (:first_name, :last_name, :email, :phone, :inquiry_type, :location, :message)");
            
            $stmt->execute([
                ':first_name' => htmlspecialchars($_POST['first_name'] ?? ''),
                ':last_name' => htmlspecialchars($_POST['last_name'] ?? ''),
                ':email' => htmlspecialchars($_POST['email'] ?? ''),
                ':phone' => htmlspecialchars($_POST['phone'] ?? ''),
                ':inquiry_type' => htmlspecialchars($_POST['inquiry_type'] ?? ''),
                ':location' => htmlspecialchars($_POST['location'] ?? ''),
                ':message' => htmlspecialchars($_POST['message'] ?? '')
            ]);
            $title = "Inquiry Sent";
            $message = "Your inquiry has been successfully sent! Our team will get back to you soon.";
            
        } else {
            throw new Exception("Invalid form fields.");
        }
        
    } else {
        header("Location: ../index.html");
        exit;
    }

} catch (Exception $e) {
    $title = "Submission Error";
    $message = "Something went wrong saving your request: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> || Weda Gedara</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            background-color: #f0f6f2;
        }
        .success-card {
            background-color: #dbe7e1;
            border: 1px solid rgba(71, 104, 86, 0.15);
            padding: 50px 40px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            font-size: 3.5rem;
            color: #2b4436;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">✓</div>
        <h2><?php echo $title; ?></h2>
        <p style="margin: 20px 0 30px; line-height: 1.8; color: var(--text-muted);"><?php echo $message; ?></p>
        <a href="../index.html" class="btn btn-primary">Return to Home</a>
    </div>
</body>
</html>
