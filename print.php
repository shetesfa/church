<?php
// print.php - FULL A4 ONE PAGE version with category support
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

$missing_fields = getMissingFields($member);
$missing_count = count($missing_fields);
$age = calculateAge($member['birth_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የአባል መረጃ | Member Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Ethiopic', 'Times New Roman', Times, serif;
            background: #f0f0f0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* EXACT A4 SIZE */
        .print-container {
            max-width: 210mm;
            width: 100%;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* FULL A4 PAGE - EXACTLY 297mm HEIGHT */
        .a4-page {
            background: linear-gradient(135deg, #FDB931, #F39C12, #C0392B);
            color: white;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
            height: 297mm;
            display: flex;
            flex-direction: column;
        }

        /* Decorative Border */
        .certificate-border {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            bottom: 12px;
            border: 2px double rgba(255,255,255,0.4);
            border-radius: 10px;
            pointer-events: none;
        }

        /* Background Pattern */
        .certificate-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.05;
            background-image: radial-gradient(rgba(255,255,255,0.5) 1px, transparent 1px);
            background-size: 30px 30px;
            pointer-events: none;
        }

        .certificate-content {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* HEADER */
        .certificate-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .church-logo {
            width: 90px;
            height: 90px;
            margin: 0 auto 10px;
            border-radius: 50%;
            border: 3px solid white;
            overflow: hidden;
        }

        .church-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .church-name-main {
            font-size: 24px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3px;
        }

        .church-subname {
            font-size: 18px;
            color: #FFD700;
            border-bottom: 1px solid #FFD700;
            display: inline-block;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .print-title {
            font-size: 20px;
            color: white;
            font-weight: 300;
        }

        /* Category Badge */
        .category-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 16px;
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

        /* WARNING SECTION */
        .warning-section {
            background: rgba(243, 156, 18, 0.15);
            border-left: 4px solid #f39c12;
            padding: 8px 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .warning-icon {
            background: #f39c12;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .warning-list span {
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        /* PROFILE HEADER */
        .profile-header {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #FFD700;
            object-fit: cover;
        }

        .member-name {
            font-size: 28px;
            color: #FFD700;
            font-weight: bold;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .member-christian {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .member-id-badge {
            display: inline-block;
            padding: 3px 12px;
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            font-family: monospace;
            font-size: 14px;
            color: #FFD700;
            border: 1px solid #FFD700;
        }

        .age-badge {
            display: inline-block;
            padding: 3px 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
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

        /* 2-COLUMN GRID */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            flex: 1;
        }

        .info-card {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            border-left: 3px solid #FFD700;
        }

        .card-title {
            font-size: 16px;
            color: #FFD700;
            margin-bottom: 12px;
            border-bottom: 1px solid rgba(255,215,0,0.3);
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            padding: 4px 0;
            border-bottom: 1px dashed rgba(255,215,0,0.2);
            font-size: 13px;
        }

        .info-label {
            width: 40%;
            color: rgba(255,255,255,0.8);
        }

        .info-value {
            width: 60%;
            color: white;
            font-weight: 500;
        }

        .info-value.missing {
            color: #f39c12;
        }

        .missing-tag {
            display: inline-block;
            padding: 1px 6px;
            background: rgba(243, 156, 18, 0.2);
            border-radius: 10px;
            color: #f39c12;
            font-size: 10px;
            margin-left: 5px;
        }

        /* REMARKS SECTION */
        .remarks-section {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .remarks-title {
            color: #FFD700;
            margin-bottom: 5px;
            font-size: 14px;
        }

        /* FOOTER WITH SIGNATURES */
        .certificate-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid rgba(255,215,0,0.3);
        }

        .signature-line {
            width: 180px;
            text-align: center;
        }

        .signature {
            border-bottom: 2px solid #FFD700;
            margin-bottom: 5px;
            height: 40px;
        }

        .signature-label {
            color: rgba(255,255,255,0.8);
            font-size: 12px;
        }

        .date {
            font-size: 14px;
            color: #FFD700;
            margin-bottom: 2px;
        }

        .date-label {
            font-size: 11px;
        }

        /* PRINT CONTROLS */
        .print-controls {
            text-align: center;
            margin: 20px 0;
        }

        .print-btn {
            padding: 10px 25px;
            background: #8B4513;
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }

        .print-btn:hover {
            background: #A52A2A;
        }

        /* PRINT OPTIMIZATION */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .print-container {
                box-shadow: none;
                max-width: 100%;
            }
            
            .a4-page {
                padding: 0.5in;
                height: 11in;
                page-break-after: avoid;
                page-break-inside: avoid;
                background: white;
                color: black;
            }
            
            .a4-page * {
                color: black;
            }
            
            .print-controls {
                display: none;
            }
            
            .certificate-border {
                border-color: #ccc;
            }
            
            .signature {
                border-bottom: 2px solid black;
            }
            
            @page {
                size: A4;
                margin: 0.5in;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button class="print-btn" onclick="window.print()">🖨️ አትም</button>
        <button class="print-btn" onclick="window.close()">❌ ዝጋ</button>
        <button class="print-btn" onclick="window.location.href='view.php?id=<?php echo $member['member_id_number']; ?>'">👁️ ተመለስ</button>
    </div>

    <div class="print-container">
        <!-- FULL A4 PAGE -->
        <div class="a4-page">
            <div class="certificate-border"></div>
            <div class="certificate-bg"></div>
            
            <div class="certificate-content">
                <!-- HEADER -->
                <div class="certificate-header">
                    <div class="church-logo">
                        <img src="images/icon.png" alt="Church Logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'90\' height=\'90\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'45\' fill=\'%23FFD700\'/%3E%3Ctext x=\'50\' y=\'70\' font-size=\'50\' text-anchor=\'middle\' fill=\'%238B4513\'%3E⛪%3C/text%3E%3C/svg%3E';">
                    </div>
                    <div class="church-name-main">አጸደ ትጉሃን ሰንበት ትምህርት ቤት</div>
                    <div class="church-subname">Atse Deteguhan Sunday School</div>
                    <div class="print-title">የአባል መረጃ ቅጽ</div>
                </div>

                <!-- MISSING FIELDS WARNING -->
                <?php if($missing_count > 0): ?>
                <div class="warning-section">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-text"><?php echo $missing_count; ?> የጎደሉ መረጃዎች አሉ</div>
                    <div class="warning-list">
                        <?php foreach($missing_fields as $field): ?>
                        <span><?php echo $field; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PROFILE HEADER -->
                <div class="profile-header">
                    <img src="<?php echo !empty($member['photo']) ? 'uploads/student_photos/'.$member['photo'] : 'images/icon.png'; ?>" 
                         class="profile-photo">
                    
                    <div class="profile-info">
                        <div class="member-name">
                            <?php echo htmlspecialchars($member['full_name']); ?>
                            <!-- Category Badge -->
                            <?php 
                            if($member['category'] == 'ህጻናት') {
                                echo '<span class="category-badge badge-children">🧒 ህጻናት</span>';
                            } else {
                                echo '<span class="category-badge badge-youth">👦 ወጣቶች</span>';
                            }
                            ?>
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

                <!-- 2-COLUMN GRID - ALL INFORMATION -->
                <div class="info-grid">
                    <!-- PERSONAL INFO -->
                    <div class="info-card">
                        <div class="card-title">👤 የግል መረጃ</div>
                        <div class="info-row"><span class="info-label">መታወቂያ ቁጥር:</span><span class="info-value"><?php echo $member['id']; ?></span></div>
                        <div class="info-row"><span class="info-label">ሙሉ ስም:</span><span class="info-value"><?php echo htmlspecialchars($member['full_name']); ?></span></div>
                        <div class="info-row"><span class="info-label">ክርስትና ስም:</span><span class="info-value"><?php echo htmlspecialchars($member['christian_name'] ?? '---'); ?></span></div>
                        
                        <!-- Phone - show only for youth or if exists -->
                        <?php if($member['category'] == 'ወጣቶች' || !empty($member['phone'])): ?>
                        <div class="info-row"><span class="info-label">ስልክ ቁጥር:</span><span class="info-value <?php echo empty($member['phone']) ? 'missing' : ''; ?>"><?php echo htmlspecialchars($member['phone'] ?? '---'); ?></span></div>
                        <?php endif; ?>
                        
                        <div class="info-row"><span class="info-label">የትውልድ ቀን:</span><span class="info-value"><?php echo !empty($member['birth_date']) ? date('d/m/Y', strtotime($member['birth_date'])) : '---'; ?></span></div>
                        <div class="info-row"><span class="info-label">ዕድሜ:</span><span class="info-value"><?php echo $age; ?> ዓመት</span></div>
                        <div class="info-row"><span class="info-label">ጾታ:</span><span class="info-value"><?php 
                            $g = $member['gender'] ?? '';
                            if($g == 'ወ' || $g == 'Male') echo 'ወንድ';
                            elseif($g == 'ሴ' || $g == 'Female') echo 'ሴት';
                            else echo '---';
                        ?></span></div>
                    </div>

                    <!-- EMERGENCY -->
                    <div class="info-card">
                        <div class="card-title">🆘 የአደጋ ጊዜ</div>
                        <div class="info-row"><span class="info-label">የአደጋ ጊዜ ተጠሪ:</span><span class="info-value"><?php echo htmlspecialchars($member['emergency_name'] ?? '---'); ?></span></div>
                        <div class="info-row"><span class="info-label">ስልክ ቁጥር:</span><span class="info-value"><?php echo htmlspecialchars($member['emergency_phone'] ?? '---'); ?></span></div>
                        
                        <!-- Location fields (for children, these are emergency location) -->
                        <div class="info-row"><span class="info-label"><?php echo ($member['category'] == 'ህጻናት') ? 'የአደጋ ጊዜ ክፍለ ከተማ' : 'ክፍለ ከተማ'; ?>:</span><span class="info-value"><?php echo htmlspecialchars($member['subcity'] ?? '---'); ?></span></div>
                        <div class="info-row"><span class="info-label"><?php echo ($member['category'] == 'ህጻናት') ? 'የአደጋ ጊዜ ወረዳ' : 'ወረዳ'; ?>:</span><span class="info-value"><?php echo htmlspecialchars($member['woreda'] ?? '---'); ?></span></div>
                        
                        <!-- Confession - youth only -->
                        <?php if($member['category'] == 'ወጣቶች'): ?>
                        <div class="info-row"><span class="info-label">የንስሐ አባት:</span><span class="info-value"><?php echo htmlspecialchars($member['confession_father'] ?? '---'); ?></span></div>
                        <div class="info-row"><span class="info-label">ስልክ ቁጥር:</span><span class="info-value"><?php echo htmlspecialchars($member['confession_phone'] ?? '---'); ?></span></div>
                        <?php endif; ?>
                        
                        <div class="info-row"><span class="info-label">መሸኛ:</span><span class="info-value"><?php echo $member['meshena'] ?? '---'; ?></span></div>
                    </div>

                    <!-- REGISTRATION & STATUS -->
                    <div class="info-card">
                        <div class="card-title">📋 ምዝገባ እና ሁኔታ</div>
                        <div class="info-row"><span class="info-label">የተመዘገበበት ቀን:</span><span class="info-value"><?php echo !empty($member['registration_date']) ? date('d/m/Y', strtotime($member['registration_date'])) : '---'; ?></span></div>
                        <div class="info-row"><span class="info-label">ዓመተ ምህረት:</span><span class="info-value"><?php echo $member['year']; ?></span></div>
                        
                        <!-- Youth-only fields -->
                        <?php if($member['category'] == 'ወጣቶች'): ?>
                        <div class="info-row"><span class="info-label">የአባልነት አይነት:</span><span class="info-value"><?php echo $member['member_type'] ?? '---'; ?></span></div>
                        <div class="info-row"><span class="info-label">የትዳር ሁኔታ:</span><span class="info-value"><?php echo $member['marital_status'] ?? '---'; ?></span></div>
                        <div class="info-row"><span class="info-label">የክህነት ደረጃ:</span><span class="info-value"><?php echo htmlspecialchars($member['church_rank'] ?? '---'); ?></span></div>
                        <?php endif; ?>
                    </div>

                    <!-- EDUCATION -->
                    <div class="info-card">
                        <div class="card-title">🎓 ትምህርት</div>
                        <div class="info-row"><span class="info-label">የትምህርት ደረጃ:</span><span class="info-value"><?php echo htmlspecialchars($member['education_level'] ?? '---'); ?></span></div>
                        
                        <!-- Profession - youth only -->
                        <?php if($member['category'] == 'ወጣቶች'): ?>
                        <div class="info-row"><span class="info-label">የሙያ መስክ:</span><span class="info-value"><?php echo htmlspecialchars($member['profession'] ?? '---'); ?></span></div>
                        <?php endif; ?>
                        
                        <!-- Previous category if upgraded -->
                        <?php if(!empty($member['previous_category'])): ?>
                        <div class="info-row"><span class="info-label">ያለፈ ምድብ:</span><span class="info-value"><?php echo $member['previous_category']; ?> (⬆️ የተሻሻለ)</span></div>
                        <?php endif; ?>
                        
                        <?php if(!empty($member['upgrade_date'])): ?>
                        <div class="info-row"><span class="info-label">የተሻሻለበት ቀን:</span><span class="info-value"><?php echo date('d/m/Y', strtotime($member['upgrade_date'])); ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- REMARKS -->
                <?php if(!empty($member['remarks'])): ?>
                <div class="remarks-section">
                    <div class="remarks-title">📝 ማስታወሻ</div>
                    <div class="remarks-text"><?php echo nl2br(htmlspecialchars($member['remarks'])); ?></div>
                </div>
                <?php endif; ?>

                <!-- FOOTER WITH SIGNATURES -->
                <div class="certificate-footer">
                    <div class="signature-line">
                        <div class="signature"></div>
                        <div class="signature-label">የአባሉ ፊርማ</div>
                    </div>
                    <div class="signature-line">
                        <div class="signature"></div>
                        <div class="signature-label">የኃላፊው ፊርማ</div>
                    </div>
                    <div class="date-line">
                        <div class="date"><?php echo date('d/m/Y'); ?></div>
                        <div class="date-label">የታተመበት ቀን</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>