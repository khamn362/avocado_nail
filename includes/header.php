<?php
require_once __DIR__ . '/notifications.php';

$stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
if (!$stmt->fetch()) {
    $pdo->exec("CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        appointment_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE INDEX idx_notifications_user ON notifications(user_id, is_read)");
}

$unread_count = isLoggedIn() ? getUnreadCount($pdo, $_SESSION['user_id']) : 0;
$recent_notifs = isLoggedIn() ? getRecentNotifications($pdo, $_SESSION['user_id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bluberry Nail Art Studio - <?php echo $title ?? 'Dashboard'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/nail_salon/assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    function toggleNotifDropdown(id) {
        var dd = document.getElementById(id);
        dd.classList.toggle('hidden');
    }
    document.addEventListener('click', function(e) {
        document.querySelectorAll('[id$="NotifDropdown"]').forEach(function(dd) {
            if (!dd.classList.contains('hidden') && !dd.contains(e.target) && !e.target.closest('[onclick*="' + dd.id + '"]') && !e.target.closest('[data-notif-bell]')) {
                dd.classList.add('hidden');
            }
        });
    });
    var _lastUnreadCount = <?php echo $unread_count; ?>;
    function pollNotifications() {
        fetch('/nail_salon/includes/notifications_api.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) return;
                var badges = document.querySelectorAll('.notif-badge-count');
                badges.forEach(function(b) {
                    if (data.unread_count > 0) {
                        b.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        b.style.display = 'flex';
                    } else {
                        b.style.display = 'none';
                    }
                });
                if (data.unread_count > _lastUnreadCount && _lastUnreadCount >= 0) {
                    var latest = data.notifications[0];
                    if (latest && !latest.is_read) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: latest.title,
                            text: latest.message,
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true
                        });
                    }
                }
                _lastUnreadCount = data.unread_count;
                var bodies = document.querySelectorAll('.notif-dropdown-body');
                bodies.forEach(function(body) {
                    if (data.notifications.length === 0) {
                        body.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">No notifications yet</p>';
                        return;
                    }
                    var isAdmin = !!document.getElementById('adminSidebar');
                var role = isAdmin ? 'admin' : 'customer';
                    var html = '';
                    data.notifications.forEach(function(n) {
                        html += '<a href="/nail_salon/' + role + '/notifications.php' + (n.appointment_id ? '?appointment_id=' + n.appointment_id : '') + '" class="notif-item ' + (n.is_read ? '' : 'notif-unread') + '">';
                        html += '<p class="notif-title">' + n.title + '</p>';
                        html += '<p class="notif-msg">' + n.message + '</p>';
                        html += '<p class="notif-time">' + n.created_at + '</p>';
                        html += '</a>';
                    });
                    body.innerHTML = html;
                });
            })
            .catch(function() {});
    }
    setInterval(pollNotifications, 30000);
    </script>
</head>
<body>

