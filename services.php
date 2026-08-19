<?php
require_once 'config/database.php';
require_once 'includes/session.php';

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();

$selected_category = $_GET['category'] ?? null;

if ($selected_category) {
    $stmt = $pdo->prepare("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.category_id = ? AND s.status='active' ORDER BY s.name");
    $stmt->execute([$selected_category]);
    $services = $stmt->fetchAll();
} else {
    $services = $pdo->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.status='active' ORDER BY c.name, s.name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services — Bluberry Nail Art Studio</title>
    <meta name="description" content="Browse our full range of premium nail services. From classic manicures to model-inspired nail art.">
    <link rel="stylesheet" href="/nail_salon/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="container">
        <a href="/nail_salon/index.php" class="logo">
            <span class="logo-icon"><span class="logo-glow"></span><i class="fas fa-spa"></i></span>
            <span class="logo-divider"></span>
            <span class="logo-text-wrap">
                <span class="logo-text">Blueberry</span>
                <span class="logo-subtitle">Nail Art Studio</span>
            </span>
        </a>
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <span class="menu-bar"></span>
            <span class="menu-bar"></span>
            <span class="menu-bar"></span>
        </button>
        <ul class="nav-links" id="navLinks">
            <li><a href="/nail_salon/index.php" class="nav-link-item">Home</a></li>
            <li><a href="/nail_salon/services.php" class="nav-link-item active">Services</a></li>
            <li><a href="/nail_salon/index.php#about" class="nav-link-item">About</a></li>
            <li><a href="/nail_salon/index.php#contact" class="nav-link-item">Contact</a></li>
            <li><a href="/nail_salon/auth/login.php" class="nav-link-item">Sign In</a></li>
            <li><a href="/nail_salon/auth/register.php" class="nav-btn-primary">Get Started</a></li>
        </ul>
    </div>
</nav>

<section class="page-header">
    <div class="container">
        <h1>Our <span>Services</span></h1>
        <p>From classic elegance to bold model-inspired designs — every service is crafted with care using natural, high-quality products. Browse our full menu below.</p>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container">
        <!-- Categories -->
        <div class="flex flex-wrap gap-2" style="margin-bottom:2.5rem;justify-content:center;">
            <a href="services.php" class="px-4 py-2 rounded-lg text-sm font-medium <?php echo !$selected_category ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50'; ?>">
                All Services
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="?category=<?php echo $cat['id']; ?>" class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $selected_category == $cat['id'] ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50'; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($services as $svc): ?>
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition group">
                <?php if ($svc['image']): ?>
                    <img src="/nail_salon/assets/uploads/<?php echo $svc['image']; ?>" alt="" class="w-full h-48 object-cover group-hover:scale-105 transition duration-300">
                <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                        <i class="fas fa-leaf text-blue-300 text-5xl"></i>
                    </div>
                <?php endif; ?>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($svc['name']); ?></h3>
                    <p class="text-sm text-gray-500 mb-3 line-clamp-2"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                    <div style="text-align:center">
                        <a href="javascript:void(0)" onclick="handleBook()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-1.5 rounded-lg transition inline-flex items-center">
                            <i class="fas fa-calendar-plus mr-1"></i>Book Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($services)): ?>
            <div class="col-span-full text-center py-12 text-gray-400">
                <i class="fas fa-spa text-4xl mb-3"></i>
                <p>No services available in this category yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3><i class="fas fa-spa"></i> Bluberry Nail Art Studio</h3>
                <p>Where fresh meets elegant. Premium nail artistry inspired by nature and fashion.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="/nail_salon/index.php">Home</a></li>
                    <li><a href="/nail_salon/services.php">Services</a></li>
                    <li><a href="/nail_salon/index.php#about">About</a></li>

                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <li><a href="/nail_salon/auth/login.php">Sign In</a></li>
                    <li><a href="/nail_salon/auth/register.php">Create Account</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get in Touch</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Visit Our Studio — No.15, Nilar Street, Pang Long, Shan State</li>
                    <li><i class="fas fa-phone"></i> Let's Talk — +95 9401 505 262</li>
                    <li><i class="fas fa-envelope"></i> Drop Us a Line — hello@blueberrynail.com</li>
                    <li><i class="fas fa-clock"></i> Studio Hours — 9:00 AM - 6:00 PM</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Bluberry Nail Art Studio. All rights reserved.</span>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
    </div>
</footer>

<script>
const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;

function handleBook() {
    if (isLoggedIn) {
        window.location.href = '/nail_salon/customer/booking.php';
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Login Required',
            text: 'Please log in or create an account to book an appointment.',
            showCancelButton: true,
            confirmButtonColor: '#3578c0',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-sign-in-alt"></i> Login',
            cancelButtonText: 'Cancel',
            showClass: { popup: 'animate__animated animate__fadeInUp' }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/nail_salon/auth/login.php';
            }
        });
    }
}

const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    menuToggle.classList.toggle('active');
});

navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        menuToggle.classList.remove('active');
    });
});
</script>

</body>
</html>
