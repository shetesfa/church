<?php
// photo_upload.php - Fixed with directory creation and dual camera options
require_once 'config.php';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/student_photos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    file_put_contents($upload_dir . 'index.html', '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Access Denied</h1></body></html>');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member = null;

if($id) {
    $result = $conn->query("SELECT * FROM members WHERE id=$id");
    if($result && $result->num_rows > 0) {
        $member = $result->fetch_assoc();
    }
}

// Handle photo upload - 50MB MAX
if(isset($_POST['upload_photo']) && $member) {
    if(isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
        $max_size = 50 * 1024 * 1024; // 50MB
        
        if(!in_array($_FILES['photo']['type'], $allowed)) {
            $error = "የምስል አይነት አይፈቀድም! በJPEG, PNG ወይም GIF ይሁን";
        } elseif($_FILES['photo']['size'] > $max_size) {
            $error = "የምስል መጠን ከ50ሜባ መብለጥ የለበትም";
        } else {
            // Delete old photo
            if(!empty($member['photo'])) {
                $old_photo = $upload_dir . $member['photo'];
                if(file_exists($old_photo)) {
                    unlink($old_photo);
                }
            }
            
            // Upload new photo
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = "ATS_" . $member['year'] . "_" . time() . "_" . $member['id'] . "." . $ext;
            $upload_path = $upload_dir . $filename;
            
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE members SET photo=? WHERE id=?");
                $stmt->bind_param("si", $filename, $member['id']);
                if($stmt->execute()) {
                    $_SESSION['message'] = "ፎቶ በተሳካ ሁኔታ ተለውጧል!";
                    $_SESSION['msg_type'] = "success";
                    header("Location: view.php?id=" . $member['member_id_number']);
                    exit();
                }
                $stmt->close();
            } else {
                $error = "ፎቶ መጫን አልተሳካም. እባክዎ የፋይል ፈቃድ ያረጋግጡ.";
            }
        }
    }
}

// Handle camera capture
if(isset($_POST['capture_photo']) && $member && isset($_POST['image_data'])) {
    $image_data = $_POST['image_data'];
    $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $image_data = base64_decode($image_data);
    
    // Delete old photo
    if(!empty($member['photo'])) {
        $old_photo = $upload_dir . $member['photo'];
        if(file_exists($old_photo)) {
            unlink($old_photo);
        }
    }
    
    // Save new photo
    $filename = "ATS_" . $member['year'] . "_" . time() . "_" . $member['id'] . ".jpg";
    $upload_path = $upload_dir . $filename;
    
    if(file_put_contents($upload_path, $image_data)) {
        $stmt = $conn->prepare("UPDATE members SET photo=? WHERE id=?");
        $stmt->bind_param("si", $filename, $member['id']);
        if($stmt->execute()) {
            $_SESSION['message'] = "ፎቶ በተሳካ ሁኔታ ተነስቷል!";
            $_SESSION['msg_type'] = "success";
            header("Location: view.php?id=" . $member['member_id_number']);
            exit();
        }
        $stmt->close();
    } else {
        $error = "ፎቶ ማስቀመጥ አልተሳካም.";
    }
}

