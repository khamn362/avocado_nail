<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireCustomer();

// Migration: add image column if missing
$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'image'");
if (!$stmt->fetch()) {
    $pdo->exec("ALTER TABLE users ADD image VARCHAR(255) DEFAULT NULL AFTER address");
}

$title = 'My Profile';
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name) || empty($email)) {
        $error = 'Full name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $image = $user['image'] ?? null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Invalid image type. Allowed: jpg, jpeg, png, gif, webp.';
            } else {
                $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $dest = '../assets/images/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    if ($image && file_exists('../assets/images/' . $image)) {
                        unlink('../assets/images/' . $image);
                    }
                    $image = $filename;
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=?, image=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $address, $image, $_SESSION['user_id']]);
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['image'] = $image;
            $success = 'Profile updated successfully.';

            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
}

// Stats
$total_appointments = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE customer_id = ?");
$total_appointments->execute([$_SESSION['user_id']]);
$total_count = $total_appointments->fetchColumn();

$completed_appointments = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE customer_id = ? AND status = 'completed'");
$completed_appointments->execute([$_SESSION['user_id']]);
$completed_count = $completed_appointments->fetchColumn();

require_once '../includes/header.php';
?>
<style>
.profile-page-header{background:linear-gradient(135deg,var(--blueberry-50) 0%,var(--cream) 40%,#e6f0ff 100%);text-align:center;padding:8rem 2rem 3rem}
.profile-page-header h1{font-family:'Playfair Display',serif;font-size:2.4rem;color:var(--blueberry-900);margin:0}
.profile-page-header h1 span{color:var(--blueberry-600)}

.profile-page-content{max-width:1100px;margin:0 auto;padding:2.5rem 1.5rem 4rem}
.profile-grid{display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start}

.profile-card{background:white;border-radius:16px;padding:1.5rem;box-shadow:0 2px 20px rgba(0,0,0,.04);border:1px solid rgba(74,144,217,.08)}
.profile-card h2{font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--blueberry-900);margin:0 0 1.2rem;display:flex;align-items:center;gap:.5rem}
.profile-card h2 i{color:var(--blueberry-500);font-size:1rem}

.profile-field{margin-bottom:1rem}
.profile-field label{display:block;font-weight:600;color:var(--dark);margin-bottom:.35rem;font-size:.85rem}
.profile-field input,.profile-field textarea{width:100%;padding:.65rem .9rem;border:2px solid var(--blueberry-100);border-radius:10px;font-size:.9rem;outline:none;transition:border-color .3s;font-family:'Inter',sans-serif;background:white}
.profile-field input:focus,.profile-field textarea:focus{border-color:var(--blueberry-400)}
.profile-field textarea{resize:vertical;min-height:60px}
.profile-field input:disabled{background:#f9fafb;color:#9ca3af;cursor:not-allowed}

.profile-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}

.profile-alert{border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.88rem;display:flex;align-items:center;gap:.5rem}
.profile-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
.profile-alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}

.profile-save-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.6rem;border-radius:12px;background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white;font-weight:700;font-size:.9rem;border:none;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif}
.profile-save-btn:hover{background:linear-gradient(135deg,var(--blueberry-600),var(--blueberry-700));transform:translateY(-2px);box-shadow:0 6px 20px rgba(53,120,192,.3)}

.profile-file-input{font-size:.85rem;color:var(--text-light)}
.profile-file-input::file-selector-button{margin-right:.8rem;padding:.45rem 1rem;border-radius:8px;border:0;background:var(--blueberry-50);color:var(--blueberry-700);font-weight:600;font-size:.82rem;cursor:pointer;transition:all .2s}
.profile-file-input::file-selector-button:hover{background:var(--blueberry-100)}

