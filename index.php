<?php
require_once 'config/database.php';
require_once 'includes/session.php';

$services = $pdo->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.status='active' ORDER BY c.name")->fetchAll();
$staff = $pdo->query("SELECT * FROM staff WHERE status='available' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bluberry Nail Art Studio — Elegance in Every Stroke</title>
    <meta name="description" content="Premium nail artistry with a fresh, modern aesthetic. Book your session today.">
    <link rel="stylesheet" href="/nail_salon/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
            <li><a href="#home" class="nav-link-item">Home</a></li>
            <li><a href="#services" class="nav-link-item">Services</a></li>
            <li><a href="#staff" class="nav-link-item">Our Team</a></li>
            <li><a href="#about" class="nav-link-item">About</a></li>
            <li><a href="/nail_salon/auth/login.php" class="nav-link-item">Sign In</a></li>
            <li><a href="/nail_salon/auth/register.php" class="nav-btn-primary">Get Started</a></li>
        </ul>
    </div>
</nav>

<section class="hero" id="home">
    <div class="container">
        <div class="hero-content">
            <h1>Where <span>Fresh</span> Meets<br><span>Elegant</span> Nail Art</h1>
            <p>Experience the perfect blend of modern model aesthetics and blueberry-inspired freshness. Premium nail artistry crafted for the confident, elegant you.</p>
            <div class="hero-buttons">
                <a href="javascript:void(0)" onclick="handleBook()" class="btn-primary">
                    <i class="fas fa-calendar-check"></i> Book Your Session
                </a>
                <a href="#services" class="btn-outline">
                    <i class="fas fa-hand-sparkles"></i> Our Services
                </a>
            </div>
        </div>
        <div class="hero-image">
            <div class="model-frame" style="background:linear-gradient(145deg, var(--blueberry-100), var(--blueberry-50));overflow:hidden;">
                <span class="model-text">✦ Model Collection</span>
                <img src="/nail_salon/assets/uploads/services/home.jpg" alt="Nail Salon" style="width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
            </div>
            <div class="floating-card">
                <i class="fas fa-star"></i>
                <div>
                    <span>Premium Quality</span>
                    <small>Top-rated studio</small>
                </div>
            </div>
            <div class="floating-card">
                <i class="fas fa-leaf"></i>
                <div>
                    <span>Natural Care</span>
                    <small>Eco-friendly products</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- our services -->
<style>
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem}
.svc-card{background:white;border-radius:20px;overflow:hidden;box-shadow:0 4px 20px rgba(26,39,68,.06);border:1px solid rgba(74,144,217,.08);transition:all .4s ease;cursor:pointer;position:relative}
.svc-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(26,39,68,.12);border-color:var(--blueberry-300)}
.svc-card-img{position:relative;height:200px;overflow:hidden}
.svc-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
.svc-card:hover .svc-card-img img{transform:scale(1.08)}
.svc-card-img .svc-placeholder{width:100%;height:100%;background:linear-gradient(135deg,var(--blueberry-100),var(--blueberry-50));display:flex;align-items:center;justify-content:center}
.svc-card-img .svc-placeholder i{font-size:3rem;color:var(--blueberry-300)}
.svc-card-badge{position:absolute;top:.75rem;left:.75rem;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);color:var(--blueberry-700);font-size:.7rem;font-weight:600;padding:.3rem .8rem;border-radius:50px;border:1px solid var(--blueberry-200)}
.svc-card-price-tag{position:absolute;top:.75rem;right:.75rem;background:var(--blueberry-600);color:white;font-size:.85rem;font-weight:700;padding:.35rem .9rem;border-radius:12px;box-shadow:0 4px 12px rgba(53,120,192,.3)}
.svc-card-body{padding:1.2rem 1.4rem 1.4rem}
.svc-card-body h3{font-family:'Playfair Display',serif;font-size:1.15rem;color:var(--blueberry-900);margin:0 0 .3rem;line-height:1.3}
.svc-card-body .svc-desc{font-size:.85rem;color:var(--text-light);line-height:1.6;margin:0 0 1rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.svc-card-meta{display:flex;align-items:center;gap:1rem;padding-top:.9rem;border-top:1px solid #f3f4f6}
.svc-card-meta .svc-meta-item{display:flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--text-light)}
.svc-card-meta .svc-meta-item i{color:var(--blueberry-500);font-size:.85rem}
.svc-card-meta .svc-book-btn{background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white;border:none;padding:.55rem 1.2rem;border-radius:12px;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none}
.svc-card-meta .svc-book-btn:hover{background:linear-gradient(135deg,var(--blueberry-600),var(--blueberry-700));transform:translateY(-2px);box-shadow:0 6px 16px rgba(53,120,192,.25)}
@media(max-width:640px){.svc-grid{grid-template-columns:1fr}}
</style>
<section class="section" id="services">
    <div class="container">
        <div class="section-header">
            <span class="tag">Our Services</span>
            <h2>Fresh <span>Blueberry</span> Treatments</h2>
            <p>From classic elegance to bold model-inspired designs — every service is crafted with care using natural, high-quality products.</p>
        </div>
        <div class="svc-grid">
            <?php foreach ($services as $svc): ?>
            <div class="svc-card">
                <div class="svc-card-img">
                    <?php if ($svc['image']): ?>
                        <img src="/nail_salon/assets/uploads/<?php echo $svc['image']; ?>" alt="<?php echo htmlspecialchars($svc['name']); ?>">
                    <?php else: ?>
                        <div class="svc-placeholder"><i class="fas fa-leaf"></i></div>
                    <?php endif; ?>
                    <span class="svc-card-badge"><?php echo htmlspecialchars($svc['category_name']); ?></span>
                    <span class="svc-card-price-tag">MMK<?php echo number_format($svc['price'], 2); ?></span>
                </div>
                <div class="svc-card-body">
                    <h3><?php echo htmlspecialchars($svc['name']); ?></h3>
                    <p class="svc-desc"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                    <div class="svc-card-meta">
                        <span class="svc-meta-item"><i class="fas fa-clock"></i> Duration: <?php echo $svc['duration']; ?> min</span>
                        <a href="javascript:void(0)" class="svc-book-btn" onclick="event.stopPropagation();handleBook()"><i class="fas fa-calendar-plus"></i> Book</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- staff -->
<?php if (!empty($staff)): ?>
<section class="section staff-section" id="staff">
    <div class="container">
        <div class="section-header">
            <span class="tag">Our Team</span>
            <h2>Meet Our <span>Artists</span></h2>
            <p>Talented nail artists dedicated to bringing your vision to life with precision and creativity.</p>
        </div>
        <div class="staff-grid">
            <?php foreach ($staff as $s): ?>
            <div class="staff-card" style="padding:0;overflow:hidden">
                <div style="height:80px;background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));position:relative">
                    <div style="position:absolute;bottom:-35px;left:50%;transform:translateX(-50%);width:72px;height:72px;border-radius:50%;background:white;padding:3px;box-shadow:0 4px 15px rgba(53,120,192,.2)">
                        <div style="width:100%;height:100%;border-radius:50%;overflow:hidden;background:var(--blueberry-100);display:flex;align-items:center;justify-content:center">
                            <?php if ($s['photo']): ?>
                            <img src="/nail_salon/assets/uploads/<?php echo htmlspecialchars($s['photo']); ?>" alt="<?php echo htmlspecialchars($s['name']); ?>" style="width:100%;height:100%;object-fit:cover">
                            <?php else: ?>
                            <i class="fas fa-user" style="font-size:1.8rem;color:var(--blueberry-400)"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="padding:2.8rem 1.5rem 1.5rem;text-align:center">
                    <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--blueberry-900);margin:0 0 .25rem"><?php echo htmlspecialchars($s['name']); ?></h3>
                    <p style="font-size:.8rem;color:var(--blueberry-500);font-weight:600;margin:0 0 .6rem"><?php echo htmlspecialchars($s['specialization'] ?? 'Nail Artist'); ?></p>
                    <?php if ($s['status'] !== 'available'): ?>
                    <span style="display:inline-block;padding:.25rem .75rem;border-radius:50px;font-size:.7rem;font-weight:600;margin-bottom:.8rem
                        <?php echo match($s['status']) { 'busy' => 'background:#fef3c7;color:#b45309', 'off' => 'background:#fee2e2;color:#dc2626', default => 'background:#f3f4f6;color:#6b7280' }; ?>">
                        <i class="fas fa-circle" style="font-size:5px;margin-right:4px;vertical-align:middle"></i><?php echo ucfirst($s['status']); ?>
                    </span>
                    <?php endif; ?>
                    <div style="display:flex;gap:.6rem;justify-content:center;margin-top:.6rem">
                        <?php if ($s['status'] === 'available'): ?>
                        <a href="javascript:void(0)" onclick="handleBook()" style="background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white;text-decoration:none;padding:.5rem 1rem;border-radius:10px;font-size:.75rem;font-weight:600;display:inline-flex;align-items:center;gap:.3rem;transition:all .3s">
                            <i class="fas fa-calendar-plus"></i> Book
                        </a>
                        <?php endif; ?>
                        <a href="/nail_salon/customer/staff_profile.php?id=<?php echo $s['id']; ?>" style="color:var(--blueberry-600);text-decoration:none;padding:.5rem 1rem;border-radius:10px;font-size:.75rem;font-weight:600;border:1.5px solid var(--blueberry-200);display:inline-flex;align-items:center;gap:.3rem;transition:all .3s;background:transparent">
                            View Profile <i class="fas fa-arrow-right" style="font-size:.65rem"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- about -->
