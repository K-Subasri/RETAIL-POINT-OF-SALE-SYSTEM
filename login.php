<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if($auth->isLoggedIn()) {
    header("Location: modules/dashboard/");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if($auth->login($username, $password)) {
        header("Location: modules/dashboard/");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Retail POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: url('image.png') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            margin: 0;
        }
        .overlay {
            background-color: rgba(0, 0, 0, 0.5);
            height: 100%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 0;
        }
        .login-wrapper {
            position: relative;
            display: flex;
            height: 100vh;
            z-index: 1;
        }
        .left-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            padding: 20px;
        }
        .left-section .logo img {
            width: 100px;
            height: auto;
            margin-bottom: 20px;
        }
        .left-section .title {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .left-section .subtitle {
            font-size: 1.2rem;
            margin-top: 10px;
            opacity: 0.9;
        }
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }
        .login-box {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 50px;
            color: #4e73df;
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="login-wrapper">
        <div class="left-section">
            <div class="logo">
                <img src="logo.png" alt="Retail POS Logo">
            </div>
            <div class="title">Retail POS System</div>
            <div class="subtitle">Fast. Simple. Efficient point-of-sale solution.</div>
        </div>
        <div class="login-container">
            <div class="login-box">
                <div class="login-logo">
                    <i class="fas fa-cash-register"></i>
                    <h2 class="mt-3">Retail POS</h2>
                </div>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
