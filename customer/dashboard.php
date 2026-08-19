<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireCustomer();

$title = 'Home';

$stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
if (!$stmt->fetch()) {
    $pdo->exec("CREATE TABLE reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        service_id INT NOT NULL,
        staff_id INT NOT NULL,
        appointment_id INT NOT NULL UNIQUE,
        rating TINYINT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
        FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
    )");
}

$reviews = $pdo->prepare("
    SELECT r.*, s.name as service_name, st.name as staff_name, u.full_name as customer_name
    FROM reviews r
    JOIN services s ON r.service_id = s.id
    JOIN staff st ON r.staff_id = st.id
    JOIN users u ON r.customer_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 6
");
$reviews->execute();
$reviews = $reviews->fetchAll();

$services = $pdo->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.status='active' ORDER BY c.name")->fetchAll();

$staff = $pdo->query("SELECT * FROM staff ORDER BY name")->fetchAll();

require_once '../includes/header.php';
?>

<section class="hero" style="min-height:100vh;padding:2.5rem 0 3rem;">
    <div class="container">
        <div class="hero-content">
            <h1>Welcome back<br><span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span></h1>
            <p>Your next fresh look is just a click away. Browse our services or check your upcoming appointments.</p>
            <div class="hero-buttons">
                <a href="booking.php" class="btn-primary">
                    <i class="fas fa-calendar-check"></i> Book Your Session
                </a>
                <a href="appointments.php" class="btn-outline">
                    <i class="fas fa-clock"></i> My Appointments
                </a>
            </div>
        </div>
        <div class="hero-image">
            <div class="model-frame" style="background:linear-gradient(145deg, var(--blueberry-100), var(--blueberry-50));overflow:hidden;">
                <span class="model-text">✦ Welcome</span>
                <img src="/nail_salon/assets/uploads/services/home.jpg?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/nail_salon/assets/uploads/services/home.jpg') ?>" alt="Nail Salon" style="width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
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

<?php if (!empty($reviews)): ?>
<section class="section" style="padding:3rem 2rem;background:var(--blueberry-50);">
    <div class="container">
        <div class="section-header">
            <span class="tag">Reviews</span>
            <h2>What Our <span>Clients</span> Say</h2>
            <p>Real feedback from our happy customers.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;max-width:1000px;margin:0 auto;">
            <?php foreach ($reviews as $rev): ?>
            <div style="background:white;border-radius:16px;padding:1.5rem;box-shadow:0 2px 12px rgba(0,0,0,0.04);border:1px solid rgba(74,144,217,0.08);">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.8rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star" style="color:<?php echo $i <= $rev['rating'] ? '#f59e0b' : '#d1d5db'; ?>;font-size:0.85rem;"></i>
                    <?php endfor; ?>
                    <span style="font-size:0.8rem;color:var(--text-light);margin-left:0.3rem;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                </div>
                <?php if (!empty($rev['comment'])): ?>
                <p style="font-size:0.9rem;color:var(--dark);line-height:1.6;margin:0 0 1rem;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                <?php endif; ?>
                <div style="display:flex;align-items:center;gap:0.8rem;padding-top:0.8rem;border-top:1px solid var(--blueberry-100);">
                    <div style="width:36px;height:36px;background:var(--blueberry-100);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-user" style="color:var(--blueberry-600);font-size:0.8rem;"></i>
                    </div>
                    <div>
                        <p style="font-weight:600;color:var(--dark);margin:0;font-size:0.85rem;"><?php echo htmlspecialchars($rev['customer_name']); ?></p>
                        <p style="font-size:0.75rem;color:var(--text-light);margin:0;"><?php echo htmlspecialchars($rev['service_name']); ?> with <?php echo htmlspecialchars($rev['staff_name']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

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
                        <a href="booking.php?service=<?php echo $svc['id']; ?>" class="svc-book-btn" onclick="event.stopPropagation()"><i class="fas fa-calendar-plus"></i> Book</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

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
                        <a href="booking.php?staff=<?php echo $s['id']; ?>" style="background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white;text-decoration:none;padding:.5rem 1rem;border-radius:10px;font-size:.75rem;font-weight:600;display:inline-flex;align-items:center;gap:.3rem;transition:all .3s">
                            <i class="fas fa-calendar-plus"></i> Book
                        </a>
                        <a href="staff_profile.php?id=<?php echo $s['id']; ?>" style="color:var(--blueberry-600);text-decoration:none;padding:.5rem 1rem;border-radius:10px;font-size:.75rem;font-weight:600;border:1.5px solid var(--blueberry-200);display:inline-flex;align-items:center;gap:.3rem;transition:all .3s;background:transparent">
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

<section class="section about-section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <div class="model-frame-2" style="overflow:hidden;">
                    <div id="aboutMap" style="width:100%;height:100%;"></div>
                </div>
                <script>
                (function(){
                    var m = L.map('aboutMap', {
                        scrollWheelZoom: false,
                        zoomControl: false,
                        attributionControl: true
                    }).setView([20.9917, 97.5208], 16);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
                        maxZoom: 19
                    }).addTo(m);

                    L.control.zoom({ position: 'topright' }).addTo(m);

                    var icon = L.divIcon({
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
                        '<p style="margin:0 0 8px;font-size:12.5px;color:#555;line-height:1.5"><i class="fas fa-map-marker-alt" style="color:#e53935;margin-right:5px"></i>No.15, Nilar Street, Pang Long, Shan State</p>' +
                        '<p style="margin:0 0 10px;font-size:12.5px;color:#555;line-height:1.5"><i class="fas fa-phone" style="color:#3578c0;margin-right:5px"></i>+95 9401 505 262</p>' +
                        '<a href="https://www.openstreetmap.org/?mlat=20.9917&mlon=97.5208#map=16/20.9917/97.5208" target="_blank" rel="noopener" ' +
                        'style="display:inline-block;background:linear-gradient(135deg,#3578c0,#2563a0);color:#fff;padding:6px 14px;border-radius:8px;font-size:11.5px;font-weight:600;text-decoration:none;transition:all 0.2s">' +
                        '<i class="fas fa-directions" style="margin-right:4px"></i>Get Directions</a>' +
                        '</div></div>';

                    L.marker([20.9917, 97.5208], {icon: icon}).addTo(m)
                        .bindPopup(popupContent, { maxWidth: 280, closeButton: true })
                        .openPopup();

                    var mapContainer = document.getElementById('aboutMap');

                    mapContainer.addEventListener('mouseenter', function(){
                        m.scrollWheelZoom.enable();
                    });
                    mapContainer.addEventListener('mouseleave', function(){
                        m.scrollWheelZoom.disable();
                    });

                    mapContainer.addEventListener('touchstart', function(e){
                        if(e.touches.length === 2){
                            m.scrollWheelZoom.enable();
                        }
                    }, {passive: true});
                    mapContainer.addEventListener('touchend', function(){
                        m.scrollWheelZoom.disable();
                    }, {passive: true});

                    mapContainer.addEventListener('wheel', function(e){
                        e.stopPropagation();
                    }, {passive: false});
                    document.addEventListener('wheel', function(e){
                        if(mapContainer.contains(e.target)){
                            e.stopPropagation();
                        }
                    }, {passive: false});

                    setTimeout(function(){ m.invalidateSize(); }, 300);
                    setTimeout(function(){ m.invalidateSize(); }, 800);

                    var resizeTimer;
                    window.addEventListener('resize', function(){
                        clearTimeout(resizeTimer);
                        resizeTimer = setTimeout(function(){ m.invalidateSize(); }, 200);
                    });

                    var style = document.createElement('style');
                    style.textContent = '@keyframes markerBounce{0%{transform:rotate(-45deg) scale(0);opacity:0}60%{transform:rotate(-45deg) scale(1.15)}100%{transform:rotate(-45deg) scale(1);opacity:1}}';
                    document.head.appendChild(style);
                })();
                </script>
            </div>
            <div class="about-content">
                <div class="section-header" style="text-align:center;margin-bottom:1rem;">
                    <span class="tag">About Us</span>
                </div>
                <h2>Where <span>Beauty</span> Meets <span>Elegance</span></h2>
                <p>At Bluberry Nail Art Studio, we believe beauty is an art. Our skilled artists craft stunning nail designs that celebrate your unique style — from timeless classics to bold, fashion-forward looks.</p>
                <p>Indulge in a luxurious beauty experience designed to pamper, refresh, and inspire. Whether it's a special occasion or your everyday self-care ritual, we're here to make you feel radiant.</p>
                <div class="about-features">
                    <div class="about-feature"><i class="fas fa-check-circle"></i><span>Premium Beauty Products</span></div>
                    <div class="about-feature"><i class="fas fa-check-circle"></i><span>Expert Nail Artists</span></div>
                    <div class="about-feature"><i class="fas fa-check-circle"></i><span>Hygienic & Luxurious</span></div>
                    <div class="about-feature"><i class="fas fa-check-circle"></i><span>Trendy Beauty Designs</span></div>
                </div>
                <div class="about-contact-list">
                    <div class="about-contact-card">
                        <div class="about-contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="about-contact-text">
                            <span class="about-contact-label">Visit Our Studio</span>
                            <span class="about-contact-value">No.15, Nilar Street, Pang Long, Shan State</span>
                        </div>
                    </div>
                    <div class="about-contact-card">
                        <div class="about-contact-icon"><i class="fas fa-phone"></i></div>
                        <div class="about-contact-text">
                            <span class="about-contact-label">Let's Talk</span>
                            <span class="about-contact-value">+95 9401 505 262</span>
                        </div>
                    </div>
                    <div class="about-contact-card">
                        <div class="about-contact-icon"><i class="fas fa-envelope"></i></div>
                        <div class="about-contact-text">
                            <span class="about-contact-label">Drop Us a Line</span>
                            <span class="about-contact-value">hello@blueberrynail.com</span>
                        </div>
                    </div>
                    <div class="about-contact-card">
                        <div class="about-contact-icon"><i class="fas fa-clock"></i></div>
                        <div class="about-contact-text">
                            <span class="about-contact-label">Studio Hours</span>
                            <span class="about-contact-value">9:00 AM - 6:00 PM</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready for a Fresh Look?</h2>
        <p>Join the Bluberry Nail Art Studio community and experience nail artistry that combines natural care with model-worthy elegance.</p>
        <a href="booking.php" class="btn-primary">
            <i class="fas fa-calendar-check"></i> Book Your Appointment
        </a>
    </div>
</section>

</main>

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
                    <li><a href="/nail_salon/customer/dashboard.php">Home</a></li>
                    <li><a href="/nail_salon/customer/services.php">Services</a></li>
                    <li><a href="/nail_salon/customer/staff.php">Our Team</a></li>
                    <li><a href="/nail_salon/customer/booking.php">Book Now</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <li><a href="/nail_salon/customer/profile.php">My Profile</a></li>
                    <li><a href="/nail_salon/auth/change-password.php">Change Password</a></li>
                    <li><a href="/nail_salon/auth/logout.php">Logout</a></li>
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

</body>
</html>
