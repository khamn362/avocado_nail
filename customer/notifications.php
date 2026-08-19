<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireCustomer();
require_once '../includes/notifications.php';

$title = 'Notifications';

if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'all') {
    markAllAsRead($pdo, $_SESSION['user_id']);
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['read']) && isset($_GET['id'])) {
    markAsRead($pdo, $_GET['id'], $_SESSION['user_id']);
    header('Location: notifications.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

require_once '../includes/header.php';
?>
<style>
.notif-page-header{background:linear-gradient(135deg,var(--blueberry-50) 0%,var(--cream) 40%,#e6f0ff 100%);text-align:center;padding:8rem 2rem 3rem}
.notif-page-header h1{font-family:'Playfair Display',serif;font-size:2.4rem;color:var(--blueberry-900);margin:0 0 .5rem}
.notif-page-header h1 span{color:var(--blueberry-600)}

.notif-page-content{max-width:800px;margin:0 auto;padding:2.5rem 1.5rem 4rem}
.notif-page-toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;flex-wrap:wrap;gap:.8rem}
.notif-page-toolbar h2{font-size:1rem;font-weight:700;color:var(--blueberry-800);margin:0}
.notif-mark-all{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white;font-weight:600;font-size:.85rem;text-decoration:none;transition:all .3s;border:none;cursor:pointer}
.notif-mark-all:hover{background:linear-gradient(135deg,var(--blueberry-600),var(--blueberry-700));transform:translateY(-1px)}

.notif-list{background:white;border-radius:16px;overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,.04);border:1px solid rgba(74,144,217,.08)}
.notif-list-item{display:flex;align-items:flex-start;padding:1rem 1.3rem;border-bottom:1px solid #f3f4f6;transition:background .2s;gap:1rem}
.notif-list-item:last-child{border-bottom:none}
.notif-list-item:hover{background:var(--blueberry-50)}
.notif-list-item.unread{background:#f0f9ff;border-left:4px solid var(--blueberry-500)}
.notif-list-item .notif-icon{width:38px;height:38px;border-radius:10px;background:var(--blueberry-50);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--blueberry-500);font-size:.9rem}
.notif-list-item.unread .notif-icon{background:var(--blueberry-100);color:var(--blueberry-600)}
.notif-list-item .notif-content{flex:1;min-width:0}
.notif-list-item .notif-content h4{font-weight:600;color:var(--dark);margin:0 0 .2rem;font-size:.9rem}
.notif-list-item .notif-content p{color:var(--text-light);font-size:.82rem;margin:0;line-height:1.5}
.notif-list-item .notif-content .notif-date{font-size:.72rem;color:#9ca3af;margin-top:.35rem;display:flex;align-items:center;gap:.3rem}
.notif-list-item .notif-actions{display:flex;align-items:center;gap:.5rem;flex-shrink:0;margin-top:.2rem}
.notif-list-item .notif-actions a{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--blueberry-500);transition:all .2s;text-decoration:none;font-size:.8rem}
.notif-list-item .notif-actions a:hover{background:var(--blueberry-50);color:var(--blueberry-600)}

.notif-empty{background:white;border-radius:16px;padding:4rem 2rem;text-align:center;box-shadow:0 2px 20px rgba(0,0,0,.04);border:1px solid rgba(74,144,217,.08)}
.notif-empty i{font-size:3rem;color:var(--blueberry-200);margin-bottom:1rem;display:block}
.notif-empty h3{font-weight:700;color:var(--blueberry-800);margin:0 0 .3rem;font-size:1.1rem}
.notif-empty p{color:var(--text-light);font-size:.9rem;margin:0}
</style>

<div class="notif-page-header">
    <h1><span>Notifications</span></h1>
</div>

<div class="notif-page-content">
    <div class="notif-page-toolbar">
        <h2><i class="fas fa-bell" style="color:var(--blueberry-500);margin-right:.4rem"></i>All Notifications</h2>
        <?php if (!empty($notifications)): ?>
        <a href="?mark_read=all" class="notif-mark-all"><i class="fas fa-check-double"></i> Mark All Read</a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="notif-empty">
            <i class="fas fa-bell-slash"></i>
            <h3>No notifications yet</h3>
            <p>You'll see updates about your appointments here.</p>
        </div>
    <?php else: ?>
        <div class="notif-list">
            <?php foreach ($notifications as $n): ?>
            <div class="notif-list-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                <div class="notif-icon">
                    <i class="fas <?php echo $n['type'] === 'new_booking' ? 'fa-calendar-check' : ($n['type'] === 'status_change' ? 'fa-sync-alt' : 'fa-bell'); ?>"></i>
                </div>
                <div class="notif-content">
                    <h4><?php echo htmlspecialchars($n['title']); ?></h4>
                    <p><?php echo htmlspecialchars($n['message']); ?></p>
                    <span class="notif-date"><i class="far fa-clock"></i> <?php echo date('F d, Y - h:i A', strtotime($n['created_at'])); ?></span>
                </div>
                <div class="notif-actions">
                    <?php if (!$n['is_read']): ?>
                        <a href="?read=1&id=<?php echo $n['id']; ?>" title="Mark as read"><i class="fas fa-check"></i></a>
                    <?php endif; ?>
                    <?php if ($n['appointment_id']): ?>
                        <a href="appointments.php" title="View appointment"><i class="fas fa-external-link-alt"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
