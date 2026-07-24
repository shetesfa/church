<?php
// view.php - Single member view with category display and upgrade option
require_once 'config.php';

// Get member by ID or ATS ID
$id_param = $_GET['id'] ?? '';

if(empty($id_param)) {
    header("Location: members.php");
    exit();
}

// Check if it's ATS ID or numeric ID
if(strpos($id_param, 'ATS') === 0) {
    $query = "SELECT * FROM members WHERE member_id_number = '$id_param'";
} else {
    $query = "SELECT * FROM members WHERE id = " . intval($id_param);
}

$result = $conn->query($query);
if($result->num_rows == 0) {
    header("Location: members.php");
    exit();
}
$member = $result->fetch_assoc();

// Get missing fields
$missing_fields = getMissingFields($member);
$missing_count = count($missing_fields);

// Calculate age
$age = calculateAge($member['birth_date']);

// Get all years for this member (history)
$history = $conn->query("SELECT year, member_type, marital_status, status, category FROM members WHERE full_name = '{$member['full_name']}' AND phone = '{$member['phone']}' ORDER BY year DESC");

$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የአባል መረጃ | Member Profile</title>
    <style>
        /* ============================================================
           CERTIFICATE STYLES - View Profile
           ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Ethiopic', 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #5D3A1A, #6B2E2E, #8B6913);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: linear-gradient(135deg, #FDB931, #F39C12, #C0392B);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px double rgba(255,255,255,0.3);
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
            cursor: pointer;
            transition: transform 0.3s;
        }

        .church-logo:hover {
            transform: scale(1.05);
        }

        .church-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header h1 {
            font-size: 36px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .header .amharic {
            font-size: 32px;
            color: #FFD700;
            border-bottom: 2px solid #FFD700;
            display: inline-block;
            padding-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Navigation */
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 10px 20px;
            background: rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            background: rgba(255,255,255,0.15);
            transition: all 0.2s;
            border: 1px solid rgba(255,215,0,0.3);
            display: inline-block;
        }

        .nav a:hover {
            background: #FFD700;
            color: #5D3A1A;
            transform: translateY(-2px);
        }

        /* Category Badge Styles */
        .category-badge-large {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 40px;
            font-size: 18px;
            font-weight: bold;
            margin-left: 15px;
        }

        .badge-children {
            background: #3498db;
            color: white;
        }

        .badge-youth {
            background: #2ecc71;
            color: white;
        }

        /* Age Badge */
        .age-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }

        /* Missing Fields Warning Banner */
        .warning-banner {
            margin: 15px 20px;
            padding: 15px;
            background: rgba(243, 156, 18, 0.2);
            border-left: 4px solid #f39c12;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .warning-icon {
            width: 40px;
            height: 40px;
            background: #f39c12;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .warning-text {
            flex: 1;
        }

        .warning-title {
            font-size: 18px;
            color: #f39c12;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .warning-list {
            color: white;
            font-size: 14px;
        }

        .warning-list span {
            background: rgba(255,255,255,0.15);
            padding: 3px 10px;
            border-radius: 15px;
            margin-right: 5px;
            display: inline-block;
            margin-bottom: 5px;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            gap: 30px;
            padding: 30px;
            align-items: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .profile-photo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 5px solid #FFD700;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .profile-photo:hover {
            transform: scale(1.05);
        }

        .profile-info {
            flex: 1;
        }

        .member-name {
            font-size: 36px;
            color: #FFD700;
            font-weight: bold;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .member-christian {
            font-size: 20px;
            color: white;
            margin-bottom: 10px;
        }

        .member-id-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 25px;
            font-family: monospace;
            font-size: 16px;
            color: #FFD700;
            border: 1px solid #FFD700;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }

        .status-approved {
            background: rgba(39, 174, 96, 0.3);
            color: #2ecc71;
            border: 1px solid #27ae60;
        }

        .status-temporary {
            background: rgba(241, 196, 15, 0.3);
            color: #f1c40f;
            border: 1px solid #f39c12;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 30px;
            position: relative;
            z-index: 2;
        }

        .info-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid #FFD700;
        }

        .card-title {
            font-size: 18px;
            color: #FFD700;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(255,215,0,0.3);
            padding-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(255,215,0,0.2);
        }

        .info-label {
            width: 40%;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }

        .info-value {
            width: 60%;
            color: white;
            font-size: 14px;
            font-weight: 500;
        }

        /* Missing field highlight */
        .info-value.missing {
            color: #f39c12;
            position: relative;
        }

        .info-value.missing::after {
            content: '⚠️';
            margin-left: 5px;
            font-size: 12px;
        }

        .missing-tag {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(243, 156, 18, 0.2);
            border-radius: 12px;
            color: #f39c12;
            font-size: 11px;
            margin-left: 5px;
        }

        /* Upgrade Section */
        .upgrade-section {
            margin: 20px 30px;
            padding: 20px;
            background: rgba(52, 152, 219, 0.2);
            border-left: 4px solid #3498db;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .upgrade-info {
            flex: 1;
        }

        .upgrade-title {
            font-size: 18px;
            color: #3498db;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .upgrade-desc {
            color: white;
            font-size: 14px;
        }

        .btn-upgrade {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-upgrade:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* History Section */
        .history-section {
            padding: 20px 30px;
            position: relative;
            z-index: 2;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .history-table th {
            background: rgba(0,0,0,0.3);
            color: #FFD700;
            padding: 10px;
            font-size: 13px;
        }

        .history-table td {
            padding: 8px 10px;
            border-bottom: 1px solid rgba(255,215,0,0.2);
            color: white;
            font-size: 13px;
            text-align: center;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            padding: 20px 30px 30px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #FFD700;
            color: #5D3A1A;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-print {
            background: #27ae60;
            color: white;
        }

        .btn-photo {
            background: #9b59b6;
            color: white;
        }

        .btn-approve {
            background: #2ecc71;
            color: white;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-back {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,215,0,0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* Photo Modal */
        .photo-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .photo-modal img {
            max-width: 90%;
            max-height: 90%;
            border: 5px solid #FFD700;
            border-radius: 10px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
        }

        .footer {
            text-align: center;
            padding: 15px;
            border-top: 1px solid rgba(255,215,0,0.2);
            color: white;
            background: rgba(0,0,0,0.2);
            font-size: 12px;
            position: relative;
            z-index: 2;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label, .info-value {
                width: 100%;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .member-name {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="header">
            <div class="church-logo" onclick="window.location.href='index.php'">
                <img src="images/icon.png" alt="Church Logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'45\' fill=\'%23FFD700\'/%3E%3Ctext x=\'50\' y=\'70\' font-size=\'50\' text-anchor=\'middle\' fill=\'%238B4513\'%3E⛪%3C/text%3E%3C/svg%3E';">
            </div>
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">የአባል መረጃ</div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="temporary.php">ጊዜያዊ</a>
            <a href="register.php">አዲስ መዝግብ</a>
            <a href="birthdays.php">ልደት</a>
            <a href="statistics.php">ስታቲስቲክስ</a>
            <?php if($member['status'] == 'temporary'): ?>
            <a href="temporary.php">⏳ ወደ ጊዜያዊ</a>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if($message): ?>
        <div style="margin:15px 20px; padding:10px; background:<?php echo $msg_type=='success'?'rgba(39,174,96,0.3)':'rgba(192,57,43,0.3)'; ?>; border-left:4px solid <?php echo $msg_type=='success'?'#27ae60':'#c0392b'; ?>; color:white;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Missing Fields Warning Banner -->
        <?php if($missing_count > 0): ?>
        <div class="warning-banner">
            <div class="warning-icon">⚠️</div>
            <div class="warning-text">
                <div class="warning-title"><?php echo $missing_count; ?> የጎደሉ መረጃዎች አሉ</div>
                <div class="warning-list">
                    <?php foreach($missing_fields as $field): ?>
                    <span><?php echo $field; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-edit" style="padding: 8px 15px;">አርትዕ ያድርጉ</a>
        </div>
        <?php endif; ?>

        <!-- NEW: Upgrade Section for Children -->
        <?php if($member['category'] == 'ህጻናት'): ?>
        <div class="upgrade-section">
            <div class="upgrade-info">
                <div class="upgrade-title">⬆️ ወደ ወጣቶች ማሻሻል</div>
                <div class="upgrade-desc">
                    ይህ አባል በአሁኑ ጊዜ በህጻናት ምድብ ውስጥ ነው። 
                    ወደ ወጣቶች ማሻሻል ከፈለጉ ከታች ያለውን ቁልፍ ይጫኑ።
                    <?php if($age >= 13): ?>
                    <span style="color: #f39c12; display: block; margin-top: 5px;">
                        ⚠️ ዕድሜው <?php echo $age; ?> ነው። ለማሻሻል ዝግጁ ነው።
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="upgrade.php?id=<?php echo $member['id']; ?>" class="btn-upgrade" onclick="return confirm('ወደ ወጣቶች ማሻሻል እርግጠኛ ነህ?')">
                ⬆️ ወደ ወጣቶች አሻሽል
            </a>
        </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <img src="<?php echo !empty($member['photo']) ? 'uploads/student_photos/'.$member['photo'] : 'images/icon.png'; ?>" 
                 class="profile-photo" 
                 onclick="showPhoto('<?php echo !empty($member['photo']) ? $member['photo'] : ''; ?>')">
            
            <div class="profile-info">
                <div class="member-name">
                    <?php echo htmlspecialchars($member['full_name']); ?>
                    <!-- NEW: Category Badge -->
                    <?php 
                    if($member['category'] == 'ህጻናት') {
                        echo '<span class="category-badge-large badge-children">🧒 ህጻናት</span>';
                    } else {
                        echo '<span class="category-badge-large badge-youth">👦 ወጣቶች</span>';
                    }
                    ?>
                    <!-- Age Badge -->
                    <span class="age-badge">🎂 <?php echo $age; ?> ዓመት</span>
                    <span class="status-badge <?php echo $member['status'] == 'approved' ? 'status-approved' : 'status-temporary'; ?>">
                        <?php echo $member['status'] == 'approved' ? 'የተረጋገጠ' : 'ጊዜያዊ'; ?>
                    </span>
                </div>
                
                <div class="member-christian">
                    ⛪ <?php echo htmlspecialchars($member['christian_name'] ?? 'ክርስትና ስም የለም'); ?>
                </div>
                
                <div class="member-id-badge">
                    <?php echo $member['member_id_number'] ?? 'ATS'.str_pad($member['id'],5,'0',STR_PAD_LEFT); ?>
                </div>
            </div>
        </div>

        <!-- Information Grid -->
        <div class="info-grid">
            <!-- Personal Info -->
            <div class="info-card">
                <div class="card-title">
                    <span>👤</span> የግል መረጃ
                </div>
                
                <div class="info-row">
                    <span class="info-label">መታወቂያ ቁጥር:</span>
                    <span class="info-value"><?php echo $member['id']; ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">ሙሉ ስም:</span>
                    <span class="info-value <?php echo empty($member['full_name']) ? 'missing' : ''; ?>">
                        <?php echo htmlspecialchars($member['full_name']); ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">ክርስትና ስም:</span>
                    <span class="info-value <?php echo empty($member['christian_name']) ? 'missing' : ''; ?>">
                        <?php echo htmlspecialchars($member['christian_name'] ?? '---'); ?>
                        <?php if(empty($member['christian_name'])): ?>
                        <span class="missing-tag">የጎደለ</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <!-- Phone - show only for youth or if exists -->
                <?php if($member['category'] == 'ወጣቶች' || !empty($member['phone'])): ?>
                <div class="info-row">
                    <span class="info-label">ስልክ ቁጥር:</span>
                    <span class="info-value <?php echo empty($member['phone']) ? 'missing' : ''; ?>">
                        <?php echo htmlspecialchars($member['phone'] ?? '---'); ?>
                        <?php if(empty($member['phone']) && $member['category'] == 'ወጣቶች'): ?>
                        <span class="missing-tag">የጎደለ</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">የትውልድ ቀን:</span>
                    <span class="info-value <?php echo empty($member['birth_date']) ? 'missing' : ''; ?>">
                        <?php echo !empty($member['birth_date']) ? date('d/m/Y', strtotime($member['birth_date'])) : '---'; ?>
                        <?php if(!empty($member['birth_date'])): ?>
                        <span style="color: #f39c12; margin-left: 5px;">(ዕድሜ: <?php echo $age; ?>)</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">ጾታ:</span>
                    <span class="info-value <?php echo empty($member['gender']) ? 'missing' : ''; ?>">
                        <?php 
                        $gender = $member['gender'] ?? '';
                        if($gender == 'ወ' || $gender == 'Male') echo 'ወንድ';
                        elseif($gender == 'ሴ' || $gender == 'Female') echo 'ሴት';
                        else echo '---';
                        ?>
                        <?php if(empty($member['gender'])): ?>
                        <span class="missing-tag">የጎደለ</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Emergency Section -->
            <div class="info-card">
                <div class="card-title">
                    <span>🆘</span> የአደጋ ጊዜ
                </div>
                
                <div class="info-row">
                    <span class="info-label">የአደጋ ጊዜ ተጠሪ:</span>
                    <span class="info-value <?php echo empty($member['emergency_name']) ? 'missing' : ''; ?>">
                        <?php echo htmlspecialchars($member['emergency_name'] ?? '---'); ?>
                        <?php if(empty($member['emergency_name'])): ?>
                        <span class="missing-tag">የጎደለ</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">ስልክ ቁጥር:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['emergency_phone'] ?? '---'); ?>
                    </span>
                </div>
                
                <!-- Location fields (for children, these are emergency location) -->
                <div class="info-row">
                    <span class="info-label"><?php echo ($member['category'] == 'ህጻናት') ? 'የአደጋ ጊዜ ክፍለ ከተማ' : 'ክፍለ ከተማ'; ?>:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['subcity'] ?? '---'); ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label"><?php echo ($member['category'] == 'ህጻናት') ? 'የአደጋ ጊዜ ወረዳ' : 'ወረዳ'; ?>:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['woreda'] ?? '---'); ?>
                    </span>
                </div>
            </div>

            <!-- Registration & Status - Show youth-only fields conditionally -->
            <div class="info-card">
                <div class="card-title">
                    <span>📋</span> ምዝገባ እና ሁኔታ
                </div>
                
                <div class="info-row">
                    <span class="info-label">የተመዘገበበት ቀን:</span>
                    <span class="info-value">
                        <?php echo !empty($member['registration_date']) ? date('d/m/Y', strtotime($member['registration_date'])) : '---'; ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">ዓመተ ምህረት:</span>
                    <span class="info-value">
                        <?php echo $member['year']; ?>
                    </span>
                </div>
                
                <!-- Youth-only fields -->
                <?php if($member['category'] == 'ወጣቶች'): ?>
                <div class="info-row">
                    <span class="info-label">የአባልነት አይነት:</span>
                    <span class="info-value <?php echo empty($member['member_type']) ? 'missing' : ''; ?>">
                        <?php echo $member['member_type'] ?? '---'; ?>
                        <?php if(empty($member['member_type'])): ?>
                        <span class="missing-tag">የጎደለ</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">የትዳር ሁኔታ:</span>
                    <span class="info-value <?php echo empty($member['marital_status']) ? 'missing' : ''; ?>">
                        <?php echo $member['marital_status'] ?? '---'; ?>
                        <?php if(empty($member['marital_status'])): ?>
                        <span class="missing-tag">የጎደለ</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">የክህነት ደረጃ:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['church_rank'] ?? '---'); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">መሸኛ:</span>
                    <span class="info-value">
                        <?php echo $member['meshena'] ?? '---'; ?>
                    </span>
                </div>
            </div>

            <!-- Education & Work -->
            <div class="info-card">
                <div class="card-title">
                    <span>🎓</span> ትምህርት
                </div>
                
                <div class="info-row">
                    <span class="info-label">የትምህርት ደረጃ:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['education_level'] ?? '---'); ?>
                    </span>
                </div>
                
                <!-- Profession - youth only -->
                <?php if($member['category'] == 'ወጣቶች'): ?>
                <div class="info-row">
                    <span class="info-label">የሙያ መስክ:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['profession'] ?? '---'); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <!-- Confession fields - youth only -->
                <?php if($member['category'] == 'ወጣቶች'): ?>
                <div class="info-row">
                    <span class="info-label">የንስሐ አባት:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['confession_father'] ?? '---'); ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">ስልክ ቁጥር:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($member['confession_phone'] ?? '---'); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Section (if multiple years) -->
        <?php if($history->num_rows > 1): ?>
        <div class="history-section">
            <div class="card-title" style="margin-bottom: 15px;">
                <span>📅</span> የአባልነት ታሪክ
            </div>
            
            <table class="history-table">
                <thead>
                    <tr>
                        <th>ዓመተ ምህረት</th>
                        <th>ምድብ</th>
                        <th>የአባልነት አይነት</th>
                        <th>የትዳር ሁኔታ</th>
                        <th>ሁኔታ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($h = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $h['year']; ?></td>
                        <td>
                            <?php 
                            if($h['category'] == 'ህጻናት') {
                                echo '<span style="color: #3498db;">ህጻናት</span>';
                            } else {
                                echo '<span style="color: #2ecc71;">ወጣቶች</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo $h['member_type'] ?? '---'; ?></td>
                        <td><?php echo $h['marital_status'] ?? '---'; ?></td>
                        <td>
                            <?php if($h['status'] == 'approved'): ?>
                            <span style="color: #2ecc71;">የተረጋገጠ</span>
                            <?php else: ?>
                            <span style="color: #f39c12;">ጊዜያዊ</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Remarks Section -->
        <?php if(!empty($member['remarks'])): ?>
        <div style="margin: 0 30px 20px; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 10px;">
            <strong style="color: #FFD700;">ማስታወሻ:</strong>
            <p style="color: white; margin-top: 5px;"><?php echo nl2br(htmlspecialchars($member['remarks'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-edit">
                ✏️ አርትዕ
            </a>
            
            <a href="print.php?id=<?php echo $member['id']; ?>" class="btn btn-print" target="_blank">
                🖨️ አትም
            </a>
            
            <a href="photo_upload.php?id=<?php echo $member['id']; ?>" class="btn btn-photo">
                📸 ፎቶ ለውጥ
            </a>
            
            <?php if($member['status'] == 'temporary'): ?>
            <a href="temporary.php?approve=<?php echo $member['id']; ?>" class="btn btn-approve" onclick="return confirm('ማጽደቅ እርግጠኛ ነህ?')">
                ✅ አጽድቅ
            </a>
            <?php endif; ?>
            
            <!-- Upgrade button for children -->
            <?php if($member['category'] == 'ህጻናት'): ?>
            <a href="upgrade.php?id=<?php echo $member['id']; ?>" class="btn btn-primary" style="background: #3498db; color: white;" onclick="return confirm('ወደ ወጣቶች ማሻሻል እርግጠኛ ነህ?')">
                ⬆️ ወደ ወጣቶች አሻሽል
            </a>
            <?php endif; ?>
            
            <a href="delete.php?id=<?php echo $member['id']; ?>" class="btn btn-delete" onclick="return confirm('መሰረዝ እርግጠኛ ነህ? ይህ ዘላቂ ነው!')">
                🗑️ ሰርዝ
            </a>
            
            <a href="members.php?year=<?php echo $member['year']; ?>&category=<?php echo urlencode($member['category']); ?>" class="btn btn-back">
                🔙 ተመለስ
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት | የተመለከተበት ቀን: <?php echo date('d/m/Y'); ?>
        </div>
    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
        <span class="close-modal" onclick="closePhotoModal()">&times;</span>
        <img id="modalPhoto" src="" alt="Member Photo">
    </div>

    <script>
        // Photo modal function
        function showPhoto(photoSrc) {
            const modal = document.getElementById('photoModal');
            const modalImg = document.getElementById('modalPhoto');
            
            if(photoSrc) {
                modalImg.src = 'uploads/student_photos/' + photoSrc;
            } else {
                modalImg.src = 'images/icon.png';
            }
            
            modal.style.display = 'flex';
        }

        function closePhotoModal() {
            document.getElementById('photoModal').style.display = 'none';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                closePhotoModal();
            }
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