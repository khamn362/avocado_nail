<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireCustomer();

$title = 'Book Appointment';
$error = '';
$success = '';

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$services = $pdo->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.status='active' ORDER BY c.name, s.name")->fetchAll();
$staff_members = $pdo->query("SELECT * FROM staff ORDER BY name")->fetchAll();

$selected_service = !empty($_GET['service']) ? intval($_GET['service']) : null;
$selected_staff = !empty($_GET['staff']) ? intval($_GET['staff']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($service_id) || empty($staff_id) || empty($appointment_date) || empty($appointment_time)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $error = 'Appointment date cannot be in the past.';
    } elseif (strtotime($appointment_date) === strtotime(date('Y-m-d')) && strtotime($appointment_time) <= strtotime('-15 minutes', strtotime(date('H:i:s')))) {
        $error = 'This time slot has already passed.';
    } else {
        $svc_stmt = $pdo->prepare("SELECT duration, price FROM services WHERE id = ?");
        $svc_stmt->execute([$service_id]);
        $svc = $svc_stmt->fetch();

        if (!$svc) {
            $error = 'Invalid service selected.';
        } else {
            $start_time = $appointment_time;
            $duration_minutes = $svc['duration'];
            $end_time = date('H:i:s', strtotime($start_time) + $duration_minutes * 60);

            $staff_stmt = $pdo->prepare("SELECT working_hours_start, working_hours_end FROM staff WHERE id = ?");
            $staff_stmt->execute([$staff_id]);
            $staff_info = $staff_stmt->fetch();

            if ($staff_info) {
                if ($start_time < $staff_info['working_hours_start']) {
                    $error = 'Staff working hours start at ' . date('h:i A', strtotime($staff_info['working_hours_start'])) . '. Please select a later time.';
                } elseif ($end_time > $staff_info['working_hours_end']) {
                    $error = 'This service ends at ' . date('h:i A', strtotime($end_time)) . ', which is beyond staff working hours (' . date('h:i A', strtotime($staff_info['working_hours_end'])) . '). Please select an earlier time.';
                }
            }

            if (!$error) {
                $staff_conflict = $pdo->prepare("SELECT id FROM appointments WHERE staff_id = ? AND appointment_date = ? AND appointment_time < ? AND end_time > ? AND status NOT IN ('cancelled')");
                $staff_conflict->execute([$staff_id, $appointment_date, $end_time, $start_time]);
                if ($staff_conflict->fetch()) {
                    $error = 'This staff member is already booked for the selected time slot.';
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("INSERT INTO appointments (customer_id, service_id, staff_id, appointment_date, appointment_time, end_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['user_id'], $service_id, $staff_id, $appointment_date, $start_time, $end_time, $notes]);
                $appointment_id = $pdo->lastInsertId();
                require_once '../includes/notifications.php';
                notifyAdmins($pdo, 'new_booking', 'New Booking', $_SESSION['full_name'] . ' booked a new appointment.', $appointment_id);
                $success = 'Appointment booked successfully! We will confirm your booking shortly.';
            }
        }
    }
}

if (isset($_GET['check_availability'])) {
    header('Content-Type: application/json');
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';
    $dur = intval($_GET['duration'] ?? 30);
    $stf = intval($_GET['staff_id'] ?? 0);

    if ($date) {
        $et = $time ? date('H:i:s', strtotime($time) + $dur * 60) : null;

        $stf_ok = true;
        if ($stf && $time && $et) {
            $si = $pdo->prepare("SELECT working_hours_start, working_hours_end FROM staff WHERE id = ?");
            $si->execute([$stf]);
            $si = $si->fetch();
            if ($si && ($time < $si['working_hours_start'] || $et > $si['working_hours_end'])) {
                $stf_ok = false;
            }
        }

        $busy_staff = [];
        if ($time && $et) {
            $cf = $pdo->prepare("SELECT staff_id, end_time FROM appointments WHERE appointment_date = ? AND status NOT IN ('cancelled') AND appointment_time < ? AND end_time > ?");
            $cf->execute([$date, $et, $time]);
            foreach ($cf->fetchAll() as $row) {
                $busy_staff[$row['staff_id']] = date('h:i A', strtotime($row['end_time']));
            }
        }

        $unavailable_staff = [];
        $us = $pdo->query("SELECT id, name, status FROM staff WHERE status != 'available'");
        foreach ($us->fetchAll() as $row) {
            $unavailable_staff[$row['id']] = ['name' => $row['name'], 'status' => $row['status']];
        }

        $busy_ranges = [];
        if ($stf) {
            $br_query = "SELECT appointment_time, end_time FROM appointments WHERE appointment_date = ? AND staff_id = ? AND status NOT IN ('cancelled')";
            $br = $pdo->prepare($br_query);
            $br->execute([$date, $stf]);
            foreach ($br->fetchAll() as $row) {
                $busy_ranges[] = [
                    'start' => date('H:i', strtotime($row['appointment_time'])),
                    'end' => date('H:i', strtotime($row['end_time']))
                ];
            }
        } else {
            $all_staff_stmt = $pdo->query("SELECT id, working_hours_start, working_hours_end FROM staff WHERE status = 'available'");
            $all_staff = $all_staff_stmt->fetchAll();
            
            $appts_stmt = $pdo->prepare("SELECT staff_id, appointment_time, end_time FROM appointments WHERE appointment_date = ? AND status NOT IN ('cancelled')");
            $appts_stmt->execute([$date]);
            $all_appts = $appts_stmt->fetchAll();
            
            for ($h = 9; $h <= 17; $h++) {
                foreach (['00', '30'] as $m) {
                    if ($h == 17 && $m == '30') continue;
                    
                    $slot_start_str = sprintf('%02d:%s:00', $h, $m);
                    $slot_end_time = strtotime($slot_start_str) + $dur * 60;
                    $slot_end_str = date('H:i:s', $slot_end_time);
                    
                    $any_staff_available = false;
                    foreach ($all_staff as $staff) {
                        if ($slot_start_str >= $staff['working_hours_start'] && $slot_end_str <= $staff['working_hours_end']) {
                            $conflict = false;
                            foreach ($all_appts as $appt) {
                                if ($appt['staff_id'] == $staff['id'] && $appt['appointment_time'] < $slot_end_str && $appt['end_time'] > $slot_start_str) {
                                    $conflict = true;
                                    break;
                                }
                            }
                            if (!$conflict) {
                                $any_staff_available = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$any_staff_available) {
                        $busy_ranges[] = [
                            'start' => date('H:i', strtotime($slot_start_str)),
                            'end' => date('H:i', $slot_end_time)
                        ];
                    }
                }
            }
        }

        echo json_encode([
            'staff_available' => $stf_ok,
            'end_time' => $et ? date('h:i A', strtotime($et)) : null,
            'busy_staff' => $busy_staff,
            'unavailable_staff' => $unavailable_staff,
            'busy_ranges' => $busy_ranges
        ]);
    }
    exit;
}

require_once '../includes/header.php';
?>

<style>
.bk{background:linear-gradient(180deg,var(--blueberry-50) 0%,#f9fafb 100%);min-height:100vh;display:flex;flex-direction:column}

/* Big Header */
.bk-head{text-align:center;padding:1.8rem 1rem 0}
.bk-head h1{font-family:'Playfair Display',serif;font-size:2.2rem;color:var(--blueberry-900);margin:0 0 .3rem}
.bk-head h1 span{color:var(--blueberry-600)}
.bk-head p{color:var(--text-light);font-size:1rem;margin:0}

/* Big Progress Steps */
.bk-progress{display:flex;align-items:center;justify-content:center;gap:0;padding:1.5rem 1rem 0;max-width:800px;margin:0 auto}
.bk-prog-step{display:flex;align-items:center;gap:.6rem}
.bk-prog-circle{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;border:3px solid var(--blueberry-200);background:white;color:var(--blueberry-400);transition:all .4s ease;flex-shrink:0}
.bk-prog-step.active .bk-prog-circle{background:var(--blueberry-600);border-color:var(--blueberry-600);color:white;box-shadow:0 6px 20px rgba(53,120,192,.35);transform:scale(1.08)}
.bk-prog-step.done .bk-prog-circle{background:var(--blueberry-100);border-color:var(--blueberry-500);color:var(--blueberry-600)}
.bk-prog-label{font-size:.9rem;font-weight:700;color:var(--blueberry-400);transition:color .3s}
.bk-prog-step.active .bk-prog-label{color:var(--blueberry-800)}
.bk-prog-step.done .bk-prog-label{color:var(--blueberry-600)}
.bk-prog-line{width:50px;height:3px;background:var(--blueberry-200);margin:0 .4rem;border-radius:3px;transition:background .4s}
.bk-prog-line.done{background:var(--blueberry-500)}

/* Alert */
.bk-alert{border-radius:14px;padding:.8rem 1.1rem;margin:0 2rem 1rem;font-size:.9rem;display:flex;align-items:center;gap:.5rem}
.bk-alert i{font-size:1.1rem;flex-shrink:0}
.bk-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
.bk-alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}

/* Panels */
.bk-panel{display:none;animation:bkFadeIn .4s ease}
.bk-panel.active{display:flex;flex-direction:column;flex:1}
@keyframes bkFadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

.bk-inner{max-width:1100px;width:100%;margin:0 auto;padding:0 2rem;flex:1;display:flex;flex-direction:column}

/* Service panel: full height */
#panelService.active{flex:1;display:flex;flex-direction:column}
#panelService .bk-svc-wrap{flex:1;display:flex;flex-direction:column}

/* Category pills */
.bk-cats{display:flex;flex-wrap:wrap;gap:.5rem;margin:1.2rem 0 1rem}
.bk-cat{padding:.5rem 1.2rem;border-radius:50px;border:2px solid var(--blueberry-200);background:white;color:var(--blueberry-700);font-size:.85rem;font-weight:600;cursor:pointer;transition:all .25s}
.bk-cat:hover{border-color:var(--blueberry-400);background:var(--blueberry-50)}
.bk-cat.on{background:var(--blueberry-600);color:white;border-color:var(--blueberry-600)}

/* Service table */
.bk-svc-table{width:100%;flex:1;display:flex;flex-direction:column;border:2px solid var(--blueberry-100);border-radius:16px;overflow:hidden;background:white}
.bk-svc-thead{display:grid;grid-template-columns:44px 1fr 100px 100px 110px;padding:.7rem 1rem;background:var(--blueberry-50);border-bottom:2px solid var(--blueberry-100)}
.bk-svc-thead span{font-size:.72rem;font-weight:700;color:var(--blueberry-700);text-transform:uppercase;letter-spacing:.5px}
.bk-svc-tbody{flex:1;overflow-y:auto;max-height:calc(100vh - 340px)}
.bk-svc-row{display:grid;grid-template-columns:44px 1fr 100px 100px 110px;padding:.6rem 1rem;align-items:center;border-bottom:1px solid #f3f4f6;cursor:pointer;transition:all .2s}
.bk-svc-row:last-child{border-bottom:none}
.bk-svc-row:hover{background:var(--blueberry-50)}
.bk-svc-row.on{background:var(--blueberry-50);border-color:var(--blueberry-200)}
.bk-svc-radio{width:22px;height:22px;border-radius:50%;border:2px solid var(--blueberry-300);display:flex;align-items:center;justify-content:center;transition:all .2s}
.bk-svc-row.on .bk-svc-radio{border-color:var(--blueberry-600);background:var(--blueberry-600)}
.bk-svc-row.on .bk-svc-radio::after{content:'\f00c';font-family:'Font Awesome 6 Free';font-weight:900;font-size:.6rem;color:white}
.bk-svc-name{font-weight:600;color:var(--dark);font-size:.9rem}
.bk-svc-name small{display:block;font-weight:400;color:var(--text-light);font-size:.72rem;margin-top:.1rem}
.bk-svc-price{font-weight:700;color:var(--blueberry-600);font-size:.9rem}
.bk-svc-dur{font-size:.8rem;color:var(--text-light);display:flex;align-items:center;gap:.3rem}
.bk-svc-dur i{color:var(--blueberry-500)}
.bk-svc-select{background:var(--blueberry-600);color:white;border:none;padding:.45rem 1rem;border-radius:8px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;text-align:center}
.bk-svc-select:hover{background:var(--blueberry-700)}
.bk-svc-row.on .bk-svc-select{background:var(--blueberry-100);color:var(--blueberry-700)}

.bk-actions{display:flex;justify-content:space-between;align-items:center;padding:1rem 0}
.bk-btn{padding:.7rem 1.8rem;border-radius:12px;font-weight:700;font-size:.95rem;cursor:pointer;transition:all .3s;border:none;display:inline-flex;align-items:center;gap:.5rem;font-family:'Inter',sans-serif}
.bk-btn-next{background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white;box-shadow:0 4px 16px rgba(53,120,192,.25)}
.bk-btn-next:hover{background:linear-gradient(135deg,var(--blueberry-600),var(--blueberry-700));transform:translateY(-2px);box-shadow:0 8px 24px rgba(53,120,192,.3)}
.bk-btn-back{background:white;color:var(--blueberry-700);border:2px solid var(--blueberry-200)}
.bk-btn-back:hover{background:var(--blueberry-50);border-color:var(--blueberry-400)}
.bk-btn-confirm{background:linear-gradient(135deg,var(--blueberry-500),var(--blueberry-600));color:white;font-size:1rem;padding:.8rem 2.5rem;box-shadow:0 4px 16px rgba(53,120,192,.25)}
.bk-btn-confirm:hover{background:linear-gradient(135deg,var(--blueberry-600),var(--blueberry-700));transform:translateY(-2px);box-shadow:0 8px 24px rgba(53,120,192,.3)}

/* Step 2 */
.bk-card2{background:white;border-radius:20px;padding:2rem;box-shadow:0 2px 20px rgba(0,0,0,.04);margin-top:1rem}
.bk-card2-title{margin-bottom:1.5rem}
.bk-card2-title h2{font-family:'Playfair Display',serif;font-size:1.5rem;color:var(--blueberry-900);margin:0 0 .25rem}
.bk-card2-title p{color:var(--text-light);font-size:.9rem;margin:0}

.bk-field-row{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;margin-bottom:1.2rem}
.bk-field label{display:block;font-weight:700;color:var(--dark);margin-bottom:.4rem;font-size:.9rem}
.bk-field label .bk-opt{font-weight:400;color:var(--text-light);font-size:.8rem}
.bk-input{width:100%;padding:.75rem 1rem;border:2px solid var(--blueberry-100);border-radius:12px;font-size:.9rem;outline:none;transition:border-color .3s;font-family:'Inter',sans-serif;background:white}
.bk-input:focus{border-color:var(--blueberry-400)}
textarea.bk-input{resize:vertical;min-height:70px}

.bk-avail{padding:.8rem 1.2rem;border-radius:12px;font-size:.88rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem}
.bk-avail i{font-size:1.1rem;flex-shrink:0}
.bk-avail-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.bk-avail-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.bk-avail-bad{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}

.bk-staff-label{font-weight:700;color:var(--dark);font-size:.9rem;margin-bottom:.7rem}
.bk-staff-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.9rem;margin-bottom:1.2rem}
.bk-staff{border:2px solid var(--blueberry-100);border-radius:16px;cursor:pointer;transition:all .3s;text-align:center;position:relative;overflow:hidden}
.bk-staff:hover{border-color:var(--blueberry-300);box-shadow:0 4px 14px rgba(26,39,68,.07)}
.bk-staff.on{border-color:var(--blueberry-500);background:var(--blueberry-50);box-shadow:0 0 0 3px rgba(74,144,217,.15)}
.bk-staff.on::after{content:'\f00c';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;top:.5rem;right:.5rem;width:24px;height:24px;background:var(--blueberry-600);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;z-index:1}
.bk-staff-inner{padding:1.2rem .8rem}
.bk-staff-avatar{width:60px;height:60px;border-radius:50%;background:var(--blueberry-100);margin:0 auto .6rem;display:flex;align-items:center;justify-content:center;overflow:hidden;border:3px solid var(--blueberry-200);transition:border-color .3s}
.bk-staff.on .bk-staff-avatar{border-color:var(--blueberry-500)}
.bk-staff-avatar img{width:100%;height:100%;object-fit:cover}
.bk-staff-avatar i{color:var(--blueberry-500);font-size:1.3rem}
.bk-staff h4{font-weight:700;color:var(--dark);margin:0 0 .15rem;font-size:.9rem}
.bk-staff p{font-size:.75rem;color:var(--text-light);margin:0 0 .35rem}
.bk-staff-hours{font-size:.7rem;color:var(--blueberry-500);display:flex;align-items:center;justify-content:center;gap:.25rem}
.bk-staff-busy{position:relative;pointer-events:none;border-color:#fca5a5!important;background:#fef2f2!important}
.bk-staff-busy .bk-staff-inner{filter:blur(3px);opacity:.45;transition:filter .3s,opacity .3s}
.bk-staff-busy-msg{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(135deg,rgba(220,38,38,.88),rgba(185,28,28,.92));color:white;font-size:.65rem;font-weight:600;padding:.4rem .5rem;text-align:center;border-radius:0 0 14px 14px;z-index:5;letter-spacing:.2px}

/* Step 3 */
.bk-confirm{background:linear-gradient(135deg,var(--blueberry-50),#f4fafe);border-radius:18px;padding:1.8rem;margin:1rem 0;border:1px solid var(--blueberry-200)}
.bk-confirm-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.2rem}
.bk-confirm-item{background:white;border-radius:14px;padding:1rem 1.2rem}
.bk-confirm-label{font-size:.72rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;font-weight:700;margin-bottom:.25rem}
.bk-confirm-val{font-weight:700;color:var(--dark);font-size:1rem}
.bk-confirm-val.bk-price{color:var(--blueberry-600);font-size:1.2rem;font-weight:800}
.bk-confirm-note{background:white;border:1px solid #fde68a;border-radius:12px;padding:.8rem 1.2rem;font-size:.88rem;color:#92400e;display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem}
.bk-confirm-note i{flex-shrink:0}

@media(max-width:768px){
    .bk-head h1{font-size:1.6rem}
    .bk-inner{padding:0 1rem}
    .bk-prog-circle{width:40px;height:40px;font-size:.9rem}
    .bk-prog-label{font-size:.78rem}
    .bk-prog-line{width:40px}
    .bk-svc-thead{grid-template-columns:36px 1fr 80px 70px;display:none}
    .bk-svc-row{grid-template-columns:36px 1fr auto;gap:.5rem;padding:.8rem 1rem}
    .bk-svc-dur,.bk-svc-select{display:none}
    .bk-field-row{grid-template-columns:1fr}
    .bk-staff-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}
    .bk-confirm-grid{grid-template-columns:1fr 1fr}
}
</style>

<section class="bk">
    <div class="bk-inner">
        <div class="bk-head">
            <h1>Book Your <span>Appointment</span></h1>
            <p>Select a service, pick your preferred date and time, and we'll handle the rest.</p>
        </div>

        <div class="bk-progress" id="bkProgress"></div>

        <form method="POST" id="bookingForm" style="flex:1;display:flex;flex-direction:column">

            <!-- Panel: Date & Time -->
            <div id="panelDateTime" class="bk-panel active">
                <div class="bk-card2">
                    <?php if ($error): ?>
                        <div class="bk-alert bk-alert-error" style="margin: 0 0 1.5rem 0;"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="bk-alert bk-alert-success" style="margin: 0 0 1.5rem 0;"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                    <?php endif; ?>
                    <div class="bk-card2-title">
                        <h2>Pick Date & Time</h2>
                        <p>Choose your preferred appointment date and time.</p>
                    </div>
                    <div class="bk-field-row">
                        <div class="bk-field">
                            <label><i class="fas fa-calendar-alt" style="color:var(--blueberry-500);margin-right:.3rem"></i> Date</label>
                            <input type="date" name="appointment_date" id="bookingDate" min="<?php echo date('Y-m-d'); ?>" required onchange="bkOnSchedule()" class="bk-input">
                        </div>
                        <div class="bk-field">
                            <label><i class="fas fa-clock" style="color:var(--blueberry-500);margin-right:.3rem"></i> Time</label>
                            <select name="appointment_time" id="bookingTime" required onchange="bkOnSchedule()" class="bk-input">
                                <option value="">Select time</option>
                                <?php for ($h = 9; $h <= 17; $h++): ?>
                                    <option value="<?php echo sprintf('%02d', $h); ?>:00:00"><?php echo date('h:i A', strtotime(sprintf('%02d:00', $h))); ?></option>
                                    <?php if ($h < 17): ?>
                                    <option value="<?php echo sprintf('%02d', $h); ?>:30:00"><?php echo date('h:i A', strtotime(sprintf('%02d:30', $h))); ?></option>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div id="availNotice" class="bk-avail" style="display:none;"></div>
                    <div class="bk-actions">
                        <div></div>
                        <button type="button" class="bk-btn bk-btn-next" onclick="bkGo(1)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>

            <!-- Panel: Service -->
            <div id="panelService" class="bk-panel">
                <div class="bk-svc-wrap">
                    <div class="bk-cats">
                        <button type="button" class="bk-cat on" onclick="bkFilterCat('all',this)">All Services</button>
                        <?php foreach ($categories as $cat): ?>
                        <button type="button" class="bk-cat" onclick="bkFilterCat('<?php echo htmlspecialchars($cat['name']); ?>',this)"><?php echo htmlspecialchars($cat['name']); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="bk-svc-table">
                        <div class="bk-svc-thead">
                            <span></span>
                            <span>Service</span>
                            <span>Category</span>
                            <span>Duration</span>
                            <span style="text-align:right">Price</span>
                        </div>
                        <div id="serviceList" class="bk-svc-tbody">
                            <?php foreach ($services as $svc): ?>
                            <label class="bk-svc-row <?php echo $selected_service == $svc['id'] ? 'on' : ''; ?>" data-cat="<?php echo htmlspecialchars($svc['category_name']); ?>">
                                <input type="radio" name="service_id" value="<?php echo $svc['id']; ?>" style="display:none;" data-duration="<?php echo $svc['duration']; ?>" data-price="<?php echo $svc['price']; ?>" data-name="<?php echo htmlspecialchars($svc['name']); ?>" <?php echo $selected_service == $svc['id'] ? 'checked' : ''; ?> onchange="bkSelectSvc(this)">
                                <div class="bk-svc-radio"></div>
                                <div class="bk-svc-name"><?php echo htmlspecialchars($svc['name']); ?><?php if (!empty($svc['description'])): ?><small><?php echo htmlspecialchars(mb_strimwidth($svc['description'],0,60,'...')); ?></small><?php endif; ?></div>
                                <div style="font-size:.78rem;color:var(--text-light)"><?php echo htmlspecialchars($svc['category_name']); ?></div>
                                <div class="bk-svc-dur"><i class="fas fa-clock"></i> <?php echo $svc['duration']; ?> min</div>
                                <div class="bk-svc-price" style="text-align:right">MMK<?php echo number_format($svc['price'], 0); ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="bk-actions">
                        <button type="button" class="bk-btn bk-btn-back" onclick="bkGo(-1)"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="bk-btn bk-btn-next" onclick="bkGo(1)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>

            <!-- Panel: Staff -->
            <div id="panelStaff" class="bk-panel">
                <div class="bk-card2">
                    <div class="bk-card2-title">
                        <h2>Choose Staff Member</h2>
                        <p>Select who you'd like to see.</p>
                    </div>
                    <div id="staffList" class="bk-staff-grid">
                        <?php foreach ($staff_members as $s): ?>
                        <label class="bk-staff <?php echo $selected_staff == $s['id'] ? 'on' : ''; ?>">
                            <input type="radio" name="staff_id" value="<?php echo $s['id']; ?>" style="display:none;" data-hours-start="<?php echo $s['working_hours_start']; ?>" data-hours-end="<?php echo $s['working_hours_end']; ?>" <?php echo $selected_staff == $s['id'] ? 'checked' : ''; ?> onchange="bkSelectStaff(this)">
                            <div class="bk-staff-inner">
                                <div class="bk-staff-avatar">
                                    <?php if ($s['photo']): ?>
                                    <img src="/nail_salon/assets/uploads/<?php echo htmlspecialchars($s['photo']); ?>" alt="<?php echo htmlspecialchars($s['name']); ?>">
                                    <?php else: ?>
                                    <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <h4><?php echo htmlspecialchars($s['name']); ?></h4>
                                <p><?php echo htmlspecialchars($s['specialization'] ?? 'Nail Artist'); ?></p>
                                <span class="bk-staff-hours"><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($s['working_hours_start'])); ?> - <?php echo date('h:i A', strtotime($s['working_hours_end'])); ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="bk-actions">
                        <button type="button" class="bk-btn bk-btn-back" onclick="bkGo(-1)"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="bk-btn bk-btn-next" onclick="bkGo(1)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>

            <!-- Panel: Confirm -->
            <div id="panelConfirm" class="bk-panel">
                <div class="bk-card2">
                    <div class="bk-card2-title">
                        <h2>Confirm Your Booking</h2>
                        <p>Review your details before confirming.</p>
                    </div>
                    <div class="bk-confirm">
                        <div class="bk-confirm-grid">
                            <div class="bk-confirm-item">
                                <div class="bk-confirm-label"><i class="fas fa-calendar" style="margin-right:.3rem"></i>Date</div>
                                <div class="bk-confirm-val" id="sumDate"></div>
                            </div>
                            <div class="bk-confirm-item">
                                <div class="bk-confirm-label"><i class="fas fa-clock" style="margin-right:.3rem"></i>Time</div>
                                <div class="bk-confirm-val" id="sumTime"></div>
                            </div>
                            <div class="bk-confirm-item">
                                <div class="bk-confirm-label"><i class="fas fa-hand-sparkles" style="margin-right:.3rem"></i>Service</div>
                                <div class="bk-confirm-val" id="sumSvc"></div>
                            </div>
                            <div class="bk-confirm-item">
                                <div class="bk-confirm-label"><i class="fas fa-user" style="margin-right:.3rem"></i>Staff</div>
                                <div class="bk-confirm-val" id="sumStaff"></div>
                            </div>
                            <div class="bk-confirm-item">
                                <div class="bk-confirm-label"><i class="fas fa-hourglass-half" style="margin-right:.3rem"></i>Duration</div>
                                <div class="bk-confirm-val" id="sumDur"></div>
                            </div>
                            <div class="bk-confirm-item">
                                <div class="bk-confirm-label"><i class="fas fa-tag" style="margin-right:.3rem"></i>Total Price</div>
                                <div class="bk-confirm-val bk-price" id="sumPrice"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bk-confirm-note">
                        <i class="fas fa-info-circle"></i> Your appointment will be submitted as <strong>pending</strong>. We will confirm it shortly.
                    </div>
                    <div class="bk-field" style="margin-top:1rem">
                        <label>Notes <span class="bk-opt">(Optional)</span></label>
                        <textarea name="notes" rows="2" placeholder="Any special requests or preferences..." class="bk-input"></textarea>
                    </div>
                    <div class="bk-actions">
                        <button type="button" class="bk-btn bk-btn-back" onclick="bkGo(-1)"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="bk-btn bk-btn-confirm"><i class="fas fa-calendar-check"></i> Confirm Booking</button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</section>

<script>
var bkStep = 0;
var bkFlow = [];

(function() {
    var hasService = <?php echo $selected_service ? 'true' : 'false'; ?>;
    var hasStaff = <?php echo $selected_staff ? 'true' : 'false'; ?>;
    bkFlow = [{id:'DateTime', label:'Date & Time', icon:'1'}];
    if (!hasService) bkFlow.push({id:'Service', label:'Choose Service', icon: hasStaff ? '2' : '2'});
    if (!hasStaff) bkFlow.push({id:'Staff', label:'Choose Staff', icon: hasService ? '2' : (hasService===false && bkFlow.length>1 ? '3' : '3')});
    bkFlow.push({id:'Confirm', label:'Confirm', icon: String(bkFlow.length)});
    for (var i = 0; i < bkFlow.length; i++) bkFlow[i].icon = String(i + 1);

    renderProgress();
    bkShow('DateTime');
})();

function renderProgress() {
    var h = '';
    for (var i = 0; i < bkFlow.length; i++) {
        h += '<div class="bk-prog-step" id="progStep' + i + '">';
        h += '<div class="bk-prog-circle">' + bkFlow[i].icon + '</div>';
        h += '<span class="bk-prog-label">' + bkFlow[i].label + '</span>';
        h += '</div>';
        if (i < bkFlow.length - 1) {
            h += '<div class="bk-prog-line" id="progLine' + i + '"></div>';
        }
    }
    document.getElementById('bkProgress').innerHTML = h;
}

function bkShow(id) {
    for (var i = 0; i < bkFlow.length; i++) {
        if (bkFlow[i].id === id) {
            bkStep = i;
            break;
        }
    }
    document.querySelectorAll('.bk-panel').forEach(function(p){ p.classList.remove('active') });
    document.getElementById('panel' + bkFlow[bkStep].id).classList.add('active');
    for (var i = 0; i < bkFlow.length; i++) {
        var ps = document.getElementById('progStep' + i);
        ps.classList.remove('active', 'done');
        if (i === bkStep) ps.classList.add('active');
        else if (i < bkStep) ps.classList.add('done');
    }
    for (var i = 0; i < bkFlow.length - 1; i++) {
        var ln = document.getElementById('progLine' + i);
        ln.classList.toggle('done', i < bkStep);
    }
    if (bkFlow[bkStep].id === 'DateTime') {
        var svc = document.querySelector('input[name="service_id"]:checked');
        var stf = document.querySelector('input[name="staff_id"]:checked');
        if (svc && stf) bkOnSchedule();
    }
    if (bkFlow[bkStep].id === 'Staff') {
        bkOnSchedule();
    }
    if (bkFlow[bkStep].id === 'Confirm') {
        bkUpdateSummary();
    }
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function bkGo(dir) {
    if (dir === 1) {
        if (bkFlow[bkStep].id === 'DateTime') {
            var dateVal = document.querySelector('[name="appointment_date"]').value;
            var timeVal = document.querySelector('[name="appointment_time"]').value;
            if (!dateVal || !timeVal) {
                return;
            }
        }
        if (bkFlow[bkStep].id === 'Service') {
            if (!document.querySelector('input[name="service_id"]:checked')) {
                return;
            }
        }
        if (bkFlow[bkStep].id === 'Staff') {
            if (!document.querySelector('input[name="staff_id"]:checked')) {
                return;
            }
        }
        bkShow(bkFlow[bkStep + 1].id);
    } else {
        bkShow(bkFlow[bkStep - 1].id);
    }
}

function bkFilterCat(cat, btn) {
    document.querySelectorAll('.bk-cat').forEach(function(b){b.classList.remove('on')});
    btn.classList.add('on');
    document.querySelectorAll('.bk-svc-row').forEach(function(c){
        c.style.display = (cat==='all' || c.dataset.cat===cat) ? '' : 'none';
    });
}

function bkSelectSvc(r) {
    document.querySelectorAll('.bk-svc-row').forEach(function(c){c.classList.remove('on')});
    r.closest('.bk-svc-row').classList.add('on');
    var date = document.querySelector('[name="appointment_date"]').value;
    var time = document.querySelector('[name="appointment_time"]').value;
    if (date && time) bkOnSchedule();
}

function bkSelectStaff(r) {
    document.querySelectorAll('.bk-staff').forEach(function(c){c.classList.remove('on')});
    r.closest('.bk-staff').classList.add('on');
}

function bkUpdateSummary() {
    var svc = document.querySelector('input[name="service_id"]:checked');
    var stf = document.querySelector('input[name="staff_id"]:checked');
    var date = document.querySelector('[name="appointment_date"]').value;
    var time = document.querySelector('[name="appointment_time"]').value;
    if (date) {
        var d = new Date(date + 'T00:00:00');
        document.getElementById('sumDate').textContent = d.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric', year:'numeric'});
    }
    if (time) {
        var p = time.split(':');
        var h = parseInt(p[0]);
        document.getElementById('sumTime').textContent = (h%12||12) + ':' + p[1] + ' ' + (h>=12?'PM':'AM');
    }
    if (svc) {
        document.getElementById('sumSvc').textContent = svc.dataset.name;
        document.getElementById('sumPrice').textContent = 'MMK' + parseFloat(svc.dataset.price).toLocaleString('en', {minimumFractionDigits:2});
        document.getElementById('sumDur').textContent = svc.dataset.duration + ' minutes';
    }
    if (stf) {
        document.getElementById('sumStaff').textContent = stf.closest('.bk-staff').querySelector('h4').textContent;
    }
}

function bkUpdateTimeSlots(busyRanges) {
    busyRanges = busyRanges || [];
    var date = document.querySelector('[name="appointment_date"]').value;
    var sel = document.getElementById('bookingTime');
    var today = new Date().toISOString().split('T')[0];
    var now = new Date();
    var svc = document.querySelector('input[name="service_id"]:checked');
    var dur = svc ? (parseInt(svc.dataset.duration) || 30) : 30;

    sel.querySelectorAll('option[value]').forEach(function(o){
        if(!o.value) return;
        var disable = false;
        if(date===today){
            var p=o.value.split(':');
            var st=new Date();st.setHours(parseInt(p[0]),parseInt(p[1]),0,0);
            var mn=new Date(now.getTime()-15*60000);
            if(st<=mn) disable = true;
        }
        if(!disable && busyRanges.length > 0){
            var p=o.value.split(':');
            var slotMin = parseInt(p[0])*60 + parseInt(p[1]);
            var slotMax = slotMin + dur;
            for(var i=0;i<busyRanges.length;i++){
                var bs=busyRanges[i].start.split(':');
                var be=busyRanges[i].end.split(':');
                var bStart=parseInt(bs[0])*60+parseInt(bs[1]);
                var bEnd=parseInt(be[0])*60+parseInt(be[1]);
                if(slotMin < bEnd && slotMax > bStart){
                    disable=true;
                    break;
                }
            }
        }
        o.disabled=disable; o.style.color=disable?'#ccc':'';
    });
    if(date===today && sel.value){
        var p=sel.value.split(':');var st=new Date();st.setHours(parseInt(p[0]),parseInt(p[1]),0,0);
        if(st<=new Date(now.getTime()-15*60000)) sel.value='';
    }
    if(sel.value && sel.disabled) sel.value='';
}

function bkOnSchedule() {
    var date = document.querySelector('[name="appointment_date"]').value;
    var time = document.querySelector('[name="appointment_time"]').value;
    var svc = document.querySelector('input[name="service_id"]:checked');
    var stf = document.querySelector('input[name="staff_id"]:checked');
    if (!date) return;
    var dur = svc ? (parseInt(svc.dataset.duration) || 30) : 30;
    var sid = stf ? stf.value : '';
    var url = 'booking.php?check_availability=1&date=' + encodeURIComponent(date) + '&duration=' + dur;
    if (time) url += '&time=' + encodeURIComponent(time);
    if (sid) url += '&staff_id=' + sid;
    fetch(url)
    .then(function(r){return r.json();}).then(function(data){
        document.querySelectorAll('.bk-staff').forEach(function(card){
            card.style.opacity='1';
            card.style.pointerEvents='auto';
            card.classList.remove('bk-staff-busy');
            var oldMsg = card.querySelector('.bk-staff-busy-msg');
            if(oldMsg) oldMsg.remove();
        });
        if(data.busy_staff && Object.keys(data.busy_staff).length > 0){
            document.querySelectorAll('#staffList input[name="staff_id"]').forEach(function(inp){
                if(data.busy_staff[inp.value]){
                    var card = inp.closest('.bk-staff');
                    card.classList.add('bk-staff-busy');
                    card.classList.remove('on');
                    card.style.opacity='1';
                    card.style.pointerEvents='none';
                    inp.checked = false;
                    var msg = document.createElement('div');
                    msg.className='bk-staff-busy-msg';
                    msg.textContent='Already booked until '+data.busy_staff[inp.value];
                    card.appendChild(msg);
                }
            });
        }
        bkUpdateTimeSlots(data.busy_ranges || []);
        var notice = document.getElementById('availNotice');
        if(svc){
            if(!data.staff_available){
                notice.style.display='flex';
                notice.className='bk-avail bk-avail-warn';
                notice.innerHTML='<i class="fas fa-exclamation-triangle"></i><span>This staff member may not be available during this time.</span>';
            } else if(data.end_time){
                notice.style.display='flex';
                notice.className='bk-avail bk-avail-ok';
                notice.innerHTML='<i class="fas fa-check-circle"></i><span>Available until '+data.end_time+'.</span>';
            } else {
                notice.style.display='none';
            }
        } else {
            notice.style.display='none';
        }
    }).catch(function(){
        bkUpdateTimeSlots([]);
    });
}

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var date = document.querySelector('[name="appointment_date"]').value;
    var time = document.querySelector('[name="appointment_time"]').value;
    var svc = document.querySelector('input[name="service_id"]:checked');
    var stf = document.querySelector('input[name="staff_id"]:checked');
    if (!date || !time || !svc || !stf) { form.submit(); return; }
    var dur = parseInt(svc.dataset.duration) || 30;
    var sid = stf.value;
    fetch('booking.php?check_availability=1&date=' + encodeURIComponent(date) + '&time=' + encodeURIComponent(time) + '&duration=' + dur + '&staff_id=' + sid)
    .then(function(r){return r.json();}).then(function(data){
        if(data.busy_staff && data.busy_staff[sid]){
            var notice = document.getElementById('availNotice');
            notice.style.display='flex';
            notice.className='bk-avail bk-avail-bad';
            notice.innerHTML='<i class="fas fa-exclamation-circle"></i><span>This slot was just booked by someone else. Please choose another time.</span>';
            bkShow('DateTime');
        } else {
            form.submit();
        }
    }).catch(function(){ form.submit(); });
});
</script>

<?php require_once '../includes/footer.php'; ?>
