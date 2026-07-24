<?php
// print_selected.php - Print multiple selected members with category support
require_once 'config.php';

$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$ids = array_map('intval', $ids);

if(empty($ids)) {
    header("Location: members.php");
    exit();
}

$ids_str = implode(',', $ids);
$result = $conn->query("SELECT * FROM members WHERE id IN ($ids_str) ORDER BY category ASC, full_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ህትመት | Print Selected</title>
    <style>
        /* Certificate Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Noto Sans Ethiopic', 'Times New Roman', serif; background: #f0f0f0; padding: 20px; }
        
        .print-container { max-width: 1100px; margin: 0 auto; }
        
        .print-page {
            background: linear-gradient(135deg, #FFD700, #FF8C00, #B22222);
            color: white;
            margin-bottom: 20px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            page-break-after: always;
            min-height: 900px;
            display: flex;
            flex-direction: column;
        }
        
        /* Category-based border colors */
        .print-page.children-page {
            border-left: 10px solid #3498db;
        }
        
        .print-page.youth-page {
            border-left: 10px solid #2ecc71;
        }
        
        .print-border {
            position: absolute;
            top: 20px; left: 20px; right: 20px; bottom: 20px;
            border: 3px double rgba(255,255,255,0.5);
            border-radius: 15px;
            pointer-events: none;
        }
        
        .print-bg {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            opacity: 0.1;
            background-image: radial-gradient(rgba(255,255,255,0.5) 1px, transparent 1px);
            background-size: 30px 30px;
        }
        
        .content { position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column; }
        
        .header { text-align: center; margin-bottom: 30px; }
        .logo { width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 4px solid white; overflow: hidden; }
        .logo img { width: 100%; height: 100%; object-fit: cover; }
        .title { font-size: 36px; color: white; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 5px; }
        .subtitle { font-size: 28px; color: #FFD700; border-bottom: 2px solid #FFD700; display: inline-block; padding-bottom: 5px; }
        
        /* Category Header */
        .category-header {
            background: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 30px;
            margin: 20px 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }
        
        .category-header.children {
            background: rgba(52, 152, 219, 0.3);
            color: #3498db;
        }
        
        .category-header.youth {
            background: rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }
        
        .student-name { font-size: 42px; color: #FFD700; text-align: center; margin: 30px 0; font-weight: bold; }
        
        /* Category Badge */
        .category-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 16px;
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
        
        .age-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 30px 0; }
        .info-item { margin-bottom: 10px; }
        .info-label { color: rgba(255,255,255,0.8); font-size: 14px; }
        .info-value { color: #FFD700; font-size: 18px; font-weight: bold; }
        
        .footer { display: flex; justify-content: space-between; margin-top: auto; padding-top: 40px; }
        .signature { width: 200px; border-bottom: 2px solid #FFD700; margin-top: 30px; text-align: center; padding-top: 5px; color: rgba(255,255,255,0.8); }
        
        .print-controls { text-align: center; margin: 20px 0; }
        .print-btn { padding: 12px 30px; background: #8B4513; color: white; border: none; border-radius: 30px; font-size: 16px; cursor: pointer; margin: 0 10px; }
        
        @media print {
            body { background: white; padding: 0; }
            .print-page { box-shadow: none; margin: 0; padding: 0.5in; background: white; color: black; page-break-after: always; }
            .title, .student-name, .subtitle, .info-value { color: black; }
            .print-border { border-color: black; }
            .signature { border-color: black; }
            .print-controls { display: none; }
            .category-header { background: #f0f0f0; color: black; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button class="print-btn" onclick="window.print()">🖨️ አትም</button>
        <button class="print-btn" onclick="window.close()">❌ ዝጋ</button>
        <a href="members.php" class="print-btn" style="text-decoration: none;">👥 ተመለስ</a>
    </div>

    <div class="print-container">
        <?php 
        $current_category = '';
        while($row = $result->fetch_assoc()): 
            $age = calculateAge($row['birth_date']);
            
            // Add category separator if category changes
            if($current_category != $row['category']):
                $current_category = $row['category'];
                $category_display = ($current_category == 'ህጻናት') ? '🧒 ህጻናት' : '👦 ወጣቶች';
                $category_class = ($current_category == 'ህጻናት') ? 'children-page' : 'youth-page';
            ?>
            <div class="print-page <?php echo $category_class; ?>">
                <div class="print-border"></div>
                <div class="print-bg"></div>
                
                <div class="content">
                    <div class="header">
                        <div class="logo">
                            <img src="images/icon.png" alt="Church Logo">
                        </div>
                        <div class="title">አጸደ ትጉሃን ሰንበት ትምህርት ቤት</div>
                        <div class="subtitle">የአባል መታወቂያ</div>
                    </div>
                    
                    <div class="category-header <?php echo ($current_category == 'ህጻናት') ? 'children' : 'youth'; ?>">
                        <?php echo $category_display; ?>
                    </div>
                    
                    <div class="student-name">
                        <?php echo htmlspecialchars($row['full_name']); ?>
                        <span class="age-badge">🎂 <?php echo $age; ?> ዓመት</span>
                    </div>
                    
                    <div class="info-grid">
                        <div>
                            <div class="info-item">
                                <div class="info-label">ክርስትና ስም</div>
                                <div class="info-value"><?php echo htmlspecialchars($row['christian_name'] ?? '---'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">መታወቂያ ቁጥር</div>
                                <div class="info-value"><?php echo $row['member_id_number'] ?? 'ATS'.str_pad($row['id'],5,'0',STR_PAD_LEFT); ?></div>
                            </div>
                            
                            <!-- Phone - show for youth or if exists -->
                            <?php if($row['category'] == 'ወጣቶች' || !empty($row['phone'])): ?>
                            <div class="info-item">
                                <div class="info-label">ስልክ ቁጥር</div>
                                <div class="info-value"><?php echo htmlspecialchars($row['phone'] ?? '---'); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <div class="info-label">የትውልድ ቀን</div>
                                <div class="info-value"><?php echo !empty($row['birth_date']) ? date('d/m/Y', strtotime($row['birth_date'])) : '---'; ?></div>
                            </div>
                        </div>
                        <div>
                            <div class="info-item">
                                <div class="info-label">ዓመተ ምህረት</div>
                                <div class="info-value"><?php echo $row['year']; ?></div>
                            </div>
                            
                            <!-- Youth-only fields -->
                            <?php if($row['category'] == 'ወጣቶች'): ?>
                            <div class="info-item">
                                <div class="info-label">የአባልነት አይነት</div>
                                <div class="info-value"><?php echo $row['member_type'] ?? '---'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">የትዳር ሁኔታ</div>
                                <div class="info-value"><?php echo $row['marital_status'] ?? '---'; ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <div class="info-label">ሁኔታ</div>
                                <div class="info-value"><?php echo $row['status'] == 'approved' ? 'የተረጋገጠ' : 'ጊዜያዊ'; ?></div>
                            </div>
                            
                            <?php if(!empty($row['previous_category'])): ?>
                            <div class="info-item">
                                <div class="info-label">የተሻሻለ</div>
                                <div class="info-value">ከህጻናት ወደ ወጣቶች</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="footer">
                        <div class="signature">የአባሉ ፊርማ</div>
                        <div class="signature">የኃላፊው ፊርማ</div>
                        <div class="signature">ቀን: <?php echo date('d/m/Y'); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>
    
    <script>
        <?php if(isset($_GET['auto_print'])): ?>
        window.onload = function() { window.print(); }
        <?php endif; ?>
    </script>
</body>
</html>