<?php if (isAdmin()): ?>
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand">
            <a href="/nail_salon/admin/dashboard.php" class="admin-logo">
                <span class="admin-logo-icon"><i class="fas fa-spa"></i></span>
                <span class="admin-logo-text-wrap">
                    <span class="admin-logo-text">Blueberry</span>
                    <span class="admin-logo-subtitle">Nail Art Studio</span>
                </span>
            </a>
            <button id="sidebarClose" class="admin-sidebar-close"><i class="fas fa-times"></i></button>
        </div>
        <nav class="admin-sidebar-nav">
            <a href="/nail_salon/admin/dashboard.php" class="admin-sidebar-link <?php echo $currentPage==='dashboard.php'?'active':''; ?>"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="/nail_salon/admin/categories.php" class="admin-sidebar-link <?php echo $currentPage==='categories.php'?'active':''; ?>"><i class="fas fa-tags"></i><span>Service Categories</span></a>
            <a href="/nail_salon/admin/services.php" class="admin-sidebar-link <?php echo $currentPage==='services.php'?'active':''; ?>"><i class="fas fa-hand-sparkles"></i><span>Services</span></a>
            <a href="/nail_salon/admin/staff.php" class="admin-sidebar-link <?php echo $currentPage==='staff.php'?'active':''; ?>"><i class="fas fa-users"></i><span>Staff</span></a>
            <a href="/nail_salon/admin/appointments.php" class="admin-sidebar-link <?php echo $currentPage==='appointments.php'?'active':''; ?>"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
            <a href="/nail_salon/admin/customers.php" class="admin-sidebar-link <?php echo $currentPage==='customers.php'?'active':''; ?>"><i class="fas fa-user-friends"></i><span>Customers</span></a>
            <a href="/nail_salon/admin/reports.php" class="admin-sidebar-link <?php echo $currentPage==='reports.php'?'active':''; ?>"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="javascript:void(0)" onclick="toggleNotifDropdown('adminNotifDropdown')" class="admin-sidebar-link <?php echo $currentPage==='notifications.php'?'active':''; ?>" data-notif-bell><i class="fas fa-bell"></i><span>Notifications</span><span class="admin-sidebar-badge notif-badge-count" style="<?php echo $unread_count<=0?'display:none;':''; ?>"><?php echo $unread_count>9?'9+':$unread_count; ?></span></a>
            <div class="admin-notif-dropdown hidden" id="adminNotifDropdown">
                <div class="notif-dropdown-header">
                    <span>Notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <a href="/nail_salon/admin/notifications.php?mark_read=all" class="text-xs text-blue-600">Mark all read</a>
                    <?php endif; ?>
                </div>
                <div class="notif-dropdown-body">
                    <?php if (empty($recent_notifs)): ?>
                        <p class="text-xs text-gray-400 text-center py-6">No notifications yet</p>
                    <?php else: ?>
                        <?php foreach ($recent_notifs as $n): ?>
                            <a href="/nail_salon/admin/notifications.php<?php echo $n['appointment_id'] ? '?appointment_id=' . $n['appointment_id'] : ''; ?>" class="notif-item <?php echo $n['is_read'] ? '' : 'notif-unread'; ?>">
                                <p class="notif-title"><?php echo htmlspecialchars($n['title']); ?></p>
                                <p class="notif-msg"><?php echo htmlspecialchars($n['message']); ?></p>
                                <p class="notif-time"><?php echo date('M d, h:i A', strtotime($n['created_at'])); ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="/nail_salon/admin/notifications.php" class="notif-view-all">View All</a>
            </div>
        </nav>
        <div class="admin-sidebar-footer">
            <div class="admin-sidebar-user">
                <?php if(!empty($_SESSION['image'])): ?>
                    <img src="/nail_salon/assets/images/<?php echo htmlspecialchars($_SESSION['image']); ?>" class="admin-sidebar-avatar">
                <?php else: ?>
                    <i class="fas fa-user-circle admin-sidebar-avatar-icon"></i>
                <?php endif; ?>
                <span class="admin-sidebar-username"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
            </div>
            <a href="/nail_salon/auth/logout.php" class="admin-sidebar-link admin-sidebar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>
    <div class="admin-overlay" id="adminOverlay">    </div>
    <button id="adminMobileToggle" class="admin-mobile-toggle"><i class="fas fa-bars"></i></button>
    <script>
    document.getElementById('adminOverlay')?.addEventListener('click',function(){
        document.getElementById('adminSidebar').classList.remove('open');
        document.getElementById('adminOverlay').classList.remove('show');
    });
    document.getElementById('adminMobileToggle')?.addEventListener('click',function(){
        document.getElementById('adminSidebar').classList.add('open');
        document.getElementById('adminOverlay').classList.add('show');
    });
    document.getElementById('sidebarClose')?.addEventListener('click',function(){
        document.getElementById('adminSidebar').classList.remove('open');
        document.getElementById('adminOverlay').classList.remove('show');
    });
    </script>
    <main class="admin-content">
