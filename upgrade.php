<?php
// upgrade.php - Manually upgrade a child to youth category
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id) {
    $_SESSION['message'] = "የአባል መታወቂያ አልተገኘም!";
    $_SESSION['msg_type'] = "error";
    header("Location: members.php");
    exit();
}

// Get member details
$result = $conn->query("SELECT * FROM members WHERE id = $id");
if($result->num_rows == 0) {
    $_SESSION['message'] = "አባል አልተገኘም!";
    $_SESSION['msg_type'] = "error";
    header("Location: members.php");
    exit();
}

$member = $result->fetch_assoc();

// Check if already in youth category
if($member['category'] == 'ወጣቶች') {
    $_SESSION['message'] = "ይህ አባል ቀድሞውኑ በወጣቶች ምድብ ውስጥ ነው!";
    $_SESSION['msg_type'] = "error";
    header("Location: view.php?id=" . $member['member_id_number']);
    exit();
}

// Handle upgrade confirmation
if(isset($_POST['confirm_upgrade'])) {
    $today = date('Y-m-d H:i:s');
    $age = calculateAge($member['birth_date']);
    $remarks = $member['remarks'] . "\n[" . date('Y-m-d') . "] ከህጻናት ወደ ወጣቶች ተሻሽሏል (ዕድሜ: $age)";
    
    $update = "UPDATE members SET 
               category = 'ወጣቶች',
               previous_category = 'ህጻናት',
               upgrade_date = NOW(),
               remarks = ?
               WHERE id = ?";
    
    $stmt = $conn->prepare($update);
    $stmt->bind_param("si", $remarks, $id);
    
    if($stmt->execute()) {
        $_SESSION['message'] = "አባል በተሳካ ሁኔታ ወደ ወጣቶች ተሻሽሏል!";
        $_SESSION['msg_type'] = "success";
        
        // Log the upgrade
        $log = "INSERT INTO category_transfers (member_id, from_category, to_category, transfer_date, age) 
                VALUES ($id, 'ህጻናት', 'ወጣቶች', NOW(), $age)";
        $conn->query($log);
        
        header("Location: view.php?id=" . $member['member_id_number']);
        exit();
    } else {
        $error = "ስህተት ተከስቷል: " . $conn->error;
    }
    $stmt->close();
}

// Get age
$age = calculateAge($member['birth_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ወደ ወጣቶች ማሻሻል | Upgrade to Youth</title>
    <style>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 600px;
            width: 100%;
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
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            cursor: pointer;
        }

        .church-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header h1 {
            font-size: 24px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .header .amharic {
            font-size: 22px;
            color: #FFD700;
            border-bottom: 2px solid #FFD700;
            display: inline-block;
            padding-bottom: 5px;
        }

        .upgrade-container {
            padding: 20px 30px 30px;
            position: relative;
            z-index: 2;
        }

        .member-info {
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .member-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #FFD700;
            object-fit: cover;
        }

        .member-details {
            flex: 1;
        }

        .member-name {
            font-size: 22px;
            color: #FFD700;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .member-id {
            font-family: monospace;
            color: white;
            margin-bottom: 5px;
        }

        .member-age {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(52, 152, 219, 0.3);
            border-radius: 20px;
            color: white;
            font-size: 14px;
        }

        .upgrade-box {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 2px dashed #FFD700;
        }

        .upgrade-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .upgrade-title {
            font-size: 24px;
            color: #FFD700;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .upgrade-message {
            color: white;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .warning-message {
            background: rgba(241, 196, 15, 0.2);
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            color: white;
            border-radius: 5px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-upgrade {
            background: #3498db;
            color: white;
        }

        .btn-upgrade:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .btn-cancel {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,215,0,0.3);
        }

        .btn-cancel:hover {
            background: #e74c3c;
            border-color: #e74c3c;
            transform: translateY(-2px);
        }

        .footer {
            text-align: center;
            padding: 15px;
            border-top: 1px solid rgba(255,215,0,0.2);
            color: white;
            background: rgba(0,0,0,0.2);
            font-size: 12px;
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
            <div class="amharic">ወደ ወጣቶች ማሻሻል</div>
        </div>

        <div class="upgrade-container">
            <div class="member-info">
                <img src="<?php echo !empty($member['photo']) ? 'uploads/student_photos/'.$member['photo'] : 'images/icon.png'; ?>" class="member-photo">
                <div class="member-details">
                    <div class="member-name"><?php echo htmlspecialchars($member['full_name']); ?></div>
                    <div class="member-id"><?php echo $member['member_id_number'] ?? 'ATS'.str_pad($member['id'],5,'0',STR_PAD_LEFT); ?></div>
                    <div>
                        <span class="member-age">🎂 ዕድሜ: <?php echo $age; ?> ዓመት</span>
                        <span style="margin-left: 10px; color: #3498db;">🧒 ህጻናት</span>
                    </div>
                </div>
            </div>

            <div class="upgrade-box">
                <div class="upgrade-icon">⬆️</div>
                <div class="upgrade-title">ወደ ወጣቶች ማሻሻል</div>
                
                <div class="upgrade-message">
                    ይህን አባል ከህጻናት ምድብ ወደ ወጣቶች ምድብ ልታሻሽሉ ነው።<br>
                    ከተሻሻለ በኋላ ሁሉም መረጃዎች ይቆያሉ።
                </div>

                <?php if($age >= 13): ?>
                <div class="warning-message">
                    ⚠️ የዚህ አባል ዕድሜ <?php echo $age; ?> ነው። ለማሻሻል ዝግጁ ነው።
                </div>
                <?php else: ?>
                <div class="warning-message" style="border-left-color: #e74c3c;">
                    ⚠️ የዚህ አባል ዕድሜ <?php echo $age; ?> ብቻ ነው። ማሻሻል የሚመከር አይደለም።
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="button-group">
                        <button type="submit" name="confirm_upgrade" class="btn btn-upgrade">
                            ✅ አረጋግጥ እና አሻሽል
                        </button>
                        <a href="view.php?id=<?php echo $member['member_id_number']; ?>" class="btn btn-cancel">
                            ❌ ተመለስ
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት
        </div>
    </div>
</body>
</html>