<section class="section about-section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <div class="model-frame-2" style="overflow:hidden;padding:0;">
                    <div id="aboutMap" style="width:100%;height:100%;min-height:400px;border-radius:30px;z-index:1;"></div>
                </div>
            </div>
            <div class="about-content">
                <div class="section-header" style="text-align:center;margin-bottom:1rem;">
                    <span class="tag">About Us</span>
                </div>
                <h2>Where <span>Beauty</span> Meets <span>Elegance</span></h2>
                <p>At Bluberry Nail Art Studio, we believe your nails are your ultimate accessory. Our studio combines the natural, nourishing power of blueberry-based treatments with cutting-edge nail artistry inspired by the latest fashion trends.</p>
                <p>Every session is designed to be a moment of self-care — whether you're preparing for a special event or treating yourself to a well-deserved break.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8 mb-8 mt-6">
                    <div class="flex items-center gap-3"><i class="fas fa-check-circle text-[#4285f4] text-xl"></i><span class="text-[#0f2142] font-medium text-[15px]">Premium Beauty Products</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-check-circle text-[#4285f4] text-xl"></i><span class="text-[#0f2142] font-medium text-[15px]">Expert Nail Artists</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-check-circle text-[#4285f4] text-xl"></i><span class="text-[#0f2142] font-medium text-[15px]">Hygienic & Luxurious</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-check-circle text-[#4285f4] text-xl"></i><span class="text-[#0f2142] font-medium text-[15px]">Trendy Beauty Designs</span></div>
                </div>
                
                <div class="flex flex-col gap-4">
                    <div class="flex items-center bg-white border border-blue-100 rounded-2xl py-3 px-4 shadow-sm relative overflow-hidden" style="min-height: 70px;">
                        <div class="w-[48px] h-[48px] bg-[#3a7fe4] rounded-xl flex items-center justify-center text-white absolute left-4 shadow-sm">
                            <i class="fas fa-map-marker-alt text-[1.1rem]"></i>
                        </div>
                        <div class="w-full text-center pl-[50px] pr-4">
                            <h4 class="font-bold text-[#0f2142] text-[15px] m-0 leading-tight">Visit Our Studio</h4>
                            <p class="text-[14px] text-slate-500 m-0 mt-1 leading-tight">No.15, Nilar Street, Pang Long, Shan State</p>
                        </div>
                    </div>

                    <div class="flex items-center bg-white border border-blue-100 rounded-2xl py-3 px-4 shadow-sm relative overflow-hidden" style="min-height: 70px;">
                        <div class="w-[48px] h-[48px] bg-[#3a7fe4] rounded-xl flex items-center justify-center text-white absolute left-4 shadow-sm">
                            <i class="fas fa-phone text-[1.1rem]" style="transform: scaleX(-1);"></i>
                        </div>
                        <div class="w-full text-center pl-[50px] pr-4">
                            <h4 class="font-bold text-[#0f2142] text-[15px] m-0 leading-tight">Let's Talk</h4>
                            <p class="text-[14px] text-slate-500 m-0 mt-1 leading-tight">+95 9401 505 262</p>
                        </div>
                    </div>

                    <div class="flex items-center bg-white border border-blue-100 rounded-2xl py-3 px-4 shadow-sm relative overflow-hidden" style="min-height: 70px;">
                        <div class="w-[48px] h-[48px] bg-[#3a7fe4] rounded-xl flex items-center justify-center text-white absolute left-4 shadow-sm">
                            <i class="fas fa-envelope text-[1.1rem]"></i>
                        </div>
                        <div class="w-full text-center pl-[50px] pr-4">
                            <h4 class="font-bold text-[#0f2142] text-[15px] m-0 leading-tight">Drop Us a Line</h4>
                            <p class="text-[14px] text-slate-500 m-0 mt-1 leading-tight">hello@blueberrynail.com</p>
                        </div>
                    </div>

                    <div class="flex items-center bg-white border border-blue-100 rounded-2xl py-3 px-4 shadow-sm relative overflow-hidden" style="min-height: 70px;">
                        <div class="w-[48px] h-[48px] bg-[#3a7fe4] rounded-xl flex items-center justify-center text-white absolute left-4 shadow-sm">
                            <i class="fas fa-clock text-[1.1rem]"></i>
                        </div>
                        <div class="w-full text-center pl-[50px] pr-4">
                            <h4 class="font-bold text-[#0f2142] text-[15px] m-0 leading-tight">Studio Hours</h4>
                            <p class="text-[14px] text-slate-500 m-0 mt-1 leading-tight">9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- book -->
<section class="cta-section">
    <div class="container">
        <h2>Ready for a Fresh Look?</h2>
        <p>Join the Bluberry Nail Art Studio community and experience nail artistry that combines natural care with model-worthy elegance.</p>
        <a href="javascript:void(0)" onclick="handleBook()" class="btn-primary">
            <i class="fas fa-calendar-check"></i> Book Your Appointment
        </a>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3><i class="fas fa-leaf"></i> Bluberry Nail Art Studio</h3>
                <p>Where fresh meets elegant. Premium nail artistry inspired by nature and fashion.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#home">Home</a></li>
            <li><a href="/nail_salon/services.php">Services</a></li>
                    <li><a href="#about">About</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <li><a href="/nail_salon/auth/login.php">Sign In</a></li>
                    <li><a href="/nail_salon/auth/register.php">Create Account</a></li>
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

// Navbar scroll effect
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Mobile menu toggle
const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    menuToggle.classList.toggle('active');
});