<?php else: ?>
<?php $customerPage = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar" id="navbar">
    <div class="container">
        <a href="/nail_salon/customer/dashboard.php" class="logo">
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
            <li><a href="/nail_salon/customer/dashboard.php" class="nav-link-item <?php echo $customerPage==='dashboard.php'?'active':''; ?>">Home</a></li>
            <li><a href="/nail_salon/customer/services.php" class="nav-link-item <?php echo $customerPage==='services.php'?'active':''; ?>">Services</a></li>
            <li><a href="/nail_salon/customer/staff.php" class="nav-link-item <?php echo $customerPage==='staff.php'?'active':''; ?>">Our Team</a></li>
            <li class="nav-separator"></li>
            <li><a href="/nail_salon/customer/appointments.php" class="nav-btn-outline"><i class="fas fa-clock"></i> My Appointments</a></li>
            <li><a href="/nail_salon/customer/booking.php" class="nav-btn-primary"><i class="fas fa-calendar-check"></i> Book Now</a></li>
            <li class="nav-notif-item">
                <a href="javascript:void(0)" onclick="toggleNotifDropdown('custNotifDropdown')" class="nav-notif-btn" data-notif-bell>
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge-count" style="<?php echo $unread_count<=0?'display:none;':''; ?>"><?php echo $unread_count>9?'9+':$unread_count; ?></span>
                </a>
                <div class="notif-dropdown hidden" id="custNotifDropdown">
                    <div class="notif-dropdown-header">
                        <span>Notifications</span>
                        <?php if ($unread_count > 0): ?>
                            <a href="/nail_salon/customer/notifications.php?mark_read=all" class="text-xs text-blue-600">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <div class="notif-dropdown-body">
                        <?php if (empty($recent_notifs)): ?>
                            <p class="text-xs text-gray-400 text-center py-6">No notifications yet</p>
                        <?php else: ?>
                            <?php foreach ($recent_notifs as $n): ?>
                                <a href="/nail_salon/customer/notifications.php<?php echo $n['appointment_id'] ? '?appointment_id=' . $n['appointment_id'] : ''; ?>" class="notif-item <?php echo $n['is_read'] ? '' : 'notif-unread'; ?>">
                                    <p class="notif-title"><?php echo htmlspecialchars($n['title']); ?></p>
                                    <p class="notif-msg"><?php echo htmlspecialchars($n['message']); ?></p>
                                    <p class="notif-time"><?php echo date('M d, h:i A', strtotime($n['created_at'])); ?></p>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="/nail_salon/customer/notifications.php" class="notif-view-all">View All</a>
                </div>
            </li>
            <li class="nav-user-item">
                <a href="javascript:void(0)" onclick="toggleUserDropdown()" class="nav-user-btn">
                    <?php if (!empty($_SESSION['image'])): ?>
                        <img src="/nail_salon/assets/images/<?php echo htmlspecialchars($_SESSION['image']); ?>" class="nav-user-avatar">
                    <?php else: ?>
                        <i class="fas fa-user-circle nav-user-icon"></i>
                    <?php endif; ?>
                    <span class="nav-user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'User')[0]); ?></span>
                    <i class="fas fa-chevron-down nav-chevron"></i>
                </a>
                <div class="user-dropdown" id="userDropdown">
                    <a href="/nail_salon/customer/review.php"><i class="fas fa-star"></i> Reviews</a>
                    <hr>
                    <a href="/nail_salon/customer/profile.php"><i class="fas fa-user-edit"></i> Profile</a>
                    <a href="/nail_salon/auth/change-password.php"><i class="fas fa-key"></i> Change Password</a>
                    <hr>
                    <a href="/nail_salon/auth/logout.php" style="color:#dc2626;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<div class="nav-drawer-overlay" id="navOverlay"></div>