$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ፎቶ ማስተዳደር | Photo Management</title>
    
    <style>
        /* ============================================================
           Certificate Styles - Photo Upload
           ============================================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Ethiopic', 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #8B4513, #A52A2A, #DAA520);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            background: linear-gradient(135deg, #FFD700, #FF8C00, #B22222);
            color: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            animation: glow 3s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(218,165,32,0.3); }
            50% { box-shadow: 0 0 40px rgba(218,165,32,0.6); }
        }

        .container::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px double rgba(255,255,255,0.5);
            border-radius: 15px;
            pointer-events: none;
        }

        .header {
            text-align: center;
            padding: 30px 20px;
            position: relative;
            z-index: 2;
        }

        .church-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .church-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header h1 {
            font-size: 32px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header .amharic {
            font-size: 28px;
            color: #FFD700;
            font-family: 'Noto Sans Ethiopic', sans-serif;
            border-bottom: 2px solid #FFD700;
            display: inline-block;
            padding-bottom: 5px;
        }

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 10px 20px;
            background: rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
            background: rgba(255,255,255,0.15);
            transition: all 0.2s;
            border: 1px solid rgba(255,215,0,0.3);
        }

        .nav a:hover {
            background: #FFD700;
            color: #8B4513;
            transform: translateY(-1px);
            border-color: #FFD700;
        }

        /* Messages */
        .message {
            margin: 15px 20px;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }

        .success {
            background: rgba(40, 167, 69, 0.2);
            color: #FFD700;
            border-left: 4px solid #28a745;
        }

        .error {
            background: rgba(220, 53, 69, 0.2);
            color: #ffc107;
            border-left: 4px solid #dc3545;
        }

        /* Photo Container */
        .photo-container {
            padding: 30px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .current-photo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #FFD700;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 15px;
        }

        .member-name {
            font-size: 28px;
            color: #FFD700;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .member-info {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* Category Badge */
        .category-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 14px;
            margin-top: 5px;
        }

        .badge-children {
            background: #3498db;
            color: white;
        }

        .badge-youth {
            background: #2ecc71;
            color: white;
        }

        /* Upload Area - Default Camera (Normal) */
        .upload-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .upload-area {
            border: 2px dashed #FFD700;
            border-radius: 10px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .upload-area:hover {
            background: rgba(255,215,0,0.2);
            border-color: white;
            transform: translateY(-2px);
        }

        .upload-icon {
            font-size: 40px;
            color: #FFD700;
            margin-bottom: 10px;
        }

        .upload-text {
            font-size: 14px;
            color: white;
            margin-bottom: 5px;
        }

        .upload-hint {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
        }

        /* Camera Section - Default Normal Camera */
        .camera-section {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
        }

        .camera-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .camera-header h3 {
            color: #FFD700;
            font-size: 18px;
        }

        .camera-selector {
            display: flex;
            gap: 10px;
        }

        .camera-btn {
            padding: 8px 15px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .camera-btn.active {
            background: #FFD700;
            color: #8B4513;
            border-color: #FFD700;
        }

        .camera-btn:hover {
            background: #FFD700;
            color: #8B4513;
        }

        #camera {
            width: 100%;
            max-width: 400px;
            margin: 0 auto 15px;
            border-radius: 10px;
            border: 2px solid #FFD700;
            background: #000;
        }

        .camera-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Preview */
        .preview {
            margin: 15px 0;
            display: none;
        }

        .preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            border: 3px solid #FFD700;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin: 3px;
            font-family: 'Noto Sans Ethiopic', sans-serif;
            display: inline-block;
            text-decoration: none;
        }

        .btn-primary {
            background: #FFD700;
            color: #8B4513;
            border: 1px solid #FFD700;
        }

        .btn-primary:hover {
            background: transparent;
            color: #FFD700;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }

        .btn-success:hover {
            background: transparent;
            color: #28a745;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }

        .btn-danger:hover {
            background: transparent;
            color: #dc3545;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: 1px solid #6c757d;
        }

        .btn-secondary:hover {
            background: transparent;
            color: #6c757d;
        }

        .footer {
            text-align: center;
            padding: 15px;
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            border-top: 1px solid rgba(255,215,0,0.3);
            position: relative;
            z-index: 2;
        }

        @media (max-width: 768px) {
            .upload-options {
                grid-template-columns: 1fr;
            }
            
            .current-photo {
                width: 150px;
                height: 150px;
            }
            
            .member-name {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-logo">
                <img src="images/icon.png" alt="Church Logo">
            </div>
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">የአባላት ፎቶ አስተዳደር</div>
        </div>

        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="view.php?id=<?php echo $member['member_id_number'] ?? ''; ?>">ተመለስ</a>
        </div>

        <?php if($message): ?>
        <div class="message <?php echo $msg_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
        <div class="message error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if($member): ?>
        <div class="photo-container">
            <img src="<?php echo !empty($member['photo']) ? 'uploads/student_photos/'.$member['photo'] : 'images/icon.png'; ?>" 
                 class="current-photo" id="currentPhoto">
            
            <div class="member-name"><?php echo htmlspecialchars($member['full_name']); ?></div>
            <div class="member-info">
                <?php 
                $age = calculateAge($member['birth_date']);
                $category_display = ($member['category'] == 'ህጻናት') ? '🧒 ህጻናት' : '👦 ወጣቶች';
                $category_class = ($member['category'] == 'ህጻናት') ? 'badge-children' : 'badge-youth';
                ?>
                <span class="category-badge <?php echo $category_class; ?>"><?php echo $category_display; ?></span>
                <span style="margin-left: 10px;">🎂 <?php echo $age; ?> ዓመት</span>
            </div>
            <div class="member-info">
                መታወቂያ: <?php echo $member['member_id_number'] ?? 'ATS'.str_pad($member['id'],5,'0',STR_PAD_LEFT); ?> | ዓመት: <?php echo $member['year']; ?>
            </div>

            <!-- Two Upload Options -->
            <div class="upload-options">
                <!-- Option 1: File Upload (Normal) -->
                <div class="upload-area" onclick="document.getElementById('photoInput').click()">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">ፋይል ምረጥ</div>
                    <div class="upload-hint">ከኮምፒውተር ፎቶ ምረጥ</div>
                </div>

                <!-- Option 2: Camera (Default Normal) -->
                <div class="upload-area" onclick="openCamera()">
                    <div class="upload-icon">📸</div>
                    <div class="upload-text">ካሜራ ክፈት</div>
                    <div class="upload-hint">አዲስ ፎቶ አንሳ</div>
                </div>
            </div>

            <!-- Hidden File Input -->
            <form method="POST" enctype="multipart/form-data" id="photoForm">
                <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;" onchange="previewPhoto(this)">
                
                <div id="preview" class="preview">
                    <img id="previewImg" src="#" alt="Preview">
                    <p style="font-size:12px; margin-top:5px;">አዲስ ፎቶ ቅድመ እይታ</p>
                    <button type="submit" name="upload_photo" class="btn btn-success" id="uploadBtn">
                        ✅ ፎቶ ስቀል
                    </button>
                </div>
            </form>

            <!-- Camera Section - Default Normal Camera -->
            <div class="camera-section" id="cameraSection" style="display: none;">
                <div class="camera-header">
                    <h3>📸 ካሜራ</h3>
                    <div class="camera-selector">
                        <button class="camera-btn active" onclick="switchCamera('user')" id="normalCamBtn">📱 መደበኛ</button>
                        <button class="camera-btn" onclick="switchCamera('environment')" id="selfieCamBtn">🤳 ሴልፊ</button>
                    </div>
                </div>
                
                <video id="camera" autoplay playsinline></video>
                <canvas id="canvas" style="display:none;"></canvas>
                
                <div class="camera-controls">
                    <button class="btn btn-primary" onclick="capturePhoto()">📸 ፎቶ አንሳ</button>
                    <button class="btn btn-secondary" onclick="closeCamera()">❌ ዝጋ</button>
                </div>
                
                <p style="font-size:11px; color:rgba(255,255,255,0.7); margin-top:10px;">
                    መደበኛ ካሜራ ነባሪ ነው። ወደ ሴልፊ ለመቀየር ከላይ ያሉትን ቁልፎች ይጫኑ።
                </p>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 20px;">
                <?php if(!empty($member['photo'])): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('ፎቶ መሰረዝ እርግጠኛ ነህ?')">
                    <button type="submit" name="delete_photo" class="btn btn-danger">
                        🗑️ ፎቶ ሰርዝ
                    </button>
                </form>
                <?php endif; ?>

                <a href="view.php?id=<?php echo $member['member_id_number']; ?>" class="btn btn-secondary">
                    🔙 ተመለስ
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት
        </div>
    </div>

    <script>
        // Camera variables
        let currentStream = null;
        let currentCamera = 'user'; // 'user' for front (selfie), 'environment' for back (normal)
        
        // Preview photo from file
        function previewPhoto(input) {
            const preview = document.getElementById('preview');
            const previewImg = document.getElementById('previewImg');
            const uploadBtn = document.getElementById('uploadBtn');
            
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Open camera - default to normal (environment)
        function openCamera() {
            const section = document.getElementById('cameraSection');
            section.style.display = 'block';
            
            // Default to normal camera (environment)
            currentCamera = 'environment';
            document.getElementById('normalCamBtn').classList.add('active');
            document.getElementById('selfieCamBtn').classList.remove('active');
            
            startCamera(currentCamera);
        }

        // Start camera with specified facing mode
        function startCamera(facingMode) {
            const camera = document.getElementById('camera');
            
            // Stop any existing stream
            if(currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
            }
            
            const constraints = {
                video: {
                    facingMode: facingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
            
            if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia(constraints)
                    .then(function(stream) {
                        currentStream = stream;
                        camera.srcObject = stream;
                    })
                    .catch(function(error) {
                        alert('ካሜራ መክፈት አልተቻለም: ' + error.message);
                        document.getElementById('cameraSection').style.display = 'none';
                    });
            }
        }

        // Switch between normal and selfie camera
        function switchCamera(mode) {
            currentCamera = mode;
            
            // Update button states
            if(mode === 'user') {
                document.getElementById('selfieCamBtn').classList.add('active');
                document.getElementById('normalCamBtn').classList.remove('active');
            } else {
                document.getElementById('normalCamBtn').classList.add('active');
                document.getElementById('selfieCamBtn').classList.remove('active');
            }
            
            startCamera(mode);
        }

        // Close camera
        function closeCamera() {
            if(currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                currentStream = null;
            }
            document.getElementById('cameraSection').style.display = 'none';
        }

        // Capture photo from camera
        function capturePhoto() {
            const camera = document.getElementById('camera');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');
            
            // Set canvas dimensions to match video
            canvas.width = camera.videoWidth;
            canvas.height = camera.videoHeight;
            
            // Draw video frame to canvas
            context.drawImage(camera, 0, 0, canvas.width, canvas.height);
            
            // Get image data as base64
            const imageData = canvas.toDataURL('image/jpeg', 0.9);
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="image_data" value="' + imageData + '">' +
                            '<input type="hidden" name="capture_photo" value="1">';
            document.body.appendChild(form);
            form.submit();
        }

        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>