.profile-sidebar-card{background:white;border-radius:16px;padding:1.5rem;box-shadow:0 2px 20px rgba(0,0,0,.04);border:1px solid rgba(74,144,217,.08);text-align:center}
.profile-avatar{width:80px;height:80px;border-radius:50%;margin:0 auto 1rem;overflow:hidden;border:3px solid var(--blueberry-200);box-shadow:0 4px 15px rgba(53,120,192,.15)}
.profile-avatar img{width:100%;height:100%;object-fit:cover}
.profile-avatar-placeholder{width:100%;height:100%;background:var(--blueberry-100);display:flex;align-items:center;justify-content:center}
.profile-avatar-placeholder i{font-size:2rem;color:var(--blueberry-400)}
.profile-name{font-weight:700;color:var(--dark);font-size:1.1rem;margin:0 0 .15rem}
.profile-username{font-size:.85rem;color:var(--text-light);margin:0 0 .2rem}
.profile-role{font-size:.75rem;color:var(--blueberry-500);font-weight:600;margin:0 0 .15rem}
.profile-joined{font-size:.72rem;color:#9ca3af;margin:0}

.profile-stat-card{background:white;border-radius:16px;padding:1.2rem;box-shadow:0 2px 20px rgba(0,0,0,.04);border:1px solid rgba(74,144,217,.08)}
.profile-stat-card h3{font-size:.85rem;font-weight:700;color:var(--blueberry-800);margin:0 0 .8rem}
.profile-stat-row{display:flex;justify-content:space-between;align-items:center;padding:.45rem 0;border-bottom:1px solid #f3f4f6}
.profile-stat-row:last-child{border-bottom:none}
.profile-stat-row span:first-child{font-size:.85rem;color:var(--text-light)}
.profile-stat-row span:last-child{font-weight:700;color:var(--blueberry-600)}

.profile-link-card{display:block;background:white;border-radius:12px;padding:.8rem 1.2rem;box-shadow:0 2px 12px rgba(0,0,0,.03);border:1px solid rgba(74,144,217,.08);text-decoration:none;color:var(--blueberry-600);font-weight:600;font-size:.88rem;text-align:center;transition:all .2s}
.profile-link-card:hover{background:var(--blueberry-50);border-color:var(--blueberry-300);transform:translateY(-1px)}

@media(max-width:768px){
    .profile-grid{grid-template-columns:1fr}
    .profile-field-grid{grid-template-columns:1fr}
}
</style>

<div class="profile-page-header">
    <h1>My <span>Profile</span></h1>
</div>

<div class="profile-page-content">
    <?php if ($error): ?>
        <div class="profile-alert profile-alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="profile-alert profile-alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="profile-card">
            <h2><i class="fas fa-user-edit"></i> Personal Information</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="profile-field-grid">
                    <div class="profile-field">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="profile-field">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                </div>
                <div class="profile-field-grid">
                    <div class="profile-field">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="profile-field">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="profile-field">
                    <label>Address</label>
                    <textarea name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <div class="profile-field">
                    <label>Profile Image</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="profile-file-input">
                </div>
                <button type="submit" class="profile-save-btn"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>

        <div style="display:flex;flex-direction:column;gap:1.2rem">
            <div class="profile-sidebar-card">
                <div class="profile-avatar">
                    <?php if ($user['image']): ?>
                        <img src="/nail_salon/assets/images/<?php echo htmlspecialchars($user['image']); ?>" alt="Profile">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </div>
                <p class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></p>
                <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                <p class="profile-role">Customer</p>
                <p class="profile-joined">Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
            </div>

            <div class="profile-stat-card">
                <h3><i class="fas fa-chart-bar" style="color:var(--blueberry-500);margin-right:.3rem"></i>Activity</h3>
                <div class="profile-stat-row">
                    <span>Total Appointments</span>
                    <span><?php echo $total_count; ?></span>
                </div>
                <div class="profile-stat-row">
                    <span>Completed</span>
                    <span><?php echo $completed_count; ?></span>
                </div>
            </div>

            <a href="/nail_salon/auth/change-password.php" class="profile-link-card"><i class="fas fa-key" style="margin-right:.4rem"></i>Change Password</a>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