<div class="nav-drawer" id="navDrawer">
    <div class="nav-drawer-header">
        <div class="nav-drawer-user">
            <?php if (!empty($_SESSION['image'])): ?>
                <img src="/nail_salon/assets/images/<?php echo htmlspecialchars($_SESSION['image']); ?>" class="nav-drawer-avatar">
            <?php else: ?>
                <div class="nav-drawer-avatar-placeholder"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <div>
                <p class="nav-drawer-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
                <p class="nav-drawer-subtitle">Customer</p>
            </div>
        </div>
        <button class="nav-drawer-close" id="navDrawerClose"><i class="fas fa-times"></i></button>
    </div>
    <nav class="nav-drawer-nav">
        <a href="/nail_salon/customer/dashboard.php" class="nav-drawer-link <?php echo $customerPage==='dashboard.php'?'active':''; ?>"><span>Home</span></a>
        <a href="/nail_salon/customer/services.php" class="nav-drawer-link <?php echo $customerPage==='services.php'?'active':''; ?>"><span>Services</span></a>
        <a href="/nail_salon/customer/staff.php" class="nav-drawer-link <?php echo $customerPage==='staff.php'?'active':''; ?>"><span>Our Team</span></a>
         <div class="nav-drawer-divider"></div>
        <a href="/nail_salon/customer/appointments.php" class="nav-drawer-link <?php echo $customerPage==='appointments.php'?'active':''; ?>"><i class="fas fa-clock"></i><span>My Appointments</span></a>
        <a href="/nail_salon/customer/booking.php" class="nav-drawer-link nav-drawer-primary-link"><i class="fas fa-calendar-check"></i><span>Book Now</span></a>
        <a href="/nail_salon/customer/notifications.php" class="nav-drawer-link <?php echo $customerPage==='notifications.php'?'active':''; ?>">
            <i class="fas fa-bell"></i><span>Notifications</span>
            <?php if ($unread_count > 0): ?>
                <span class="nav-drawer-badge"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <div class="nav-drawer-divider"></div>
        <a href="/nail_salon/customer/review.php" class="nav-drawer-link <?php echo $customerPage==='review.php'?'active':''; ?>"><i class="fas fa-star"></i><span>Reviews</span></a>
        <a href="/nail_salon/customer/profile.php" class="nav-drawer-link <?php echo $customerPage==='profile.php'?'active':''; ?>"><i class="fas fa-user-edit"></i><span>Profile</span></a>
        <a href="/nail_salon/auth/change-password.php" class="nav-drawer-link"><i class="fas fa-key"></i><span>Change Password</span></a>
        <div class="nav-drawer-divider"></div>
        <a href="/nail_salon/auth/logout.php" class="nav-drawer-link nav-drawer-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </nav>
</div>

<div class="nav-js">
<script>
function toggleUserDropdown() {
    var dd = document.getElementById('userDropdown');
    dd.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    var btn = document.querySelector('.nav-user-btn');
    var dd = document.getElementById('userDropdown');
    if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
        dd.classList.remove('show');
    }
});

var navbar = document.getElementById('navbar');
window.addEventListener('scroll', function() {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

var menuToggle = document.getElementById('menuToggle');
var navDrawer = document.getElementById('navDrawer');
var navOverlay = document.getElementById('navOverlay');
var navDrawerClose = document.getElementById('navDrawerClose');

function openDrawer() {
    navDrawer.classList.add('open');
    navOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    menuToggle.classList.add('active');
}

function closeDrawer() {
    navDrawer.classList.remove('open');
    navOverlay.classList.remove('show');
    document.body.style.overflow = '';
    menuToggle.classList.remove('active');
}

if (menuToggle) menuToggle.addEventListener('click', function() {
    if (navDrawer.classList.contains('open')) closeDrawer();
    else openDrawer();
});
if (navOverlay) navOverlay.addEventListener('click', closeDrawer);
if (navDrawerClose) navDrawerClose.addEventListener('click', closeDrawer);
navDrawer.querySelectorAll('a').forEach(function(link) {
    link.addEventListener('click', closeDrawer);
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && navDrawer.classList.contains('open')) closeDrawer();
});
</script>
</div>
<main>
<?php endif; ?>
