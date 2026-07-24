<?php
// excel_export.php - Export with options, logo, beautiful formatting
require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Get export options
$export_type = $_POST['export_type'] ?? $_GET['export_type'] ?? 'all';
$year = $_POST['year'] ?? $_GET['year'] ?? 'all';
$status = $_POST['status'] ?? $_GET['status'] ?? 'all';
$member_type = $_POST['member_type'] ?? $_GET['member_type'] ?? 'all';
$ids = isset($_POST['ids']) ? $_POST['ids'] : (isset($_GET['ids']) ? explode(',', $_GET['ids']) : []);

// Build query based on options
$query = "SELECT * FROM members WHERE 1=1";

if($export_type == 'selected' && !empty($ids)) {
    $ids = array_map('intval', $ids);
    $query .= " AND id IN (" . implode(',', $ids) . ")";
}

if($year != 'all') {
    $query .= " AND year = " . intval($year);
}

if($status != 'all') {
    $query .= " AND status = '$status'";
}

if($member_type != 'all') {
    $query .= " AND member_type = '$member_type'";
}

$query .= " ORDER BY year DESC, full_name ASC";
$result = $conn->query($query);

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('አባላት');

// Add Logo (if exists)
if(file_exists('images/icon.png')) {
    $drawing = new Drawing();
    $drawing->setName('Church Logo');
    $drawing->setDescription('Church Logo');
    $drawing->setPath('images/icon.png');
    $drawing->setHeight(80);
    $drawing->setCoordinates('A1');
    $drawing->setWorksheet($sheet);
}

