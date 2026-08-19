<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

$title = 'Services';
$message = '';
$error = '';

$upload_dir = '../assets/uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $category_id = $_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $status = $_POST['status'] ?? 'active';
        $image = '';

        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO services (category_id, name, description, price, duration, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $description, $price, $duration, $image, $status]);
            $message = 'Service added successfully.';
        } else {
            $id = $_POST['id'];
            if ($image) {
                $stmt = $pdo->prepare("UPDATE services SET category_id=?, name=?, description=?, price=?, duration=?, image=?, status=? WHERE id=?");
                $stmt->execute([$category_id, $name, $description, $price, $duration, $image, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE services SET category_id=?, name=?, description=?, price=?, duration=?, status=? WHERE id=?");
                $stmt->execute([$category_id, $name, $description, $price, $duration, $status, $id]);
            }
            $message = 'Service updated successfully.';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM services WHERE id=?");
        $stmt->execute([$id]);
        $message = 'Service deleted successfully.';
    }
}

$services = $pdo->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id ORDER BY s.status ASC, s.name ASC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status='active'")->fetchAll();

$categoryCounts = [];
foreach ($services as $svc) {
    $catName = $svc['category_name'];
    $categoryCounts[$catName] = ($categoryCounts[$catName] ?? 0) + 1;
}

require_once '../includes/header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Service Management</h1>
        <button onclick="openModal('addModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm w-full sm:w-auto text-center">
            <i class="fas fa-plus mr-2"></i>Add Service
        </button>
    </div>

    <?php if ($message): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="flex flex-wrap gap-2 mb-4">
        <button onclick="filterCategory('all')" class="svc-filter-btn px-4 py-1.5 rounded-full text-sm font-medium border-2 border-blue-600 bg-blue-600 text-white transition-all" data-category="all">All (<?php echo count($services); ?>)</button>
        <?php foreach ($categories as $cat): ?>
        <button onclick="filterCategory('<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>')" class="svc-filter-btn px-4 py-1.5 rounded-full text-sm font-medium border-2 border-gray-200 text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-all bg-white" data-category="<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>">
            <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $categoryCounts[$cat['name']] ?? 0; ?>)
        </button>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Service Name</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Category</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Price</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Duration</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody id="servicesTableBody">
                <?php foreach ($services as $svc): ?>
                <tr class="border-b hover:bg-gray-50 svc-row" data-category="<?php echo htmlspecialchars($svc['category_name'], ENT_QUOTES); ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                                <?php if ($svc['image']): ?>
                                    <img src="/nail_salon/assets/uploads/<?php echo $svc['image']; ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400"><i class="fas fa-leaf"></i></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($svc['name']); ?></p>
                                <p class="text-xs text-gray-500 truncate max-w-[200px]" title="<?php echo htmlspecialchars($svc['description'] ?? ''); ?>"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <?php echo htmlspecialchars($svc['category_name']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900">MMK <?php echo number_format($svc['price'], 2); ?></td>
                    <td class="px-6 py-4 text-gray-600"><i class="fas fa-clock mr-1 text-gray-400"></i><?php echo $svc['duration']; ?> min</td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 text-xs rounded-full <?php echo $svc['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo ucfirst($svc['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-2 whitespace-nowrap">
                        <button onclick='editService(<?php echo json_encode($svc); ?>)' class="text-blue-500 hover:text-blue-700 p-1">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this service?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $svc['id']; ?>">
                            <button type="submit" class="text-red-500 hover:text-red-700 p-1"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($services)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-spa text-3xl mb-2 text-gray-300"></i>
                        <p>No services available yet. Add your first service!</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Add Service</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price (MMK)</label>
                        <input type="number" name="price" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                        <input type="number" name="duration" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Edit Service</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="edit_category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="edit_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price (MMK)</label>
                        <input type="number" name="price" id="edit_price" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                        <input type="number" name="duration" id="edit_duration" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image (leave empty to keep current)</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }
function editService(svc) {
    document.getElementById('edit_id').value = svc.id;
    document.getElementById('edit_category_id').value = svc.category_id;
    document.getElementById('edit_name').value = svc.name;
    document.getElementById('edit_description').value = svc.description || '';
    document.getElementById('edit_price').value = svc.price;
    document.getElementById('edit_duration').value = svc.duration;
    document.getElementById('edit_status').value = svc.status;
    openModal('editModal');
}
function filterCategory(category) {
    document.querySelectorAll('.svc-filter-btn').forEach(btn => {
        btn.classList.remove('border-blue-600', 'bg-blue-600', 'text-white');
        btn.classList.add('border-gray-200', 'text-gray-600', 'bg-white');
    });
    const active = document.querySelector(`.svc-filter-btn[data-category="${category}"]`);
    if (active) {
        active.classList.remove('border-gray-200', 'text-gray-600', 'bg-white');
        active.classList.add('border-blue-600', 'bg-blue-600', 'text-white');
    }
    document.querySelectorAll('.svc-row').forEach(row => {
        if (category === 'all' || row.dataset.category === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
<?php require_once '../includes/footer.php'; ?>
