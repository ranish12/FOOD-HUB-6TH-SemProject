<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $image_url = $_POST['image_url'];
                
                $stmt = $conn->prepare("INSERT INTO Categories (name, description, image_url) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $description, $image_url])) {
                    $success = "Category added successfully!";
                } else {
                    $error = "Failed to add category.";
                }
                break;
                
            case 'edit':
                $category_id = $_POST['category_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $image_url = $_POST['image_url'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE Categories SET name = ?, description = ?, image_url = ?, is_active = ? WHERE category_id = ?");
                if ($stmt->execute([$name, $description, $image_url, $is_active, $category_id])) {
                    $success = "Category updated successfully!";
                } else {
                    $error = "Failed to update category.";
                }
                break;
                
            case 'delete':
                $category_id = $_POST['category_id'];
                
                // Check if category has menu items
                $stmt = $conn->prepare("SELECT COUNT(*) FROM Menu WHERE category_id = ?");
                $stmt->execute([$category_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Cannot delete category with existing menu items.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM Categories WHERE category_id = ?");
                    if ($stmt->execute([$category_id])) {
                        $success = "Category deleted successfully!";
                    } else {
                        $error = "Failed to delete category.";
                    }
                }
                break;
        }
    }
}

// Get all categories
$stmt = $conn->query("SELECT * FROM Categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Food Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar a.active {
            background-color: #007bff;
        }
        .main-content {
            padding: 20px;
        }
        .category-card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navigation.php'; ?>
    <div class="container">
        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <h2 class="mb-4">Category Management</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <!-- <h5 class="mb-0">Add New Category</h5> -->
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Add Category Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Category Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="image_url" class="form-label">Image URL</label>
                                        <input type="text" class="form-control" id="image_url" name="image_url">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <input type="text" class="form-control" id="description" name="description">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="row">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-4">
                        <div class="card category-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <div>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $category['category_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $category['category_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $category['category_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                        <div class="mb-3">
                                            <label for="edit_name<?php echo $category['category_id']; ?>" class="form-label">Category Name</label>
                                            <input type="text" class="form-control" id="edit_name<?php echo $category['category_id']; ?>" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_description<?php echo $category['category_id']; ?>" class="form-label">Description</label>
                                            <input type="text" class="form-control" id="edit_description<?php echo $category['category_id']; ?>" name="description" value="<?php echo htmlspecialchars($category['description']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_image_url<?php echo $category['category_id']; ?>" class="form-label">Image URL</label>
                                            <input type="text" class="form-control" id="edit_image_url<?php echo $category['category_id']; ?>" name="image_url" value="<?php echo htmlspecialchars($category['image_url']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="edit_is_active<?php echo $category['category_id']; ?>" name="is_active" <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="edit_is_active<?php echo $category['category_id']; ?>">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal<?php echo $category['category_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Delete Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete this category?</p>
                                    <p><strong><?php echo htmlspecialchars($category['name']); ?></strong></p>
                                </div>
                                <div class="modal-footer">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 