// Close mobile menu on link click
navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        menuToggle.classList.remove('active');
    });
});

// Smooth scroll offset for fixed navbar
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offset = 80;
            const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    });
});
</script>

<script>
(function(){
    var aboutMap = L.map('aboutMap', {
        scrollWheelZoom: false,
        zoomControl: false,
        attributionControl: true
    }).setView([20.9917, 97.5208], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(aboutMap);

    L.control.zoom({ position: 'topright' }).addTo(aboutMap);

    var aboutIcon = L.divIcon({
        html: '<div style="background:linear-gradient(135deg,#e53935,#c62828);width:36px;height:36px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 4px 14px rgba(229,57,53,0.45);display:flex;align-items:center;justify-content:center;animation:markerBounce 0.6s ease"><div style="transform:rotate(45deg);color:#fff;font-size:16px;"><i class="fas fa-map-marker-alt"></i></div></div>',
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -38],
        className: ''
    });

    var popupContent = '<div style="min-width:220px;font-family:Inter,sans-serif">' +
        '<div style="background:linear-gradient(135deg,#1a2744,#2563a0);color:#fff;padding:12px 14px;border-radius:12px 12px 0 0;margin:-9px -20px 12px -8px">' +
        '<b style="font-size:14px;font-family:Playfair Display,serif">Bluberry Nail Art Studio</b></div>' +
        '<div style="padding:0 2px">' +
        '<p style="margin:0 0 8px;font-size:12.5px;color:#555;line-height:1.5"><i class="fas fa-map-marker-alt" style="color:#e53935;margin-right:5px"></i>No.15, Nilar Street, Pang Long, Shan State, Burma</p>' +
        '<p style="margin:0 0 10px;font-size:12.5px;color:#555;line-height:1.5"><i class="fas fa-phone" style="color:#3578c0;margin-right:5px"></i>+95 9401 505 262</p>' +
        '<a href="https://www.openstreetmap.org/?mlat=20.9917&mlon=97.5208#map=16/20.9917/97.5208" target="_blank" rel="noopener" ' +
        'style="display:inline-block;background:linear-gradient(135deg,#3578c0,#2563a0);color:#fff;padding:6px 14px;border-radius:8px;font-size:11.5px;font-weight:600;text-decoration:none;transition:all 0.2s">' +
        '<i class="fas fa-directions" style="margin-right:4px"></i>Get Directions</a>' +
        '</div></div>';

    L.marker([20.9917, 97.5208], {icon: aboutIcon}).addTo(aboutMap)
        .bindPopup(popupContent, { maxWidth: 280, closeButton: true })
        .openPopup();

    var mapContainer = document.getElementById('aboutMap');

    mapContainer.addEventListener('mouseenter', function(){
        aboutMap.scrollWheelZoom.enable();
    });
    mapContainer.addEventListener('mouseleave', function(){
        aboutMap.scrollWheelZoom.disable();
    });

    mapContainer.addEventListener('touchstart', function(e){
        if(e.touches.length === 2){
            aboutMap.scrollWheelZoom.enable();
        }
    }, {passive: true});
    mapContainer.addEventListener('touchend', function(){
        aboutMap.scrollWheelZoom.disable();
    }, {passive: true});

    mapContainer.addEventListener('wheel', function(e){
        e.stopPropagation();
    }, {passive: false});
    document.addEventListener('wheel', function(e){
        if(mapContainer.contains(e.target)){
            e.stopPropagation();
        }
    }, {passive: false});

    setTimeout(function(){ aboutMap.invalidateSize(); }, 300);
    setTimeout(function(){ aboutMap.invalidateSize(); }, 800);

    var resizeTimer;
    window.addEventListener('resize', function(){
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function(){ aboutMap.invalidateSize(); }, 200);
    });

    var style = document.createElement('style');
    style.textContent = '@keyframes markerBounce{0%{transform:rotate(-45deg) scale(0);opacity:0}60%{transform:rotate(-45deg) scale(1.15)}100%{transform:rotate(-45deg) scale(1);opacity:1}}';
    document.head.appendChild(style);
})();
</script>

</body>
</html>
