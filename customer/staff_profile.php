<?php
require_once '../config/database.php';
require_once '../includes/session.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /nail_salon/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();

if (!$staff) {
    header('Location: staff.php');
    exit;
}

$title = htmlspecialchars($staff['name']) . ' - Profile';
require_once '../includes/header.php';
?>
<style>
.sp-page-header{background:linear-gradient(135deg,var(--blueberry-50) 0%,var(--cream) 40%,#e6f0ff 100%);text-align:center;padding:8rem 2rem 2rem}
.sp-page-header h1{font-family:'Playfair Display',serif;font-size:2rem;color:var(--blueberry-900);margin:0}
.sp-page-header h1 span{color:var(--blueberry-600)}
.sp-card-wrap{max-width:500px;margin:0 auto;padding:0 1.5rem 4rem}
.sp-card{background:white;border-radius:20px;overflow:hidden;box-shadow:0 4px 30px rgba(26,39,68,.08);border:1px solid rgba(74,144,217,.08)}
.sp-card-banner{height:100px;background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));position:relative}
.sp-card-avatar{width:110px;height:110px;border-radius:50%;margin:-55px auto 0;overflow:hidden;background:white;padding:4px;box-shadow:0 4px 20px rgba(53,120,192,.2);position:relative;z-index:1}
.sp-card-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.sp-card-avatar-placeholder{width:100%;height:100%;border-radius:50%;background:var(--blueberry-100);display:flex;align-items:center;justify-content:center}
.sp-card-avatar-placeholder i{font-size:3rem;color:var(--blueberry-400)}
.sp-card-body{padding:1rem 2rem 2rem;text-align:center}
.sp-card-body h1{font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--blueberry-900);margin:0 0 .25rem}
.sp-card-spec{color:var(--blueberry-500);font-weight:600;font-size:.9rem;margin:0 0 .5rem}
.sp-status-badge{display:inline-block;padding:.3rem .9rem;border-radius:50px;font-size:.75rem;font-weight:600;margin-bottom:1rem}

.sp-info-row{display:flex;align-items:flex-start;gap:.8rem;text-align:left;padding:.8rem 0;border-bottom:1px solid #f3f4f6}
.sp-info-row:last-child{border-bottom:none}
.sp-info-icon{width:36px;height:36px;border-radius:10px;background:var(--blueberry-50);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--blueberry-500);font-size:.85rem}
.sp-info-label{font-size:.7rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;font-weight:700;margin-bottom:2px}
.sp-info-value{font-size:.9rem;color:var(--dark);font-weight:500;line-height:1.5}

.sp-actions{display:flex;gap:.8rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap}
.sp-actions a{padding:.65rem 1.4rem;border-radius:12px;font-size:.85rem;font-weight:600;display:inline-flex;align-items:center;gap:.4rem;transition:all .3s;text-decoration:none}
.sp-back-btn{color:var(--blueberry-600);border:1.5px solid var(--blueberry-200);background:white}
.sp-back-btn:hover{background:var(--blueberry-50);border-color:var(--blueberry-400)}
.sp-book-btn{background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white}
.sp-book-btn:hover{background:linear-gradient(135deg,var(--blueberry-600),var(--blueberry-700));transform:translateY(-2px);box-shadow:0 6px 20px rgba(53,120,192,.3)}
</style>

<div class="sp-page-header">
    <h1><span>Staff Profile</span></h1>
</div>

<div class="sp-card-wrap">
    <div class="sp-card">
        <div class="sp-card-banner"></div>
        <div class="sp-card-avatar">
            <?php if ($staff['photo']): ?>
                <img src="/nail_salon/assets/uploads/<?php echo htmlspecialchars($staff['photo']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
            <?php else: ?>
                <div class="sp-card-avatar-placeholder"><i class="fas fa-user"></i></div>
            <?php endif; ?>
        </div>
        <div class="sp-card-body">
            <h1><?php echo htmlspecialchars($staff['name']); ?></h1>
            <p class="sp-card-spec"><?php echo htmlspecialchars($staff['specialization'] ?? 'Nail Artist'); ?></p>
            <?php if ($staff['status'] !== 'available'): ?>
            <span class="sp-status-badge" style="<?php echo match($staff['status']) {
                'busy' => 'background:#fef3c7;color:#b45309',
                'off' => 'background:#fee2e2;color:#dc2626',
                default => 'background:#f3f4f6;color:#6b7280'
            }; ?>">
                <i class="fas fa-circle" style="font-size:6px;margin-right:5px;vertical-align:middle"></i><?php echo ucfirst($staff['status']); ?>
            </span>
            <?php endif; ?>

            <div style="text-align:left;margin-top:.5rem">
                <div class="sp-info-row">
                    <div class="sp-info-icon"><i class="fas fa-user"></i></div>
                    <div>
                        <p class="sp-info-label">Name</p>
                        <p class="sp-info-value"><?php echo htmlspecialchars($staff['name']); ?></p>
                    </div>
                </div>
                <div class="sp-info-row">
                    <div class="sp-info-icon"><i class="fas fa-briefcase"></i></div>
                    <div>
                        <p class="sp-info-label">Experience</p>
                        <p class="sp-info-value"><?php echo nl2br(htmlspecialchars($staff['experience'] ?? 'Not specified')); ?></p>
                    </div>
                </div>
                <div class="sp-info-row">
                    <div class="sp-info-icon"><i class="fas fa-certificate"></i></div>
                    <div>
                        <p class="sp-info-label">Certification</p>
                        <p class="sp-info-value"><?php echo nl2br(htmlspecialchars($staff['certification'] ?? 'Not specified')); ?></p>
                    </div>
                </div>
            </div>

            <div class="sp-actions">
                <a href="/nail_salon/customer/staff.php" class="sp-back-btn"><i class="fas fa-arrow-left" style="font-size:.75rem"></i> Back to Team</a>
                <?php if ($staff['status'] === 'available'): ?>
                <a href="javascript:void(0)" onclick="handleBook()" class="sp-book-btn"><i class="fas fa-calendar-plus"></i> Book Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
const staffId = <?php echo $staff['id']; ?>;
function handleBook() {
    if (isLoggedIn) {
        window.location.href = '/nail_salon/customer/booking.php?staff=' + staffId;
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
</script>
<?php require_once '../includes/footer.php'; ?>
