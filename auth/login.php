<?php
require_once '../config/database.php';
require_once '../includes/session.php';

$error = '';
$success = '';

if (isset($_SESSION['registered']) && $_SESSION['registered']) {
    $success = 'Registration successful! You can now login.';
    unset($_SESSION['registered']);
}

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '/nail_salon/admin/dashboard.php' : '/nail_salon/customer/dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'Your account is inactive. Please contact admin.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['image'] = $user['image'] ?? null;

                header('Location: ' . ($user['role'] === 'admin' ? '/nail_salon/admin/dashboard.php' : '/nail_salon/customer/dashboard.php'));
                exit;
            }
        } else {
            $stmt2 = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE email = ?");
            $stmt2->execute([$email]);
            $exists = $stmt2->fetch()['cnt'] > 0;
            if (!$exists) {
                $error = 'No account found with that email.';
            } else {
                $error = 'Invalid password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Bluberry Nail Art Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/nail_salon/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-50 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md m-4">
        <div class="text-center mb-8 flex flex-col items-center">
            <a href="/nail_salon/index.php" class="logo mb-4 inline-flex" style="text-decoration:none;">
                <span class="logo-icon"><span class="logo-glow"></span><i class="fas fa-spa"></i></span>
                <span class="logo-divider"></span>
                <span class="logo-text-wrap text-left">
                    <span class="logo-text">Blueberry</span>
                    <span class="logo-subtitle">Nail Art Studio</span>
                </span>
            </a>
            <h1 class="text-3xl font-bold text-blue-900 mt-2">Welcome Back</h1>
            <p class="text-gray-500 mt-2">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-3 top-3 text-gray-400"></i>
                    <input type="email" name="email" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="you@example.com">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                    <input type="password" name="password" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i>Sign In
            </button>
        </form>

        <p class="text-center mt-6 text-gray-500">
            Don't have an account?
            <a href="register.php" class="text-blue-600 hover:text-blue-700 font-semibold">Register</a>
        </p>
        <p class="text-center mt-2">
            <a href="/nail_salon/index.php" class="text-gray-400 hover:text-blue-600 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Home
            </a>
        </p>
    </div>
</body>
</html>
