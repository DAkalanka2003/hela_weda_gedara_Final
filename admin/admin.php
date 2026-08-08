<?php
/**
 * Weda Gedara - Secure Administrative Portal (Forest & Forestry Edition)
 * Location: admin/admin.php
 */

// Harden session cookies
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Password verification settings
$admin_hash = '751b6dee23f140e17853dabe5ef1a476caad8b14a9d500f397c64d6c1d2dc857'; // SHA-256 hash of 'admin123' with salt
$salt = 'WedaGedaraSecureSalt2026!';
$db_file = __DIR__ . '/database.sqlite';
$content_file = __DIR__ . '/content.json';
$worksheet_file = __DIR__ . '/worksheet.txt';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure database connection
try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    $db = null;
}

// Handle AJAX actions (CMS save, Worksheet save, Record Deletion)
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    // 1. CSRF Verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Security validation failed. Session expired.']);
        exit;
    }
    
    // 2. Password Verification
    $entered = $_POST['auth_password'] ?? '';
    $entered_hash = hash('sha256', $entered . $salt);
    if (!hash_equals($admin_hash, $entered_hash)) {
        echo json_encode(['success' => false, 'error' => 'Invalid administrator password. Access denied.']);
        exit;
    }
    
    $action = $_POST['ajax_action'];
    
    // 3. Process Actions
    if ($action === 'save_content') {
        if (file_exists($content_file)) {
            $current = json_decode(file_get_contents($content_file), true);
            foreach ($_POST['cms'] as $key => $val) {
                $current[$key] = htmlspecialchars($val);
            }
            file_put_contents($content_file, json_encode($current, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Website content successfully updated and saved!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database content file not found.']);
        }
        exit;
    }
    
    if ($action === 'save_worksheet') {
        $notes = $_POST['worksheet_notes'] ?? '';
        file_put_contents($worksheet_file, $notes);
        echo json_encode(['success' => true, 'message' => 'Worksheet notes successfully saved!']);
        exit;
    }
    
    if ($action === 'delete_record') {
        $table = $_POST['table'] ?? '';
        $id = intval($_POST['id'] ?? 0);
        if ($db && ($table === 'appointments' || $table === 'inquiries') && $id > 0) {
            $stmt = $db->prepare("DELETE FROM $table WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Record successfully deleted from database!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error or invalid table/ID.']);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid operation.']);
    exit;
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Authentication check
$authenticated = isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;

// Brute Force protection check (Traditional Login Gateway)
$lockout_time = 300; // 5 minutes lockout
if (isset($_SESSION['lockout_until']) && time() < $_SESSION['lockout_until']) {
    $remaining = $_SESSION['lockout_until'] - time();
    $login_error = "Too many failed attempts. Locked out for " . ceil($remaining / 60) . " minutes.";
} else {
    if (isset($_POST['login_submit'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $login_error = "Security validation failed. Please refresh and try again.";
        } else {
            $entered = $_POST['password'] ?? '';
            $entered_hash = hash('sha256', $entered . $salt);
            
            if (hash_equals($admin_hash, $entered_hash)) {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['admin_auth'] = true;
                unset($_SESSION['failed_attempts']);
                unset($_SESSION['lockout_until']);
                header("Location: admin.php");
                exit;
            } else {
                // Failed login attempt
                sleep(2); // Throttles automation
                $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
                
                if ($_SESSION['failed_attempts'] >= 5) {
                    $_SESSION['lockout_until'] = time() + $lockout_time;
                    $login_error = "Too many failed attempts. Locked out for 5 minutes.";
                } else {
                    $login_error = "Invalid administrator password. Attempt " . $_SESSION['failed_attempts'] . " of 5.";
                }
            }
        }
    }
}

// Fetch Content
$cms_data = [];
if (file_exists($content_file)) {
    $cms_data = json_decode(file_get_contents($content_file), true);
}

// Fetch Worksheet content
$worksheet_content = '';
if (file_exists($worksheet_file)) {
    $worksheet_content = file_get_contents($worksheet_file);
}

// Fetch database records
$appointments = [];
$inquiries = [];
$visitors_count = 0;
if ($db) {
    try {
        $appointments = $db->query("SELECT * FROM appointments ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $inquiries = $db->query("SELECT * FROM inquiries ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        $db->exec("CREATE TABLE IF NOT EXISTS visitors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT UNIQUE,
            visit_time DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $visitors_count = $db->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    } catch (Exception $e) {
        // Tables might not be initialized yet
    }
}

// Group CMS fields by page categories for easy management
$grouped_cms = [
    'Home Page' => [],
    'About Page' => [],
    'Academy Page' => [],
    'Ayurveda Page' => [],
    'Treatments Page' => [],
    'Consultation Page' => [],
    'Contact & Footer Details' => []
];

foreach ($cms_data as $key => $val) {
    if (strpos($key, 'home_') === 0) {
        $grouped_cms['Home Page'][$key] = $val;
    } elseif (strpos($key, 'about_') === 0) {
        $grouped_cms['About Page'][$key] = $val;
    } elseif (strpos($key, 'academy_') === 0 || strpos($key, 'course_') === 0) {
        $grouped_cms['Academy Page'][$key] = $val;
    } elseif (strpos($key, 'ayurveda_') === 0) {
        $grouped_cms['Ayurveda Page'][$key] = $val;
    } elseif (strpos($key, 'treatments_') === 0) {
        $grouped_cms['Treatments Page'][$key] = $val;
    } elseif (strpos($key, 'consultation_') === 0 || strpos($key, 'rate_') === 0) {
        $grouped_cms['Consultation Page'][$key] = $val;
    } else {
        $grouped_cms['Contact & Footer Details'][$key] = $val;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <title>Weda Gedara || Administration Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-primary: #0a2111;
            --admin-accent: #2e7d32;
            --admin-bg-glass: rgba(255, 255, 255, 0.78);
            --admin-border: rgba(27, 83, 41, 0.12);
        }
        
        body {
            background-image: radial-gradient(circle at 50% 50%, rgba(244, 248, 245, 0.94) 0%, rgba(230, 242, 233, 0.96) 100%), url('../images/hero1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-dark);
            min-height: 100vh;
            font-family: var(--font-body);
        }

        /* Glassmorphic Login */
        .admin-login-wrapper {
            max-width: 440px;
            margin: 120px auto;
            padding: 45px;
            background: var(--admin-bg-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .admin-login-wrapper h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 25px;
            font-weight: 800;
        }

        /* Header Restyling */
        .admin-header {
            background: rgba(10, 33, 17, 0.94);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 15px 0;
            border-bottom: 2px solid var(--primary-light);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
        }

        .admin-nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .admin-nav-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Nav & Tab Buttons */
        .admin-tab-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.85);
            font-family: var(--font-heading);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            padding: 10px 18px;
            border-radius: 50px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-tab-btn.active {
            color: #fff;
            background-color: var(--accent);
            border-color: var(--accent);
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.35);
        }

        .admin-tab-btn:hover:not(.active) {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }

        /* Stats Row */
        .admin-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            max-width: 1250px;
            margin: 40px auto 10px;
            padding: 0 24px;
        }

        .stat-card {
            background: var(--admin-bg-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: var(--radius-sm);
            background: var(--accent-light);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            color: var(--primary);
            line-height: 1.1;
        }

        .stat-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 4px 0 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Grid Layout */
        .admin-main-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            max-width: 1250px;
            margin: 20px auto 60px;
            padding: 0 24px;
        }

        .admin-sidebar {
            background: var(--admin-bg-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 25px 18px;
            height: fit-content;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-menu-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 800;
            color: var(--text-muted);
            margin-bottom: 18px;
            padding-left: 10px;
        }

        .sidebar-menu-item {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 12px 16px;
            font-family: var(--font-body);
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }

        .sidebar-menu-item.active {
            background-color: var(--primary-light);
            color: #fff;
            box-shadow: 0 4px 12px rgba(27, 83, 41, 0.2);
        }

        .sidebar-menu-item:hover:not(.active) {
            background-color: var(--accent-light);
            color: var(--primary-light);
            padding-left: 20px;
        }

        /* Workspace & Cards */
        .admin-card {
            background: var(--admin-bg-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 35px;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }

        .admin-card h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary);
        }

        .cms-page-panel {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .cms-page-panel.active {
            display: block;
        }

        /* Inputs & Textareas */
        .cms-field-group {
            margin-bottom: 24px;
            background-color: rgba(255, 255, 255, 0.4);
            padding: 22px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(27, 83, 41, 0.08);
            transition: all 0.3s ease;
        }

        .cms-field-group:focus-within {
            border-color: var(--primary-light);
            background-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 15px rgba(27, 83, 41, 0.03);
        }

        .cms-field-group label {
            display: block;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary);
            font-size: 0.95rem;
            text-transform: capitalize;
        }

        .cms-input, .cms-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(27, 83, 41, 0.15);
            border-radius: var(--radius-sm);
            background-color: rgba(255, 255, 255, 0.9);
            font-family: inherit;
            font-size: 0.98rem;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .cms-input:focus, .cms-textarea:focus {
            outline: none;
            border-color: var(--accent);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.15);
        }

        /* Buttons custom sizes */
        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 35px;
            background-color: var(--accent);
            color: #fff;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-family: var(--font-heading);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.25);
        }

        .btn-submit:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 33, 17, 0.3);
        }

        /* Tables style */
        .admin-table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background-color: rgba(255, 255, 255, 0.7);
            text-align: left;
        }

        .admin-table th {
            background-color: var(--primary-light);
            color: #fff;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(27, 83, 41, 0.08);
            font-size: 0.92rem;
            color: var(--text-dark);
            vertical-align: middle;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background-color: rgba(255, 255, 255, 0.6);
        }

        .badge-type {
            background: var(--accent-light);
            color: var(--primary-light);
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .admin-delete-btn {
            color: #c62828;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 50px;
            transition: all 0.2s ease;
        }

        .admin-delete-btn:hover {
            background: rgba(198, 40, 40, 0.1);
            color: #b71c1c;
        }

        .alert-success {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
            animation: fadeIn 0.4s ease;
        }

        /* Toast Feedback */
        #toast-feedback {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #0a2111;
            color: #fff;
            padding: 16px 30px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            font-weight: 600;
            border-left: 6px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 992px) {
            .admin-main-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <?php if (!$authenticated): ?>
        <!-- LOGIN PANEL -->
        <div class="admin-login-wrapper">
            <div style="font-size: 2.8rem; color: var(--accent); margin-bottom: 20px;"><i class="fa fa-shield-halved"></i></div>
            <h2>Admin Gateway</h2>
            <?php if (isset($login_error)): ?>
                <div style="color: #c62828; background: #ffebee; border: 1px solid #ffcdd2; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fa fa-circle-exclamation"></i> <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            <form action="admin.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div style="text-align: left; margin-bottom: 25px;">
                    <label for="password" style="font-weight: 700; color: var(--primary); font-size: 0.95rem; display: block; margin-bottom: 8px;">Administrator Password</label>
                    <input type="password" id="password" name="password" required style="width: 100%; padding: 14px 16px; border: 1px solid rgba(27, 83, 41, 0.15); border-radius: var(--radius-sm); font-size: 1rem; transition: all 0.3s;" placeholder="••••••••">
                </div>
                <button type="submit" name="login_submit" class="btn-submit" style="width: 100%; justify-content: center; padding: 14px 0;"><i class="fa fa-sign-in-alt"></i> Sign In</button>
            </form>
        </div>
    <?php else: ?>
        <!-- MAIN ADMINISTRATIVE INTERFACE -->
        <header class="admin-header">
            <div class="container admin-nav-container">
                <a href="../index.html" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
                    <img src="../images/logo.png" alt="Weda Gedara Logo" style="height: 44px; width: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
                    <h1 style="color: #fff; font-size: 1.45rem; margin: 0; font-weight: 800; letter-spacing: -0.5px;">Weda Gedara Admin Portal</h1>
                </a>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div id="srilanka-clock" style="color: #a5d6a7; font-family: var(--font-heading); font-size: 0.9rem; font-weight: 700; background: rgba(255,255,255,0.06); padding: 8px 16px; border-radius: 50px; border: 1px solid rgba(255,255,255,0.1); display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa fa-clock"></i> Loading...
                    </div>
                    <div class="admin-nav-links">
                        <button class="admin-tab-btn active" onclick="switchMainTab('cms-main-tab')"><i class="fa fa-pen-to-square"></i> Content Manager</button>
                        <button class="admin-tab-btn" onclick="switchMainTab('bookings-main-tab')"><i class="fa fa-calendar-check"></i> Appointments (<?php echo count($appointments); ?>)</button>
                        <button class="admin-tab-btn" onclick="switchMainTab('inquiries-main-tab')"><i class="fa fa-envelope"></i> Inquiries (<?php echo count($inquiries); ?>)</button>
                        <button class="admin-tab-btn" onclick="switchMainTab('worksheet-main-tab')"><i class="fa fa-clipboard-list"></i> Worksheet</button>
                        <a href="admin.php?logout=1" class="admin-tab-btn" style="background-color: rgba(239, 83, 80, 0.15); border-color: rgba(239, 83, 80, 0.3); color: #ef5350;"><i class="fa fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- DASHBOARD METRIC STATS -->
        <section class="admin-stats-row" id="admin-stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-calendar-days"></i></div>
                <div class="stat-info">
                    <h3><?php echo count($appointments); ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #e3f2fd; color: #1e88e5;"><i class="fa fa-envelope-open-text"></i></div>
                <div class="stat-info">
                    <h3><?php echo count($inquiries); ?></h3>
                    <p>Inquiries</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #e8f5e9; color: #2e7d32;"><i class="fa fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $visitors_count; ?></h3>
                    <p>Unique Visitors</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #fff8e1; color: #ffb300;"><i class="fa fa-file-pen"></i></div>
                <div class="stat-info">
                    <h3><?php echo count($cms_data); ?></h3>
                    <p>CMS Variables</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #f3e5f5; color: #8e24aa;"><i class="fa fa-note-sticky"></i></div>
                <div class="stat-info">
                    <h3><?php echo strlen($worksheet_content); ?></h3>
                    <p>Notes Characters</p>
                </div>
            </div>
        </section>

        <div class="container" style="max-width: 1250px; margin-top: 20px;">
            <?php if (isset($success_msg)): ?>
                <div class="alert-success">
                    <i class="fa fa-circle-check" style="font-size: 1.25rem;"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- MAIN LAYOUT GRID -->
        <div class="admin-main-grid">

            <!-- SIDEBAR (Dynamic CMS category selector) -->
            <aside class="admin-sidebar" id="cms-sidebar-wrapper">
                <div class="sidebar-menu-title">Select Web Page</div>
                <button class="sidebar-menu-item active" onclick="switchCMSPage('home-page-panel')"><i class="fa fa-house"></i> Home Page</button>
                <button class="sidebar-menu-item" onclick="switchCMSPage('about-page-panel')"><i class="fa fa-circle-info"></i> About Page</button>
                <button class="sidebar-menu-item" onclick="switchCMSPage('academy-page-panel')"><i class="fa fa-graduation-cap"></i> Academy Page</button>
                <button class="sidebar-menu-item" onclick="switchCMSPage('ayurveda-page-panel')"><i class="fa fa-spa"></i> Ayurveda Page</button>
                <button class="sidebar-menu-item" onclick="switchCMSPage('treatments-page-panel')"><i class="fa fa-hand-holding-heart"></i> Treatments Page</button>
                <button class="sidebar-menu-item" onclick="switchCMSPage('consultation-page-panel')"><i class="fa fa-user-doctor"></i> Consultation Page</button>
                <button class="sidebar-menu-item" onclick="switchCMSPage('contact-page-panel')"><i class="fa fa-address-book"></i> Contact & Footer</button>
            </aside>

            <!-- MAIN WORKSPACE -->
            <main class="admin-workspace">

                <!-- SECTION 1: CONTENT MANAGER TAB (WITH GROUPED PAGES) -->
                <div id="cms-main-tab" class="main-tab-content">
                    <form action="admin.php" method="POST" id="cms-editor-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <?php foreach ($grouped_cms as $page_title => $fields): ?>
                            <?php 
                                if ($page_title === 'Contact & Footer Details') {
                                    $panel_id = 'contact-page-panel';
                                } else {
                                    $panel_id = strtolower(str_replace(' & ', '-', str_replace(' ', '-', $page_title))) . '-panel';
                                }
                                $is_active = ($page_title === 'Home Page') ? 'active' : '';
                            ?>
                            <div id="<?php echo $panel_id; ?>" class="cms-page-panel <?php echo $is_active; ?>">
                                <div class="admin-card">
                                    <h2 style="margin-bottom: 25px; border-bottom: 2px solid rgba(27, 83, 41, 0.12); padding-bottom: 15px; color: var(--primary); display: flex; align-items: center; gap: 12px;">
                                        <i class="fa fa-folder-open" style="color: var(--accent);"></i> <?php echo $page_title; ?> Text Editor
                                    </h2>
                                    
                                    <?php foreach ($fields as $key => $val): ?>
                                        <div class="cms-field-group">
                                            <label for="cms_<?php echo $key; ?>"><?php echo ucwords(str_replace('_', ' ', str_replace('home_', '', str_replace('about_', '', str_replace('academy_', '', str_replace('consultation_', '', $key)))))); ?></label>
                                            <?php if (strlen($val) > 85): ?>
                                                <textarea id="cms_<?php echo $key; ?>" name="cms[<?php echo $key; ?>]" rows="4" class="cms-textarea"><?php echo htmlspecialchars($val); ?></textarea>
                                            <?php else: ?>
                                                <input type="text" id="cms_<?php echo $key; ?>" name="cms[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($val); ?>" class="cms-input">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div style="margin-top: 30px;">
                                        <button type="submit" name="save_content_submit" class="btn-submit"><i class="fa fa-save"></i> Save <?php echo $page_title; ?> Changes</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                    </form>
                </div>

                <!-- SECTION 2: BOOKING LISTING TAB -->
                <div id="bookings-main-tab" class="main-tab-content" style="display: none;">
                    <div class="admin-card">
                        <h2 style="margin-bottom: 10px; color: var(--primary);"><i class="fa fa-calendar-check" style="color: var(--accent); margin-right: 8px;"></i> Consultation Appointments</h2>
                        <p style="color: var(--text-muted); margin-bottom: 25px;">Client booking requests recorded from the Consultation request form.</p>
                        
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Contact Details</th>
                                        <th>Type</th>
                                        <th>Preferred Time</th>
                                        <th>Notes</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($appointments)): ?>
                                        <tr><td colspan="8" style="text-align: center; padding: 35px; color: var(--text-muted);">No booking requests found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($appointments as $row): ?>
                                            <tr>
                                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                                <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                                                <td style="line-height: 1.5;">
                                                    <i class="fa fa-envelope" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($row['email']); ?><br>
                                                    <i class="fa fa-phone" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($row['phone']); ?>
                                                </td>
                                                <td><span class="badge-type"><?php echo htmlspecialchars(str_replace('_', ' ', $row['consultation_type'])); ?></span></td>
                                                <td><?php echo htmlspecialchars($row['preferred_time']); ?></td>
                                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['message']); ?>"><?php echo htmlspecialchars($row['message']); ?></td>
                                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                <td><a href="#" class="admin-delete-btn" data-table="appointments" data-id="<?php echo $row['id']; ?>"><i class="fa fa-trash"></i> Delete</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: INQUIRY LISTING TAB -->
                <div id="inquiries-main-tab" class="main-tab-content" style="display: none;">
                    <div class="admin-card">
                        <h2 style="margin-bottom: 10px; color: var(--primary);"><i class="fa fa-envelope-open-text" style="color: var(--accent); margin-right: 8px;"></i> Contact Inquiries & Messages</h2>
                        <p style="color: var(--text-muted); margin-bottom: 25px;">General messages recorded from the Contact page inquiry form.</p>
                        
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Contact Details</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Message</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($inquiries)): ?>
                                        <tr><td colspan="8" style="text-align: center; padding: 35px; color: var(--text-muted);">No inquiries found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($inquiries as $row): ?>
                                            <tr>
                                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                                <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                                                <td style="line-height: 1.5;">
                                                    <i class="fa fa-envelope" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($row['email']); ?><br>
                                                    <i class="fa fa-phone" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($row['phone']); ?>
                                                </td>
                                                <td><span class="badge-type" style="background-color: #e3f2fd; color: #1e88e5;"><?php echo htmlspecialchars(str_replace('_', ' ', $row['inquiry_type'])); ?></span></td>
                                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                                <td><?php echo htmlspecialchars($row['message']); ?></td>
                                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                <td><a href="#" class="admin-delete-btn" data-table="inquiries" data-id="<?php echo $row['id']; ?>"><i class="fa fa-trash"></i> Delete</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: WORKSHEET TAB -->
                <div id="worksheet-main-tab" class="main-tab-content" style="display: none;">
                    <div class="admin-card">
                        <h2 style="margin-bottom: 10px; color: var(--primary);"><i class="fa fa-clipboard-list" style="color: var(--accent); margin-right: 8px;"></i> Worksheet Scratchpad</h2>
                        <p style="color: var(--text-muted); margin-bottom: 25px;">A private notes board to write schedules, recipes, patient files, or generic to-do tasks.</p>
                        
                        <form action="admin.php" method="POST" id="worksheet-editor-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <textarea name="worksheet_notes" rows="18" class="cms-textarea" style="background-color: #FAFdfb; font-family: 'Courier New', Courier, monospace; font-size: 1rem; line-height: 1.6; border: 1px solid rgba(27,83,41,0.15); border-radius: var(--radius-sm); padding: 20px;" placeholder="Start typing your tasks and notes here..."><?php echo htmlspecialchars($worksheet_content); ?></textarea>
                            <div style="margin-top: 20px;">
                                <button type="submit" name="save_worksheet_submit" class="btn-submit"><i class="fa fa-save"></i> Save Notes</button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>

        <script>
            // Switch main tabs
            function switchMainTab(tabId, saveToStorage = true) {
                // Hide all main content areas
                document.querySelectorAll('.main-tab-content').forEach(el => {
                    el.style.display = 'none';
                });
                
                // Show selected main content
                document.getElementById(tabId).style.display = 'block';
                
                // Show/hide the sidebar dynamically (only display sidebar on CMS Content Manager tab)
                const sidebar = document.getElementById('cms-sidebar-wrapper');
                const statsRow = document.getElementById('admin-stats-row');
                if (tabId === 'cms-main-tab') {
                    sidebar.style.display = 'block';
                    statsRow.style.display = 'grid';
                    document.querySelector('.admin-main-grid').style.gridTemplateColumns = '280px 1fr';
                } else {
                    sidebar.style.display = 'none';
                    statsRow.style.display = 'none';
                    document.querySelector('.admin-main-grid').style.gridTemplateColumns = '1fr';
                }
                
                // Toggle active class on header buttons
                document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tabId)) {
                        btn.classList.add('active');
                    }
                });

                if (saveToStorage) {
                    localStorage.setItem('admin_active_main_tab', tabId);
                }
            }

            // Switch sub-page settings inside Content Manager tab
            function switchCMSPage(panelId, saveToStorage = true) {
                // Hide all sub-panels
                document.querySelectorAll('.cms-page-panel').forEach(panel => {
                    panel.classList.remove('active');
                });
                
                // Show target panel
                document.getElementById(panelId).classList.add('active');
                
                // Toggle active state in sidebar buttons
                document.querySelectorAll('.sidebar-menu-item').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(panelId)) {
                        btn.classList.add('active');
                    }
                });

                if (saveToStorage) {
                    localStorage.setItem('admin_active_cms_panel', panelId);
                }
            }

            // Restore active tabs on load
            window.addEventListener('DOMContentLoaded', () => {
                const savedMainTab = localStorage.getItem('admin_active_main_tab');
                const savedCMSPanel = localStorage.getItem('admin_active_cms_panel');

                if (savedMainTab) {
                    switchMainTab(savedMainTab, false);
                }
                if (savedCMSPanel) {
                    switchCMSPage(savedCMSPanel, false);
                }

                // Initialize Sri Lanka Live Clock
                function updateSriLankaClock() {
                    const clockElement = document.getElementById('srilanka-clock');
                    if (!clockElement) return;
                    
                    const options = {
                        timeZone: 'Asia/Colombo',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true
                    };
                    
                    const formatter = new Intl.DateTimeFormat('en-US', options);
                    clockElement.innerHTML = `<i class="fa fa-clock"></i> SL: ${formatter.format(new Date())}`;
                }
                updateSriLankaClock();
                setInterval(updateSriLankaClock, 1000);
            });

            // JavaScript Action Re-Authentication & Double Confirmation Flow
            let activeActionCallback = null;

            function triggerConfirmation(actionCallback) {
                activeActionCallback = actionCallback;
                document.getElementById('reauth-error').style.display = "none";
                document.getElementById('reauth-password-input').value = "";
                
                // Show Confirmation step first
                document.getElementById('reauth-step-confirm').style.display = "block";
                document.getElementById('reauth-step-password').style.display = "none";
                
                document.getElementById('reauth-modal').style.display = "flex";
            }

            function closeReauthModal() {
                document.getElementById('reauth-modal').style.display = "none";
                activeActionCallback = null;
            }

            function confirmToPasswordStep() {
                document.getElementById('reauth-step-confirm').style.display = "none";
                document.getElementById('reauth-step-password').style.display = "block";
                document.getElementById('reauth-password-input').focus();
            }

            async function submitReauthPassword() {
                const password = document.getElementById('reauth-password-input').value;
                if (!password) {
                    alert('Please enter your password.');
                    return;
                }
                if (activeActionCallback) {
                    await activeActionCallback(password);
                }
            }

            function showToastFeedback(message) {
                const toast = document.createElement('div');
                toast.id = 'toast-feedback';
                toast.innerHTML = `<i class="fa fa-circle-check" style="color: #2e7d32; font-size: 1.3rem;"></i> <span>${message}</span>`;
                document.body.appendChild(toast);
            }

            // Intercept CMS form
            const cmsForm = document.getElementById('cms-editor-form');
            if (cmsForm) {
                cmsForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    triggerConfirmation(async (password) => {
                        const formData = new FormData(cmsForm);
                        formData.append('ajax_action', 'save_content');
                        formData.append('auth_password', password);
                        
                        try {
                            const res = await fetch('admin.php', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.success) {
                                closeReauthModal();
                                showToastFeedback(data.message);
                                setTimeout(() => window.location.reload(), 2000);
                            } else {
                                const errDiv = document.getElementById('reauth-error');
                                errDiv.innerText = data.error || 'Authorization failed.';
                                errDiv.style.display = "block";
                            }
                        } catch(err) {
                            console.error(err);
                            alert('Network or server communication error.');
                        }
                    });
                });
            }

            // Intercept Worksheet form
            const worksheetForm = document.getElementById('worksheet-editor-form');
            if (worksheetForm) {
                worksheetForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    triggerConfirmation(async (password) => {
                        const formData = new FormData(worksheetForm);
                        formData.append('ajax_action', 'save_worksheet');
                        formData.append('auth_password', password);
                        
                        try {
                            const res = await fetch('admin.php', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.success) {
                                closeReauthModal();
                                showToastFeedback(data.message);
                                setTimeout(() => window.location.reload(), 2000);
                            } else {
                                const errDiv = document.getElementById('reauth-error');
                                errDiv.innerText = data.error || 'Authorization failed.';
                                errDiv.style.display = "block";
                            }
                        } catch(err) {
                            console.error(err);
                            alert('Network or server communication error.');
                        }
                    });
                });
            }

            // Intercept Delete buttons
            document.querySelectorAll('.admin-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const table = btn.getAttribute('data-table');
                    const id = btn.getAttribute('data-id');
                    
                    triggerConfirmation(async (password) => {
                        const formData = new FormData();
                        formData.append('ajax_action', 'delete_record');
                        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
                        formData.append('auth_password', password);
                        formData.append('table', table);
                        formData.append('id', id);
                        
                        try {
                            const res = await fetch('admin.php', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.success) {
                                closeReauthModal();
                                showToastFeedback(data.message);
                                setTimeout(() => window.location.reload(), 2000);
                            } else {
                                const errDiv = document.getElementById('reauth-error');
                                errDiv.innerText = data.error || 'Authorization failed.';
                                errDiv.style.display = "block";
                            }
                        } catch(err) {
                            console.error(err);
                            alert('Network or server communication error.');
                        }
                    });
                });
            });
        </script>
    <?php endif; ?>

    <!-- DYNAMIC MULTI-STAGE RE-AUTHORIZATION SECURITY DIALOG -->
    <div id="reauth-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(10,33,17,0.5); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--admin-bg-glass); border: 1px solid var(--border-color); padding: 40px; border-radius: var(--radius-md); max-width: 440px; width: 90%; box-shadow: var(--shadow-lg); text-align: center; color: var(--text-dark);">
            
            <!-- STAGE 1: SURE? CONFIRMATION -->
            <div id="reauth-step-confirm">
                <div style="font-size: 2.8rem; color: #ffb300; margin-bottom: 20px;"><i class="fa fa-circle-question"></i></div>
                <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 10px; color: var(--primary);">Confirm Operation</h3>
                <p style="font-size: 0.92rem; color: var(--text-muted); margin-bottom: 25px; line-height: 1.5;">Are you absolutely sure you want to proceed with this modification?</p>
                
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="btn-submit" style="padding: 12px 30px; font-size: 0.92rem;" onclick="confirmToPasswordStep()"><i class="fa fa-check"></i> Yes, Proceed</button>
                    <button type="button" class="btn-submit" style="padding: 12px 30px; font-size: 0.92rem; background: rgba(0,0,0,0.06); color: var(--text-dark); box-shadow: none;" onclick="closeReauthModal()"><i class="fa fa-times"></i> Cancel</button>
                </div>
            </div>

            <!-- STAGE 2: PASSWORD INPUT FORM -->
            <div id="reauth-step-password" style="display: none;">
                <div style="font-size: 2.8rem; color: var(--accent); margin-bottom: 20px;"><i class="fa fa-shield-halved"></i></div>
                <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 10px; color: var(--primary);">Security Authorization</h3>
                <p style="font-size: 0.92rem; color: var(--text-muted); margin-bottom: 20px; line-height: 1.5;">Please enter your administrator password to authorize this action.</p>
                
                <div id="reauth-error" style="display: none; color: #c62828; background: #ffebee; border: 1px solid #ffcdd2; padding: 12px; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.88rem; margin-bottom: 20px;"></div>
                
                <input type="password" id="reauth-password-input" placeholder="Password" style="width: 100%; padding: 14px; border: 1px solid rgba(27,83,41,0.15); border-radius: var(--radius-sm); font-size: 1rem; margin-bottom: 25px; outline: none; background: #fff;" onkeydown="if(event.key==='Enter') submitReauthPassword()">
                
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="btn-submit" style="padding: 12px 30px; font-size: 0.92rem;" onclick="submitReauthPassword()"><i class="fa fa-key"></i> Verify & Execute</button>
                    <button type="button" class="btn-submit" style="padding: 12px 30px; font-size: 0.92rem; background: rgba(0,0,0,0.06); color: var(--text-dark); box-shadow: none;" onclick="closeReauthModal()"><i class="fa fa-times"></i> Cancel</button>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
