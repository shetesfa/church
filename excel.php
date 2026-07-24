<?php
include 'config/database.php'; // your database connection
require 'vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$message="";

// ===== IMPORT EXCEL TO TEMPORARY =====
if(isset($_POST['import'])){
    if(isset($_FILES['excel_file']) && $_FILES['excel_file']['size']>0){
        $filePath = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator(2) as $row) { // skip header
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $data = [];
            foreach ($cellIterator as $cell) $data[] = $cell->getValue();

            if(!empty($data[0])){ // only insert if full_name exists
                $stmt = $conn->prepare("INSERT INTO temporary_students (full_name, christian_name, phone, birth_date, gender, parent_name, parent_phone, year) VALUES (?,?,?,?,?,?,?,?)");

                // Convert Excel date to MySQL format
                $birth_date = !empty($data[3]) ? date('Y-m-d', strtotime($data[3])) : NULL;

                $stmt->bind_param(
                    "ssssssis",
                    $data[0],      // full_name
                    $data[1],      // christian_name
                    $data[2],      // phone
                    $birth_date,   // birth_date
                    $data[4],      // gender
                    $data[5],      // parent_name
                    $data[6],      // parent_phone
                    $data[7]       // year
                );
                $stmt->execute();
                $stmt->close();
            }
        }
        $message="Excel imported to temporary table!";
    }
}

// ===== EXPORT APPROVED STUDENTS TO EXCEL =====
if(isset($_POST['export'])){
    $result = $conn->query("SELECT full_name, christian_name, phone, birth_date, gender, parent_name, parent_phone, year FROM students ORDER BY id ASC");
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->fromArray(['Full Name','Christian Name','Phone','Birth Date','Gender','Parent Name','Parent Phone','Year'], NULL, 'A1');

    $rowNum = 2;
    while($row = $result->fetch_assoc()){
        $sheet->fromArray(array_values($row), NULL, 'A'.$rowNum);
        $rowNum++;
    }

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Approved_Students.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Excel Import/Export - Church</title>
<style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:0;}
.container{max-width:800px;margin:50px auto;background:#fff;padding:25px;border-radius:10px;box-shadow:0 0 15px rgba(0,0,0,0.1);}
h2{text-align:center;color:#1e3a5f;margin-bottom:25px;}
form input[type=file]{width:100%;padding:12px;margin:10px 0;border:1px solid #ccc;border-radius:5px;}
form input[type=submit]{background:#1e3a5f;color:#fff;padding:12px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;margin-top:10px;width:100%;}
form input[type=submit]:hover{background:#2e4f7a;}
p.message{color:green;background:#e0ffe0;padding:12px;border-radius:5px;text-align:center;}
hr{margin:30px 0;border:0;border-top:1px solid #ccc;}
a.button{display:inline-block;padding:12px 20px;background:#1e3a5f;color:#fff;text-decoration:none;border-radius:5px;margin-top:15px;}
a.button:hover{background:#2e4f7a;}
</style>
</head><var>1q</var>
<body>
<div class="container">
<h2>Excel Import / Export - Church</h2>

<?php if($message!="") echo "<p class='message'>$message</p>"; ?>

<!-- IMPORT FORM -->
<form method="POST" enctype="multipart/form-data">
<input type="file" name="excel_file" accept=".xlsx,.xls" required>
<input type="submit" name="import" value="Import to Temporary Table">
</form>

<hr>

<!-- EXPORT FORM -->
<form method="POST">
<input type="submit" name="export" value="Export Approved Students">
</form>

<a href="index.php" class="button">Back to Dashboard</a>
</div>
</body>
</html>