// Add Header Title
$sheet->setCellValue('C2', 'አጸደ ትጉሃን ሰንበት ትምህርት ቤት');
$sheet->getStyle('C2')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('8B4513');
$sheet->mergeCells('C2:H2');
$sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('C3', 'የአባላት ዝርዝር');
$sheet->getStyle('C3')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('DAA520');
$sheet->mergeCells('C3:H3');
$sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('C4', 'የተመረጠበት ቀን: ' . date('d/m/Y'));
$sheet->mergeCells('C4:H4');
$sheet->getStyle('C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Headers
$headers = [
    'መታወቂያ ቁጥር', 'ሙሉ ስም', 'ክርስትና ስም', 'ስልክ ቁጥር', 'የትውልድ ቀን',
    'ጾታ', 'የአደጋ ጊዜ ተጠሪ', 'ስልክ', 'የንስሐ አባት', 'ስልክ',
    'የተመዘገበበት ቀን', 'መሸኛ', 'የትዳር ሁኔታ', 'የአባልነት አይነት',
    'የትምህርት ደረጃ', 'የሙያ መስክ', 'ክፍለ ከተማ', 'ወረዳ',
    'የክህነት ደረጃ', 'ዓመት', 'ሁኔታ', 'ማስታወሻ'
];

// Style headers
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '8B4513'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$col = 'A';
$startRow = 6;
foreach($headers as $header) {
    $sheet->setCellValue($col . $startRow, $header);
    $sheet->getStyle($col . $startRow)->applyFromArray($headerStyle);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Add data
$rowNum = $startRow + 1;
while($row = $result->fetch_assoc()) {
    $col = 'A';
    
    // Alternate row colors
    if(($rowNum - $startRow) % 2 == 0) {
        $sheet->getStyle('A' . $rowNum . ':V' . $rowNum)->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('F9F9F9');
    }
    
    $sheet->setCellValue($col++ . $rowNum, $row['member_id_number'] ?? 'ATS'.str_pad($row['id'],5,'0',STR_PAD_LEFT));
    $sheet->setCellValue($col++ . $rowNum, $row['full_name']);
    $sheet->setCellValue($col++ . $rowNum, $row['christian_name']);
    $sheet->setCellValue($col++ . $rowNum, $row['phone']);
    $sheet->setCellValue($col++ . $rowNum, !empty($row['birth_date']) ? date('d/m/Y', strtotime($row['birth_date'])) : '');
    $sheet->setCellValue($col++ . $rowNum, $row['gender']);
    $sheet->setCellValue($col++ . $rowNum, $row['emergency_name']);
    $sheet->setCellValue($col++ . $rowNum, $row['emergency_phone']);
    $sheet->setCellValue($col++ . $rowNum, $row['confession_father']);
    $sheet->setCellValue($col++ . $rowNum, $row['confession_phone']);
    $sheet->setCellValue($col++ . $rowNum, !empty($row['registration_date']) ? date('d/m/Y', strtotime($row['registration_date'])) : '');
    $sheet->setCellValue($col++ . $rowNum, $row['meshena']);
    $sheet->setCellValue($col++ . $rowNum, $row['marital_status']);
    $sheet->setCellValue($col++ . $rowNum, $row['member_type']);
    $sheet->setCellValue($col++ . $rowNum, $row['education_level']);
    $sheet->setCellValue($col++ . $rowNum, $row['profession']);
    $sheet->setCellValue($col++ . $rowNum, $row['subcity']);
    $sheet->setCellValue($col++ . $rowNum, $row['woreda']);
    $sheet->setCellValue($col++ . $rowNum, $row['church_rank']);
    $sheet->setCellValue($col++ . $rowNum, $row['year']);
    $sheet->setCellValue($col++ . $rowNum, $row['status'] == 'approved' ? 'የተረጋገጠ' : 'ጊዜያዊ');
    $sheet->setCellValue($col++ . $rowNum, $row['remarks']);
    
    // Highlight missing fields
    if(empty($row['phone'])) {
        $sheet->getStyle('D' . $rowNum)->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('FFF3CD');
    }
    if(empty($row['birth_date']) || $row['birth_date']=='0000-00-00') {
        $sheet->getStyle('E' . $rowNum)->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('FFF3CD');
    }
    if(empty($row['gender'])) {
        $sheet->getStyle('F' . $rowNum)->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('FFF3CD');
    }
    
    $rowNum++;
}

// Add border to all data
$sheet->getStyle('A' . $startRow . ':V' . ($rowNum-1))->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

// Add total row
$totalRow = $rowNum + 1;
$sheet->setCellValue('A' . $totalRow, 'ጠቅላላ አባላት:');
$sheet->setCellValue('B' . $totalRow, ($rowNum - $startRow - 1) . ' አባላት');
$sheet->getStyle('A' . $totalRow . ':B' . $totalRow)->getFont()->setBold(true);

// Add filter information
$filterRow = $totalRow + 2;
$sheet->setCellValue('A' . $filterRow, 'የተጠቀሙ ማጣሪያዎች:');
$sheet->getStyle('A' . $filterRow)->getFont()->setBold(true);

$filterInfo = [];
if($year != 'all') $filterInfo[] = "ዓመት: $year";
if($status != 'all') $filterInfo[] = "ሁኔታ: " . ($status == 'approved' ? 'የተረጋገጠ' : 'ጊዜያዊ');
if($member_type != 'all') $filterInfo[] = "አይነት: $member_type";
if($export_type == 'selected') $filterInfo[] = "የተመረጡ አባላት ብቻ";

$sheet->setCellValue('A' . ($filterRow + 1), implode(' | ', $filterInfo) ?: 'ምንም ማጣሪያ አልተጠቀሙም');

// Set filename
$filename = 'አባላት_';
if($year != 'all') $filename .= $year . '_';
if($status != 'all') $filename .= ($status == 'approved' ? 'የተረጋገጡ' : 'ጊዜያዊ') . '_';
$filename .= date('Y_m_d') . '.xlsx';

// If this is a form request, show export options page
if(!isset($_POST['export']) && !isset($_GET['export'])) {
    // Get years for dropdown
    $years = $conn->query("SELECT DISTINCT year FROM members ORDER BY year DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ኤክሴል ላክ | Export Options</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Noto Sans Ethiopic', serif; background: linear-gradient(135deg, #5D3A1A, #6B2E2E, #8B6913); padding: 20px; }
        
        .container { max-width: 800px; margin: 0 auto; background: linear-gradient(135deg, #FDB931, #F39C12, #C0392B); border-radius: 20px; position: relative; overflow: hidden; }
        .container::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 2px double rgba(255,255,255,0.3); border-radius: 15px; }
        
        .header { text-align: center; padding: 30px; position: relative; z-index: 2; }
        .church-logo { width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 4px solid white; overflow: hidden; cursor: pointer; }
        .church-logo img { width: 100%; height: 100%; object-fit: cover; }
        .header h1 { font-size: 36px; color: white; text-transform: uppercase; letter-spacing: 3px; }
        .header .amharic { font-size: 32px; color: #FFD700; border-bottom: 2px solid #FFD700; display: inline-block; }
        
        .nav { display: flex; flex-wrap: wrap; gap: 5px; padding: 10px 20px; background: rgba(0,0,0,0.3); }
        .nav a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 20px; font-size: 14px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,215,0,0.3); }
        .nav a:hover, .nav a.active { background: #FFD700; color: #5D3A1A; }
        
        .export-container { padding: 30px; position: relative; z-index: 2; }
        .section-title { font-size: 22px; color: #FFD700; margin-bottom: 20px; border-left: 4px solid #FFD700; padding-left: 15px; }
        
        .export-form { background: rgba(255,255,255,0.1); border-radius: 15px; padding: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #FFD700; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(255,255,255,0.15); color: white; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #FFD700; }
        select.form-control option { background: #5D3A1A; }
        
        .radio-group { display: flex; gap: 20px; flex-wrap: wrap; }
        .radio-label { display: flex; align-items: center; gap: 5px; color: white; cursor: pointer; }
        
        .button-group { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .btn { padding: 12px 30px; border: none; border-radius: 25px; font-size: 16px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; }
        .btn-primary { background: #FFD700; color: #5D3A1A; }
        .btn-secondary { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,215,0,0.3); }
        .btn:hover { transform: translateY(-2px); }
        
        .footer { text-align: center; padding: 15px; border-top: 1px solid rgba(255,215,0,0.2); color: white; background: rgba(0,0,0,0.2); font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-logo" onclick="window.location.href='index.php'">
                <img src="images/icon.png" alt="Logo">
            </div>
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">ኤክሴል ላክ - ማጣሪያ</div>
        </div>

        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="temporary.php">ጊዜያዊ</a>
            <a href="excel_import.php">ኤክሴል አምጣ</a>
            <a href="excel_export.php" class="active">ኤክሴል ላክ</a>
        </div>

        <div class="export-container">
            <div class="section-title">📥 ኤክሴል አውርድ - የሚፈልጉትን ይምረጡ</div>
            
            <form method="POST" action="excel_export.php?export=1" class="export-form">
                <div class="form-group">
                    <label>📊 የሚላክ መረጃ አይነት</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="export_type" value="all" checked> ሁሉም አባላት
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="export_type" value="filtered"> በማጣሪያ መሰረት
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>📅 ዓመት</label>
                    <select name="year" class="form-control">
                        <option value="all">ሁሉም ዓመታት</option>
                        <?php while($y = $years->fetch_assoc()): ?>
                        <option value="<?php echo $y['year']; ?>"><?php echo $y['year']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>✅ ሁኔታ</label>
                    <select name="status" class="form-control">
                        <option value="all">ሁሉም</option>
                        <option value="approved">የተረጋገጠ</option>
                        <option value="temporary">ጊዜያዊ</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>🏷️ የአባልነት አይነት</label>
                    <select name="member_type" class="form-control">
                        <option value="all">ሁሉም</option>
                        <option value="እጩ">እጩ</option>
                        <option value="መደበኛ">መደበኛ</option>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" name="export" class="btn btn-primary">
                        📥 ኤክሴል አውርድ
                    </button>
                    <a href="members.php" class="btn btn-secondary">
                        🔙 ተመለስ
                    </a>
                </div>
            </form>
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት
        </div>
    </div>
</body>
</html>

<?php
    exit();
}

// Output Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>