<?php
// edit.php - Edit member information with category support
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
if(!$id) {
    header("Location: members.php");
    exit();
}

$result = $conn->query("SELECT * FROM members WHERE id=$id");
if($result->num_rows == 0) {
    header("Location: members.php");
    exit();
}
$member = $result->fetch_assoc();

if(isset($_POST['update'])) {
    $full_name = $_POST['full_name'] ?? '';
    $christian_name = $_POST['christian_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $birth_date = $_POST['birth_date'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $emergency_name = $_POST['emergency_name'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    $confession_father = $_POST['confession_father'] ?? '';
    $confession_phone = $_POST['confession_phone'] ?? '';
    $registration_date = $_POST['registration_date'] ?? null;
    $meshena = $_POST['meshena'] ?? null;
    $marital_status = $_POST['marital_status'] ?? null;
    $member_type = $_POST['member_type'] ?? null;
    $education_level = $_POST['education_level'] ?? '';
    $profession = $_POST['profession'] ?? '';
    $subcity = $_POST['subcity'] ?? '';
    $woreda = $_POST['woreda'] ?? '';
    $church_rank = $_POST['church_rank'] ?? '';
    $member_id_number = $_POST['member_id_number'] ?? '';
    $year = $_POST['year'] ?? $member['year'];
    $remarks = $_POST['remarks'] ?? '';
    $status = $_POST['status'] ?? $member['status'];
    $category = $_POST['category'] ?? $member['category']; // NEW: category field
    
    $stmt = $conn->prepare("UPDATE members SET 
        full_name=?, christian_name=?, phone=?, birth_date=?, gender=?,
        emergency_name=?, emergency_phone=?,
        confession_father=?, confession_phone=?,
        registration_date=?, meshena=?,
        marital_status=?, member_type=?,
        education_level=?, profession=?,
        subcity=?, woreda=?,
        church_rank=?, member_id_number=?,
        year=?, remarks=?, status=?, category=?
        WHERE id=?");
    
    $stmt->bind_param("sssssssssssssssssssssssi", 
        $full_name, $christian_name, $phone, $birth_date, $gender,
        $emergency_name, $emergency_phone,
        $confession_father, $confession_phone,
        $registration_date, $meshena,
        $marital_status, $member_type,
        $education_level, $profession,
        $subcity, $woreda,
        $church_rank, $member_id_number,
        $year, $remarks, $status, $category,
        $id
    );
    
    if($stmt->execute()) {
        $_SESSION['message'] = "የአባል መረጃ በተሳካ ሁኔታ ተሻሽሏል!";
        $_SESSION['msg_type'] = "success";
        header("Location: view.php?id=" . $member['member_id_number']);
        exit();
    }
    $stmt->close();
}

// Calculate age for display
$age = calculateAge($member['birth_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>አርትዕ | Edit Member</title>
    <style>
        /* Certificate CSS */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Noto Sans Ethiopic', serif; background: linear-gradient(135deg, #8B4513, #A52A2A, #DAA520); padding: 20px; }
        
        .container { max-width: 1000px; margin: 0 auto; background: linear-gradient(135deg, #FFD700, #FF8C00, #B22222); border-radius: 20px; color: white; position: relative; overflow: hidden; animation: glow 3s ease-in-out infinite; }
        @keyframes glow { 0%,100% { box-shadow: 0 0 20px rgba(218,165,32,0.3); } 50% { box-shadow: 0 0 40px rgba(218,165,32,0.6); } }
        .container::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 2px double rgba(255,255,255,0.5); border-radius: 15px; }
        
        .header { text-align: center; padding: 30px; position: relative; z-index: 2; }
        .church-logo { width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 4px solid white; overflow: hidden; }
        .church-logo img { width: 100%; height: 100%; object-fit: cover; }
        .header h1 { font-size: 36px; color: white; text-transform: uppercase; letter-spacing: 3px; }
        .header .amharic { font-size: 32px; color: #FFD700; border-bottom: 2px solid #FFD700; display: inline-block; }
        
        .nav { display: flex; flex-wrap: wrap; gap: 5px; padding: 10px 20px; background: rgba(0,0,0,0.2); }
        .nav a { color: white; text-decoration: none; padding: 6px 12px; border-radius: 15px; font-size: 13px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,215,0,0.3); }
        .nav a:hover { background: #FFD700; color: #8B4513; }
        
        .form-container { padding: 30px; position: relative; z-index: 2; }
        .form-title { font-size: 24px; color: #FFD700; margin-bottom: 20px; text-align: center; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .form-section { background: rgba(255,255,255,0.1); border-radius: 10px; padding: 20px; border-left: 4px solid #FFD700; }
        .section-title { font-size: 18px; color: #FFD700; margin-bottom: 15px; border-bottom: 1px solid rgba(255,215,0,0.3); padding-bottom: 5px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; color: rgba(255,255,255,0.9); }
        .form-control { width: 100%; padding: 10px; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #FFD700; }
        select.form-control option { background: #8B4513; }
        
        /* NEW: Category selection styles */
        .category-section {
            grid-column: 1/-1;
            background: rgba(255,255,255,0.1);
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
        
        /* Conditional fields */
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
        
        .photo-section { display: flex; gap: 20px; align-items: center; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px; margin-bottom: 20px; }
        .current-photo { width: 100px; height: 100px; border-radius: 50%; border: 3px solid #FFD700; object-fit: cover; }
        
        .form-actions { margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end; }
        .btn { padding: 10px 25px; border: none; border-radius: 25px; font-size: 14px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #FFD700; color: #8B4513; }
        .btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,215,0,0.3); }
        .btn:hover { transform: translateY(-2px); }
        
        .footer { text-align: center; padding: 15px; border-top: 1px solid rgba(255,215,0,0.3); font-size: 12px; }
        
        /* Age info */
        .age-info {
            background: rgba(52, 152, 219, 0.2);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-logo">
                <img src="images/icon.png" alt="Logo">
            </div>
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">የአባል መረጃ ማስተካከያ</div>
        </div>

        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="view.php?id=<?php echo $member['member_id_number']; ?>">ተመልከት</a>
        </div>

        <div class="form-container">
            <div class="form-title">አርትዕ: <?php echo htmlspecialchars($member['full_name']); ?></div>
            
            <form method="POST">
                <div class="photo-section">
                    <img src="<?php echo !empty($member['photo']) ? 'uploads/student_photos/'.$member['photo'] : 'images/icon.png'; ?>" class="current-photo">
                    <div>
                        <p>የአሁኑ ፎቶ</p>
                        <a href="photo_upload.php?id=<?php echo $member['id']; ?>" class="btn btn-primary btn-sm">ፎቶ ለውጥ</a>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- NEW: Category Section -->
                    <div class="category-section">
                        <div class="section-title">👥 ምድብ / Category</div>
                        <div class="age-info">
                            📅 ዕድሜ: <strong><?php echo $age; ?> ዓመት</strong>
                            <?php if($age >= 13 && $member['category'] == 'ህጻናት'): ?>
                            <span style="color: #f39c12; margin-left: 15px;">⚠️ ወደ ወጣቶች ለማሻሻል ዝግጁ</span>
                            <?php endif; ?>
                        </div>
                        <div class="category-options">
                            <label class="category-option children <?php echo $member['category'] == 'ህጻናት' ? 'selected' : ''; ?>">
                                <input type="radio" name="category" value="ህጻናት" <?php echo $member['category'] == 'ህጻናት' ? 'checked' : ''; ?> onclick="toggleFields('children')">
                                <span style="font-size: 18px;">🧒 ህጻናት (Children)</span>
                            </label>
                            <label class="category-option youth <?php echo $member['category'] == 'ወጣቶች' ? 'selected' : ''; ?>">
                                <input type="radio" name="category" value="ወጣቶች" <?php echo $member['category'] == 'ወጣቶች' ? 'checked' : ''; ?> onclick="toggleFields('youth')">
                                <span style="font-size: 18px;">👦 ወጣቶች (Youth)</span>
                            </label>
                        </div>
                        <div style="font-size: 12px; margin-top: 10px; color: rgba(255,255,255,0.7);">
                            <span style="color: #3498db;">🧒 ህጻናት</span> - አንዳንድ መረጃዎች አይታዩም<br>
                            <span style="color: #2ecc71;">👦 ወጣቶች</span> - ሁሉም መረጃዎች ይታያሉ
                        </div>
                    </div>

                    <!-- Basic Info -->
                    <div class="form-section">
                        <div class="section-title">መሠረታዊ መረጃ</div>
                        <div class="form-group">
                            <label>ሙሉ ስም</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>ክርስትና ስም</label>
                            <input type="text" name="christian_name" class="form-control" value="<?php echo htmlspecialchars($member['christian_name'] ?? ''); ?>">
                        </div>
                        <!-- Phone field - youth only -->
                        <div class="form-group youth-only-field" id="phoneField" <?php echo $member['category'] == 'ህጻናት' ? 'style="display:none;"' : ''; ?>>
                            <label>ስልክ ቁጥር</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>">
                            <div class="field-note">ለወጣቶች ብቻ</div>
                        </div>
                        <div class="form-group">
                            <label>የትውልድ ቀን</label>
                            <input type="date" name="birth_date" class="form-control" value="<?php echo $member['birth_date'] ?? ''; ?>" id="birthDate">
                        </div>
                        <div class="form-group">
                            <label>ጾታ</label>
                            <select name="gender" class="form-control" required>
                                <option value="">ይምረጡ</option>
                                <option value="ወ" <?php echo $member['gender']=='ወ' ? 'selected' : ''; ?>>ወንድ</option>
                                <option value="ሴ" <?php echo $member['gender']=='ሴ' ? 'selected' : ''; ?>>ሴት</option>
                            </select>
                        </div>
                    </div>

                    <!-- Emergency Section -->
                    <div class="form-section">
                        <div class="section-title">🆘 የአደጋ ጊዜ</div>
                        <div class="form-group">
                            <label>የአደጋ ጊዜ ተጠሪ</label>
                            <input type="text" name="emergency_name" class="form-control" value="<?php echo htmlspecialchars($member['emergency_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>ስልክ ቁጥር</label>
                            <input type="text" name="emergency_phone" class="form-control" value="<?php echo htmlspecialchars($member['emergency_phone'] ?? ''); ?>">
                        </div>
                        <!-- Confession fields - youth only -->
                        <div class="youth-only-field" id="confessionFields" <?php echo $member['category'] == 'ህጻናት' ? 'style="display:none;"' : ''; ?>>
                            <div style="margin-top: 15px; border-top: 1px dashed rgba(255,215,0,0.3); padding-top: 15px;">
                                <div style="color: #FFD700; margin-bottom: 10px;">📿 ንስሐ (ለወጣቶች ብቻ)</div>
                                <div class="form-group">
                                    <label>የንስሐ አባት</label>
                                    <input type="text" name="confession_father" class="form-control" value="<?php echo htmlspecialchars($member['confession_father'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>ስልክ ቁጥር</label>
                                    <input type="text" name="confession_phone" class="form-control" value="<?php echo htmlspecialchars($member['confession_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registration -->
                    <div class="form-section">
                        <div class="section-title">ምዝገባ</div>
                        <div class="form-group">
                            <label>የተመዘገበበት ቀን</label>
                            <input type="date" name="registration_date" class="form-control" value="<?php echo $member['registration_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>መሸኛ</label>
                            <select name="meshena" class="form-control">
                                <option value="">ይምረጡ</option>
                                <option value="አለ" <?php echo ($member['meshena']??'')=='አለ' ? 'selected' : ''; ?>>አለ</option>
                                <option value="የለም" <?php echo ($member['meshena']??'')=='የለም' ? 'selected' : ''; ?>>የለም</option>
                            </select>
                        </div>
                        <!-- Youth-only fields -->
                        <div class="youth-only-field" id="youthRegistrationFields" <?php echo $member['category'] == 'ህጻናት' ? 'style="display:none;"' : ''; ?>>
                            <div class="form-group">
                                <label>የትዳር ሁኔታ</label>
                                <select name="marital_status" class="form-control">
                                    <option value="">ይምረጡ</option>
                                    <option value="ያገባ" <?php echo ($member['marital_status']??'')=='ያገባ' ? 'selected' : ''; ?>>ያገባ</option>
                                    <option value="ያላገባ" <?php echo ($member['marital_status']??'')=='ያላገባ' ? 'selected' : ''; ?>>ያላገባ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>የአባልነት አይነት</label>
                                <select name="member_type" class="form-control">
                                    <option value="">ይምረጡ</option>
                                    <option value="እጩ" <?php echo ($member['member_type']??'')=='እጩ' ? 'selected' : ''; ?>>እጩ</option>
                                    <option value="መደበኛ" <?php echo ($member['member_type']??'')=='መደበኛ' ? 'selected' : ''; ?>>መደበኛ</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Education & Location -->
                    <div class="form-section">
                        <div class="section-title">📍 ትምህርትና አካባቢ</div>
                        <div class="form-group">
                            <label>የትምህርት ደረጃ</label>
                            <input type="text" name="education_level" class="form-control" value="<?php echo htmlspecialchars($member['education_level'] ?? ''); ?>">
                        </div>
                        <!-- Profession - youth only -->
                        <div class="form-group youth-only-field" id="professionField" <?php echo $member['category'] == 'ህጻናት' ? 'style="display:none;"' : ''; ?>>
                            <label>የሙያ መስክ</label>
                            <input type="text" name="profession" class="form-control" value="<?php echo htmlspecialchars($member['profession'] ?? ''); ?>">
                            <div class="field-note">ለወጣቶች ብቻ</div>
                        </div>
                        <!-- Location fields (labels change based on category) -->
                        <div class="form-group">
                            <label id="subcityLabel"><?php echo ($member['category'] == 'ህጻናት') ? 'የአደጋ ጊዜ ክፍለ ከተማ' : 'ክፍለ ከተማ'; ?></label>
                            <input type="text" name="subcity" class="form-control" value="<?php echo htmlspecialchars($member['subcity'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label id="woredaLabel"><?php echo ($member['category'] == 'ህጻናት') ? 'የአደጋ ጊዜ ወረዳ' : 'ወረዳ'; ?></label>
                            <input type="text" name="woreda" class="form-control" value="<?php echo htmlspecialchars($member['woreda'] ?? ''); ?>">
                        </div>
                        <!-- Church rank - youth only -->
                        <div class="form-group youth-only-field" id="churchRankField" <?php echo $member['category'] == 'ህጻናት' ? 'style="display:none;"' : ''; ?>>
                            <label>የክህነት ደረጃ</label>
                            <input type="text" name="church_rank" class="form-control" value="<?php echo htmlspecialchars($member['church_rank'] ?? ''); ?>">
                            <div class="field-note">ለወጣቶች ብቻ</div>
                        </div>
                    </div>

                    <!-- Church & Status -->
                    <div class="form-section">
                        <div class="section-title">ቤተክርስቲያን እና ሁኔታ</div>
                        <div class="form-group">
                            <label>የመታወቂያ ቁጥር</label>
                            <input type="text" name="member_id_number" class="form-control" value="<?php echo htmlspecialchars($member['member_id_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>ዓመት</label>
                            <select name="year" class="form-control" required>
                                <?php for($y=2010; $y<=2018; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $member['year']==$y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ሁኔታ</label>
                            <select name="status" class="form-control">
                                <option value="temporary" <?php echo $member['status']=='temporary' ? 'selected' : ''; ?>>ጊዜያዊ</option>
                                <option value="approved" <?php echo $member['status']=='approved' ? 'selected' : ''; ?>>የተረጋገጠ</option>
                            </select>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="form-section" style="grid-column:1/-1;">
                        <div class="section-title">ማስታወሻ</div>
                        <textarea name="remarks" class="form-control" rows="3"><?php echo htmlspecialchars($member['remarks'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="view.php?id=<?php echo $member['member_id_number']; ?>" class="btn btn-secondary">ተመለስ</a>
                    <button type="submit" name="update" class="btn btn-primary">አስቀምጥ</button>
                </div>
            </form>
        </div>

        <div class="footer">© <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት</div>
    </div>

    <script>
        function toggleFields(category) {
            const youthFields = document.querySelectorAll('.youth-only-field');
            
            if(category === 'children') {
                // Hide youth-only fields
                youthFields.forEach(field => {
                    field.style.display = 'none';
                });
                
                // Change labels for children
                document.getElementById('subcityLabel').innerHTML = 'የአደጋ ጊዜ ክፍለ ከተማ';
                document.getElementById('woredaLabel').innerHTML = 'የአደጋ ጊዜ ወረዳ';
                
            } else {
                // Show youth-only fields
                youthFields.forEach(field => {
                    field.style.display = 'block';
                });
                
                // Restore labels for youth
                document.getElementById('subcityLabel').innerHTML = 'ክፍለ ከተማ';
                document.getElementById('woredaLabel').innerHTML = 'ወረዳ';
            }
        }
        
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
        
        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                if(msg) msg.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>