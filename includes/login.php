<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit();
}

$username = $password = '';
$username_err = $password_err = $login_err = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "لطفا نام کاربری را وارد کنید.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "لطفا رمز عبور را وارد کنید.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check input errors before authentication
    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password, role, full_name, email, phone FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                // Check if username exists, if yes then verify password
                if ($stmt->num_rows == 1) {                    
                    $stmt->bind_result($id, $username, $hashed_password, $role, $full_name, $email, $phone);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["user_name"] = $username; // compatibility
                            $_SESSION["role"] = $role;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["email"] = $email;
                            $_SESSION["phone"] = $phone;
                            
                            // Redirect user to welcome page
							header("location: /index.php");
							exit();
                        } else {
                            // Password is not valid
                            $login_err = "نام کاربری یا رمز عبور اشتباه است.";
                        }
                    }
                } else {
                    // Username doesn't exist
                    $login_err = "نام کاربری یا رمز عبور اشتباه است.";
                }
            } else {
                echo "خطایی رخ داده است. لطفا بعدا دوباره تلاش کنید.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
// Initialize system settings
initialize_system_settings();

// Get background setting
$background_image = get_background_image();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت نوجوانان و بزرگسالان</title>
    <link href="../assets/css/font-face.css" rel="stylesheet">
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
	<style>
        body {
            background: url('<?php echo htmlspecialchars($background_image); ?>') no-repeat center center fixed !important;
            background-size: cover !important;
            background-attachment: fixed !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .main-content {
            background: transparent !important;
        }
        .content-box {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            margin: 20px auto;
        }
        .login-container {
			margin:auto;
		}
		.alert-dismissible {
			padding: 1rem;
			margin: 0 auto;
		}
        
    </style>
</head>
<body>
<?php 
        // Show error message if login failed
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>' . $login_err . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        
        // Show success message if user just logged out
        if (isset($_GET['logout']) && $_GET['logout'] == '1') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>شما با موفقیت از سیستم خارج شدید.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        ?>
    <div class="login-container">
        <div class="login-header">
            <p>ورود به مدیریت</p>
        </div>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person-fill me-2"></i>نام کاربری
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($username); ?>" placeholder="نام کاربری خود را وارد کنید">
                </div>
                <span class="invalid-feedback d-block"><?php echo $username_err; ?></span>
            </div>    
            
            <div class="form-group mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock-fill me-2"></i>رمز عبور
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent">
                        <i class="bi bi-key"></i>
                    </span>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                           placeholder="رمز عبور خود را وارد کنید">
                </div>
                <span class="invalid-feedback d-block"><?php echo $password_err; ?></span>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-left me-2"></i>ورود به سیستم
                </button>
            </div>
        </form>
    </div>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
