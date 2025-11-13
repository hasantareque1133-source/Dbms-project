<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$error = '';

if (isLoggedIn()) {
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $institutional_id = trim($_POST['institutional_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($institutional_id) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT user_id, institutional_id, name, email, password, role, department, year FROM users WHERE institutional_id = ?");
        $stmt->bind_param("s", $institutional_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['institutional_id'] = $user['institutional_id'];
                
                logActivity($conn, 'User logged in', 'users', $user['user_id']);
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: /admin/dashboard.php');
                        break;
                    case 'moderator':
                        header('Location: /moderator/dashboard.php');
                        break;
                    case 'student':
                        header('Location: /student/dashboard.php');
                        break;
                    default:
                        header('Location: /index.php');
                }
                exit();
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'Invalid credentials.';
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - University Networking Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>University Networking Portal</h1>
                <p>Connect. Learn. Grow.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="mb-3">
                    <label for="institutional_id" class="form-label">Institutional ID</label>
                    <input type="text" class="form-control" id="institutional_id" name="institutional_id" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            
            <div class="login-footer">
                <p class="text-muted">Default Admin: ADMIN001 / admin123</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
