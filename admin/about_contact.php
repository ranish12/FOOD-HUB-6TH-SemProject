<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_about':
                $title = $_POST['title'];
                $content = $_POST['content'];
                
                // Handle image upload
                $image_url = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../assets/images/about/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $new_filename = 'about_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_url = 'assets/images/about/' . $new_filename;
                    }
                }
                
                $stmt = $conn->prepare("UPDATE AboutUs SET title = ?, content = ?" . ($image_url ? ", image_url = ?" : "") . " WHERE id = 1");
                $params = [$title, $content];
                if ($image_url) {
                    $params[] = $image_url;
                }
                $stmt->execute($params);
                
                $success = "About Us content updated successfully!";
                break;
                
            case 'update_contact':
                $title = $_POST['title'];
                $address = $_POST['address'];
                $phone = $_POST['phone'];
                $email = $_POST['email'];
                
                $stmt = $conn->prepare("UPDATE ContactInfo SET title = ?, address = ?, phone = ?, email = ? WHERE id = 1");
                $stmt->execute([$title, $address, $phone, $email]);
                
                $success = "Contact information updated successfully!";
                break;
        }
    }
}

// Get current content
$stmt = $conn->query("SELECT * FROM AboutUs WHERE id = 1");
$about = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$about) {
    // Insert default About Us data if not exists
    $stmt = $conn->prepare("INSERT INTO AboutUs (id, title, content) VALUES (1, 'About Food Hub', 'Welcome to Food Hub')");
    $stmt->execute();
    $about = ['id' => 1, 'title' => 'About Food Hub', 'content' => 'Welcome to Food Hub', 'image_url' => ''];
}

$stmt = $conn->query("SELECT * FROM ContactInfo WHERE id = 1");
$contact = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    // Insert default Contact Info if not exists
    $stmt = $conn->prepare("INSERT INTO ContactInfo (id, title, address, phone, email, working_hours) VALUES (1, 'Contact Us', 'Banepa', '+977-XXX', 'contact@foodhub.com', 'Mon-Sun: 10 AM - 10 PM')");
    $stmt->execute();
    $contact = ['id' => 1, 'title' => 'Contact Us', 'address' => 'Banepa', 'phone' => '+977-XXX', 'email' => 'contact@foodhub.com', 'working_hours' => 'Mon-Sun: 10 AM - 10 PM'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About & Contact - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar .active {
            background-color: #0d6efd;
        }
        .main-content {
            padding: 20px;
        }
        .content-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navigation.php'; ?>
    <div class="container">
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 main-content">
            <h2 class="mb-4">Manage About & Contact Pages</h2>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- About Us Section -->
                <div class="content-section">
                    <h3>About Us Content</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_about">
                        
                        <div class="mb-3">
                            <label for="about_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="about_title" name="title" 
                                   value="<?php echo htmlspecialchars($about['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="about_content" class="form-label">Content</label>
                            <textarea class="form-control" id="about_content" name="content" rows="6" required><?php echo htmlspecialchars($about['content']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="about_image" class="form-label">Image</label>
                            <?php if ($about['image_url']): ?>
                                <div class="mb-2">
                                    <img src="../<?php echo htmlspecialchars($about['image_url']); ?>" 
                                         alt="Current About Us image" 
                                         class="preview-image">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="about_image" name="image" accept="image/*">
                            <small class="text-muted">Leave empty to keep the current image</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update About Us</button>
                    </form>
                </div>

                <!-- Contact Us Section -->
                <div class="content-section">
                    <h3>Contact Information</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_contact">
                        
                        <div class="mb-3">
                            <label for="contact_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="contact_title" name="title" 
                                   value="<?php echo htmlspecialchars($contact['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_address" class="form-label">Address</label>
                            <textarea class="form-control" id="contact_address" name="address" rows="3"><?php echo htmlspecialchars($contact['address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="contact_phone" name="phone" 
                                   value="<?php echo htmlspecialchars($contact['phone']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="contact_email" name="email" 
                                   value="<?php echo htmlspecialchars($contact['email']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Contact Info</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 