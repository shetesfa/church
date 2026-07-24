<?php
// register.php - Register new member with category selection and dynamic fields
require_once 'config.php';

$message = '';
$error = '';

// Generate next ATS ID
$next_ats_id = generateNextATSId($conn);

// Get current active year
$current_year = isset($_SESSION['current_year']) ? $_SESSION['current_year'] : 2018;

if(isset($_POST['register'])) {
    // Get all form data
    $full_name = $_POST['full_name'] ?? '';
    $christian_name = $_POST['christian_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $birth_date = $_POST['birth_date'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $emergency_name = $_POST['emergency_name'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    $confession_father = $_POST['confession_father'] ?? '';
    $confession_phone = $_POST['confession_phone'] ?? '';
    $registration_date = $_POST['registration_date'] ?? date('Y-m-d');
    $meshena = $_POST['meshena'] ?? null;
    $marital_status = $_POST['marital_status'] ?? null;
    $member_type = $_POST['member_type'] ?? null;
    $education_level = $_POST['education_level'] ?? '';
    $profession = $_POST['profession'] ?? '';
    $subcity = $_POST['subcity'] ?? '';
    $woreda = $_POST['woreda'] ?? '';
    $church_rank = $_POST['church_rank'] ?? '';
    $year = $_POST['year'] ?? $current_year;
    $remarks = $_POST['remarks'] ?? '';
    $category = $_POST['category'] ?? 'ህጻናት';
    
    // Generate ATS ID
    $member_id_number = $_POST['member_id_number'] ?? generateNextATSId($conn, $year);
    
    // Handle photo upload
    $photo_filename = null;
    if(isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
        $max_size = 50 * 1024 * 1024;
        
        if(!in_array($_FILES['photo']['type'], $allowed)) {
            $error = "የምስል አይነት አይፈቀድም! በJPEG, PNG, GIF ወይም WEBP ይሁን";
        } elseif($_FILES['photo']['size'] > $max_size) {
            $error = "የምስል መጠን ከ50ሜባ መብለጥ የለበትም";
        } else {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_filename = $member_id_number . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/student_photos/" . $photo_filename);
        }
    }
    
    if(empty($error)) {
        $stmt = $conn->prepare("INSERT INTO members (
            full_name, christian_name, phone, birth_date, gender,
            emergency_name, emergency_phone,
            confession_father, confession_phone,
            registration_date, meshena,
            marital_status, member_type,
            education_level, profession,
            subcity, woreda,
            church_rank, member_id_number,
            photo, year, remarks, status, category
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'temporary', ?)");
        
        $stmt->bind_param("sssssssssssssssssssssss", 
            $full_name, $christian_name, $phone, $birth_date, $gender,
            $emergency_name, $emergency_phone,
            $confession_father, $confession_phone,
            $registration_date, $meshena,
            $marital_status, $member_type,
            $education_level, $profession,
            $subcity, $woreda,
            $church_rank, $member_id_number,
            $photo_filename, $year, $remarks, $category
        );
        
        if($stmt->execute()) {
            $_SESSION['message'] = "አባል በተሳካ ሁኔታ ተመዝግቧል! መታወቂያ ቁጥር: $member_id_number";
            $_SESSION['msg_type'] = "success";
            header("Location: view.php?id=$member_id_number");
            exit();
        } else {
            $error = "ስህተት ተከስቷል: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>አዲስ አባል መመዝገቢያ | Register</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Noto Sans Ethiopic', serif; background: linear-gradient(135deg, #5D3A1A, #6B2E2E, #8B6913); padding: 20px; }
        
        .container { max-width: 1000px; margin: 0 auto; background: linear-gradient(135deg, #FDB931, #F39C12, #C0392B); border-radius: 20px; position: relative; overflow: hidden; }
        .container::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 2px double rgba(255,255,255,0.3); border-radius: 15px; }
        
        .header { text-align: center; padding: 30px; position: relative; z-index: 2; }
        .church-logo { width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 4px solid white; overflow: hidden; cursor: pointer; }
        .church-logo img { width: 100%; height: 100%; object-fit: cover; }
        .header h1 { font-size: 36px; color: white; text-transform: uppercase; letter-spacing: 3px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .header .amharic { font-size: 32px; color: #FFD700; border-bottom: 2px solid #FFD700; display: inline-block; }
        
        .nav { display: flex; flex-wrap: wrap; gap: 5px; padding: 10px 20px; background: rgba(0,0,0,0.3); }
        .nav a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 20px; font-size: 14px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,215,0,0.3); }
        .nav a:hover, .nav a.active { background: #FFD700; color: #5D3A1A; }
        
        .form-container { padding: 30px; position: relative; z-index: 2; }
        .form-title { font-size: 24px; color: #FFD700; margin-bottom: 20px; text-align: center; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .form-section { background: rgba(255,255,255,0.15); border-radius: 10px; padding: 20px; border-left: 4px solid #FFD700; }
        .section-title { font-size: 18px; color: #FFD700; margin-bottom: 15px; border-bottom: 1px solid rgba(255,215,0,0.3); padding-bottom: 5px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; color: rgba(255,255,255,0.9); }
        .form-control { width: 100%; padding: 10px; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #FFD700; }
        select.form-control option { background: #5D3A1A; }
        
        /* Category Section Styles */
        .category-section {
            grid-column: 1/-1;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #FFD700;
        }
        
        .category-options {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            margin-top: 10px;
        }
        
        .category-option {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
            transition: all 0.2s;
        }
        
        .category-option:hover {
            background: rgba(255,215,0,0.3);
            transform: translateY(-2px);
        }
        
        .category-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .category-option.selected {
            background: #FFD700;
            color: #5D3A1A;
            font-weight: bold;
        }
        
        .category-option.children { border-left: 4px solid #3498db; }
        .category-option.youth { border-left: 4px solid #2ecc71; }
        
        .category-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-children { background: #3498db; color: white; }
        .badge-youth { background: #2ecc71; color: white; }
        
        /* Field highlighting for children vs youth */
        .youth-only-field {
            transition: all 0.3s;
        }
        
        .youth-only-field.hidden {
            display: none;
        }
        
        .field-note {
            font-size: 11px;
            color: #f39c12;
            margin-top: 3px;
            font-style: italic;
        }
        
        .ats-id-field {
            background: rgba(255,215,0,0.2);
            border: 2px solid #FFD700;
            font-weight: bold;
            font-family: monospace;
            font-size: 16px;
        }
        
        .photo-section { grid-column: 1/-1; background: rgba(255,255,255,0.1); border-radius: 10px; padding: 30px; text-align: center; border: 2px dashed #FFD700; }
        .photo-preview { width: 150px; height: 150px; border-radius: 50%; border: 4px solid #FFD700; object-fit: cover; margin: 0 auto 15px; cursor: pointer; }
        
        .form-actions { margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end; }
        .btn { padding: 10px 25px; border: none; border-radius: 25px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #FFD700; color: #5D3A1A; }
        .btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,215,0,0.3); }
        .btn:hover { transform: translateY(-2px); }
        
        .footer { text-align: center; padding: 15px; border-top: 1px solid rgba(255,215,0,0.2); color: white; background: rgba(0,0,0,0.2); font-size: 12px; }
        
        .info-note {
            background: rgba(52, 152, 219, 0.2);
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            margin: 15px 0;
            font-size: 13px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-logo" onclick="window.location.href='index.php'">
                <img src="images/icon.png" alt="Logo">
            </div>
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">አዲስ አባል መመዝገቢያ</div>
        </div>

        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="temporary.php">ጊዜያዊ</a>
            <a href="register.php" class="active">አዲስ መዝግብ</a>
        </div>

        <?php if($error): ?>
        <div style="margin:15px 20px; padding:10px; background:rgba(192,57,43,0.3); border-left:4px solid #c0392b; color:white;">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-title">የአባል መረጃ ቅጽ</div>
            
            <form method="POST" enctype="multipart/form-data">
                <!-- ATS ID Display -->
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(255,215,0,0.15); border-radius: 10px; text-align: center;">
                    <span style="color: #FFD700; font-size: 16px;">የሚመደብ መታወቂያ ቁጥር:</span>
                    <span style="font-family: monospace; font-size: 24px; color: white; font-weight: bold; margin-left: 10px;"><?php echo $next_ats_id; ?></span>
                    <input type="hidden" name="member_id_number" value="<?php echo $next_ats_id; ?>">
                </div>

                <div class="form-grid">
                    <!-- Photo Section -->
                    <div class="photo-section">
                        <img id="photoPreview" src="images/icon.png" class="photo-preview" onclick="document.getElementById('photoInput').click()">
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;" onchange="previewPhoto(this)">
                        <div>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('photoInput').click()">ፎቶ ምረጥ</button>
                        </div>
                        <p style="font-size:12px; margin-top:10px; color:rgba(255,255,255,0.7);">ከፍተኛ 50ሜባ | JPG, PNG, GIF, WEBP</p>
                    </div>

                    <!-- Category Selection -->
                    <div class="category-section">
                        <div class="section-title">👥 ምድብ ይምረጡ / Select Category</div>
                        <div class="category-options">
                            <label class="category-option children <?php echo (!isset($_POST['category']) || $_POST['category'] == 'ህጻናት') ? 'selected' : ''; ?>">
                                <input type="radio" name="category" value="ህጻናት" <?php echo (!isset($_POST['category']) || $_POST['category'] == 'ህጻናት') ? 'checked' : ''; ?> onclick="toggleFields('children')">
                                <span style="font-size: 18px;">🧒 ህጻናት (Children)</span>
                                <span class="category-badge badge-children" style="margin-left: 10px;">0-12 ዓመት</span>
                            </label>
                            <label class="category-option youth <?php echo (isset($_POST['category']) && $_POST['category'] == 'ወጣቶች') ? 'selected' : ''; ?>">
                                <input type="radio" name="category" value="ወጣቶች" <?php echo (isset($_POST['category']) && $_POST['category'] == 'ወጣቶች') ? 'checked' : ''; ?> onclick="toggleFields('youth')">
                                <span style="font-size: 18px;">👦 ወጣቶች (Youth)</span>
                                <span class="category-badge badge-youth" style="margin-left: 10px;">13-18 ዓመት</span>
                            </label>
                        </div>
                        <div class="info-note">
                            <strong>ማስታወሻ:</strong> ለህጻናት አንዳንድ መረጃዎች አይጠየቁም
                        </div>
                    </div>

                    <!-- Basic Info -->
                    <div class="form-section">
                        <div class="section-title">መሠረታዊ መረጃ</div>
                        <div class="form-group">
                            <label>ሙሉ ስም *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>ክርስትና ስም</label>
                            <input type="text" name="christian_name" class="form-control">
                        </div>
                        <!-- Phone field - shows for youth only -->
                        <div class="form-group youth-only-field" id="phoneField">
                            <label>ስልክ ቁጥር *</label>
                            <input type="text" name="phone" class="form-control">
                            <div class="field-note">ለወጣቶች ብቻ</div>
                        </div>
                        <div class="form-group">
                            <label>የትውልድ ቀን</label>
                            <input type="date" name="birth_date" class="form-control" id="birthDate">
                        </div>
                        <div class="form-group">
                            <label>ጾታ *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">ይምረጡ</option>
                                <option value="ወ">ወንድ</option>
                                <option value="ሴ">ሴት</option>
                            </select>
                        </div>
                    </div>

                    <!-- Emergency Section -->
                    <div class="form-section">
                        <div class="section-title">🆘 የአደጋ ጊዜ</div>
                        <div class="form-group">
                            <label>የአደጋ ጊዜ ተጠሪ ስም</label>
                            <input type="text" name="emergency_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>ስልክ ቁጥር</label>
                            <input type="text" name="emergency_phone" class="form-control">
                        </div>
                        <!-- Confession fields - youth only -->
                        <div class="youth-only-field" id="confessionFields">
                            <div style="margin-top: 15px; border-top: 1px dashed rgba(255,215,0,0.3); padding-top: 15px;">
                                <div style="color: #FFD700; margin-bottom: 10px;">📿 ንስሐ (ለወጣቶች ብቻ)</div>
                                <div class="form-group">
                                    <label>የንስሐ አባት ስም</label>
                                    <input type="text" name="confession_father" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>ስልክ ቁጥር</label>
                                    <input type="text" name="confession_phone" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Details -->
                    <div class="form-section">
                        <div class="section-title">📋 ምዝገባ</div>
                        <div class="form-group">
                            <label>የተመዘገበበት ቀን</label>
                            <input type="date" name="registration_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>መሸኛ</label>
                            <select name="meshena" class="form-control">
                                <option value="">ይምረጡ</option>
                                <option value="አለ">አለ</option>
                                <option value="የለም">የለም</option>
                            </select>
                        </div>
                        <!-- Youth-only fields -->
                        <div class="youth-only-field" id="youthRegistrationFields">
                            <div class="form-group">
                                <label>የትዳር ሁኔታ</label>
                                <select name="marital_status" class="form-control">
                                    <option value="">ይምረጡ</option>
                                    <option value="ያገባ">ያገባ</option>
                                    <option value="ያላገባ">ያላገባ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>የአባልነት አይነት</label>
                                <select name="member_type" class="form-control">
                                    <option value="">ይምረጡ</option>
                                    <option value="እጩ">እጩ</option>
                                    <option value="መደበኛ">መደበኛ</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Education & Location -->
                    <div class="form-section">
                        <div class="section-title">📍 ትምህርትና አካባቢ</div>
                        <div class="form-group">
                            <label>የትምህርት ደረጃ</label>
                            <input type="text" name="education_level" class="form-control">
                        </div>
                        <!-- Profession field - youth only -->
                        <div class="form-group youth-only-field" id="professionField">
                            <label>የሙያ መስክ</label>
                            <input type="text" name="profession" class="form-control">
                            <div class="field-note">ለወጣቶች ብቻ</div>
                        </div>
                        <!-- For children: these are emergency location fields -->
                        <div class="form-group" id="subcityField">
                            <label id="subcityLabel"><?php echo "ክፍለ ከተማ"; ?></label>
                            <input type="text" name="subcity" class="form-control">
                        </div>
                        <div class="form-group" id="woredaField">
                            <label id="woredaLabel"><?php echo "ወረዳ"; ?></label>
                            <input type="text" name="woreda" class="form-control">
                        </div>
                        <!-- Church rank - youth only -->
                        <div class="form-group youth-only-field" id="churchRankField">
                            <label>የክህነት ደረጃ</label>
                            <input type="text" name="church_rank" class="form-control">
                            <div class="field-note">ለወጣቶች ብቻ</div>
                        </div>
                    </div>

                    <!-- Year & Remarks -->
                    <div class="form-section">
                        <div class="section-title">📅 ዓመት እና ማስታወሻ</div>
                        <div class="form-group">
                            <label>ዓመት *</label>
                            <select name="year" class="form-control" required>
                                <?php for($y=2010; $y<=2025; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $current_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ማስታወሻ</label>
                            <textarea name="remarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">አጽዳ</button>
                    <button type="submit" name="register" class="btn btn-primary">መዝግብ</button>
                    <a href="members.php" class="btn btn-secondary">ተመለስ</a>
                </div>
            </form>
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት
        </div>
    </div>

    <script>
        function previewPhoto(input) {
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Toggle fields based on category
        function toggleFields(category) {
            const youthFields = document.querySelectorAll('.youth-only-field');
            
            if(category === 'children') {
                // Hide youth-only fields
                youthFields.forEach(field => {
                    field.classList.add('hidden');
                });
                
                // Change labels for children
                document.getElementById('subcityLabel').innerHTML = 'የአደጋ ጊዜ ክፍለ ከተማ';
                document.getElementById('woredaLabel').innerHTML = 'የአደጋ ጊዜ ወረዳ';
                
            } else {
                // Show youth-only fields
                youthFields.forEach(field => {
                    field.classList.remove('hidden');
                });
                
                // Restore labels for youth
                document.getElementById('subcityLabel').innerHTML = 'ክፍለ ከተማ';
                document.getElementById('woredaLabel').innerHTML = 'ወረዳ';
            }
        }
        
        // Auto-suggest category based on age
        document.getElementById('birthDate').addEventListener('change', function() {
            if(this.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if(monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                const childrenRadio = document.querySelector('input[value="ህጻናት"]');
                const youthRadio = document.querySelector('input[value="ወጣቶች"]');
                
                // Auto-select based on age
                if(age < 13) {
                    childrenRadio.checked = true;
                    childrenRadio.closest('.category-option').classList.add('selected');
                    youthRadio.closest('.category-option').classList.remove('selected');
                    toggleFields('children');
                } else {
                    youthRadio.checked = true;
                    youthRadio.closest('.category-option').classList.add('selected');
                    childrenRadio.closest('.category-option').classList.remove('selected');
                    toggleFields('youth');
                }
            }
        });
        
        // Highlight selected category
        document.querySelectorAll('input[name="category"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.category-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.closest('.category-option').classList.add('selected');
                
                if(this.value === 'ህጻናት') {
                    toggleFields('children');
                } else {
                    toggleFields('youth');
                }
            });
        });
        
        // Set initial state based on default selection
        window.onload = function() {
            const defaultCategory = document.querySelector('input[name="category"]:checked').value;
            if(defaultCategory === 'ህጻናት') {
                toggleFields('children');
            } else {
                toggleFields('youth');
            }
        };
    </script>
</body>
</html>