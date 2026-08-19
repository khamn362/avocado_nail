<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireCustomer();

$title = 'Staff';

$staff_members = $pdo->query("SELECT * FROM staff ORDER BY name")->fetchAll();

require_once '../includes/header.php';
?>
<style>
.staff-page-header{background:linear-gradient(135deg,var(--blueberry-50) 0%,var(--cream) 40%,#e6f0ff 100%);text-align:center;padding:8rem 2rem 3rem}
.staff-page-header h1{font-family:'Playfair Display',serif;font-size:2.4rem;color:var(--blueberry-900);margin:0 0 .5rem}
.staff-page-header h1 span{color:var(--blueberry-600)}
.staff-page-header p{color:var(--text-light);font-size:1rem;max-width:500px;margin:0 auto;line-height:1.7}

.staff-page-grid{max-width:1200px;margin:0 auto;padding:2.5rem 1.5rem 4rem}

.staff-member-card{background:white;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(26,39,68,.06);border:1px solid rgba(74,144,217,.08);transition:all .4s ease;position:relative}
.staff-member-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(26,39,68,.12)}
.staff-member-banner{height:80px;background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));position:relative}
.staff-member-avatar-wrap{position:absolute;bottom:-35px;left:50%;transform:translateX(-50%);width:72px;height:72px;border-radius:50%;background:white;padding:3px;box-shadow:0 4px 15px rgba(53,120,192,.2)}
.staff-member-avatar-inner{width:100%;height:100%;border-radius:50%;overflow:hidden;background:var(--blueberry-100);display:flex;align-items:center;justify-content:center}
.staff-member-avatar-inner img{width:100%;height:100%;object-fit:cover}
.staff-member-avatar-inner i{font-size:1.8rem;color:var(--blueberry-400)}
.staff-member-body{padding:2.8rem 1.5rem 1.5rem;text-align:center}
.staff-member-body h3{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--blueberry-900);margin:0 0 .25rem}
.staff-member-body .staff-spec{font-size:.8rem;color:var(--blueberry-500);font-weight:600;margin:0 0 .6rem}
.staff-member-status{display:inline-block;padding:.25rem .75rem;border-radius:50px;font-size:.7rem;font-weight:600;margin-bottom:.8rem}
.staff-member-actions{display:flex;gap:.6rem;justify-content:center;margin-top:.6rem}
.staff-member-actions a{padding:.5rem 1rem;border-radius:10px;font-size:.75rem;font-weight:600;display:inline-flex;align-items:center;gap:.3rem;transition:all .3s;text-decoration:none}
.staff-book-btn{background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white}
.staff-book-btn:hover{background:linear-gradient(135deg,var(--blueberry-600),var(--blueberry-700));transform:translateY(-2px)}
.staff-profile-btn{color:var(--blueberry-600);border:1.5px solid var(--blueberry-200);background:transparent}
.staff-profile-btn:hover{background:var(--blueberry-50);border-color:var(--blueberry-400)}
</style>

<div class="staff-page-header">
    <h1>Meet Our <span>Artists</span></h1>
    <p>Talented nail artists dedicated to bringing your vision to life with precision and creativity.</p>
</div>

<div class="staff-page-grid">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.5rem">
        <?php foreach ($staff_members as $staff): ?>
        <div class="staff-member-card">
            <div class="staff-member-banner">
                <div class="staff-member-avatar-wrap">
                    <div class="staff-member-avatar-inner">
                        <?php if ($staff['photo']): ?>
                            <img src="/nail_salon/assets/uploads/<?php echo htmlspecialchars($staff['photo']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="staff-member-body">
                <h3><?php echo htmlspecialchars($staff['name']); ?></h3>
                <p class="staff-spec"><?php echo htmlspecialchars($staff['specialization'] ?? 'Nail Artist'); ?></p>
                <?php if ($staff['status'] !== 'available'): ?>
                <span class="staff-member-status" style="<?php echo match($staff['status']) { 'busy' => 'background:#fef3c7;color:#b45309', 'off' => 'background:#fee2e2;color:#dc2626', default => 'background:#f3f4f6;color:#6b7280' }; ?>">
                    <i class="fas fa-circle" style="font-size:5px;margin-right:4px;vertical-align:middle"></i><?php echo ucfirst($staff['status']); ?>
                </span>
                <?php endif; ?>
                <div class="staff-member-actions">
                    <?php if ($staff['status'] === 'available'): ?>
                    <a href="booking.php?staff=<?php echo $staff['id']; ?>" class="staff-book-btn"><i class="fas fa-calendar-plus"></i> Book</a>
                    <?php endif; ?>
                    <a href="staff_profile.php?id=<?php echo $staff['id']; ?>" class="staff-profile-btn">View Profile <i class="fas fa-arrow-right" style="font-size:.65rem"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
