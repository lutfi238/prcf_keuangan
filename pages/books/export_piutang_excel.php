<?php
session_start();

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Finance Manager') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

// Get header ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: No receivable header ID specified');
}

$id_piutang = $_GET['id'];

// Fetch header data
$header_query = "SELECT 
    ph.*,
    p.nama_proyek,
    u.nama as creator_name
FROM buku_piutang_header ph
LEFT JOIN proyek p ON ph.kode_proyek = p.kode_proyek
LEFT JOIN user u ON ph.created_by = u.id_user
WHERE ph.id_piutang = ?";

$stmt = $conn->prepare($header_query);
$stmt->bind_param("i", $id_piutang);
$stmt->execute();
$header_result = $stmt->get_result();

if ($header_result->num_rows === 0) {
    die('Error: Receivable header not found');
}

$header = $header_result->fetch_assoc();

// Fetch detail transactions
$details_query = "SELECT * FROM buku_piutang_detail 
    WHERE id_piutang = ? 
    ORDER BY tgl_trx ASC, id_detail_piutang ASC";

$details_stmt = $conn->prepare($details_query);
$details_stmt->bind_param("i", $id_piutang);
$details_stmt->execute();
$details_result = $details_stmt->get_result();

$details = [];
while ($detail = $details_result->fetch_assoc()) {
    $details[] = $detail;
}

// Month names
$month_names = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

$month_name = $month_names[$header['periode_bulan']];
$year = $header['periode_tahun'];

// ============================================================
// INTELLIGENT ORIENTATION DETECTION
// ============================================================
// Calculate total content width to determine optimal orientation
// Advance Book has 9 columns with varying widths

// Column widths (in Excel units):
$column_widths = [
    80,  // Date
    100, // Reference
    250, // Description
    150, // Recipient
    100, // Debit (IDR)
    100, // Credit (IDR)
    100, // Balance (IDR)
    100, // Debit (USD)
    100  // Credit (USD)
];

$total_width = array_sum($column_widths); // 1080 units

// Thresholds:
// Portrait: Up to 850 units (comfortable for 8.5" width)
// Landscape: 850-1400 units (comfortable for 11" width)
$portrait_threshold = 850;

// Calculate estimated content width based on actual data
$max_description_length = 0;
$max_recipient_length = 0;

foreach ($details as $detail) {
    $max_description_length = max($max_description_length, strlen($detail['description'] ?? ''));
    $max_recipient_length = max($max_recipient_length, strlen($detail['recipient'] ?? ''));
}

// Adjust column widths based on content
if ($max_description_length > 40) {
    $column_widths[2] = 300; // Increase Description width
} elseif ($max_description_length > 30) {
    $column_widths[2] = 270; // Moderate increase
}

if ($max_recipient_length > 20) {
    $column_widths[3] = 180; // Increase Recipient width
} elseif ($max_recipient_length > 15) {
    $column_widths[3] = 160; // Moderate increase
}

$adjusted_total_width = array_sum($column_widths);

// Determine orientation based on content width
if ($adjusted_total_width <= $portrait_threshold) {
    $page_orientation = 'Portrait';
    $paper_width_label = '8.5"';
} else {
    $page_orientation = 'Landscape';
    $paper_width_label = '11"';
    // In landscape, we can accommodate wider content
    // Adjust columns for better landscape display
    $column_widths[2] = min($column_widths[2], 320); // Description max in landscape
    $column_widths[3] = min($column_widths[3], 200); // Recipient max in landscape
}

// Comment for debugging (will be in HTML comment in Excel)
$orientation_note = "Auto-detected orientation: $page_orientation (Total width: {$adjusted_total_width}px, Threshold: {$portrait_threshold}px)";

// Create filename
$filename = sprintf(
    "Advance_Book_%s_%s_%s_%s.xls",
    $header['kode_proyek'],
    $year,
    $month_name,
    date('YmdHis')
);

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Start Excel XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>PRCF Keuangan Dashboard</Author>
  <Created><?php echo date('Y-m-d\TH:i:s\Z'); ?></Created>
 </DocumentProperties>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" ss:Size="11"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="s62">
   <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1"/>
  </Style>
  <Style ss:ID="s63">
   <Font ss:FontName="Calibri" ss:Size="12" ss:Bold="1"/>
  </Style>
  <Style ss:ID="s64">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>
   <Interior ss:Color="#10B981" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s65">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Alignment ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="s66">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <NumberFormat ss:Format="#,##0.00"/>
  </Style>
  <Style ss:ID="s67">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="s68">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <NumberFormat ss:Format="#,##0.00"/>
   <Interior ss:Color="#E7E6E6" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s69">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>
  </Style>
  <Style ss:ID="s70">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Advance Book">
  <!-- <?php echo $orientation_note; ?> -->
  <Table>
   <!-- Label column for Project Info section - wider to accommodate labels with colons -->
   <Column ss:Index="1" ss:Width="130"/>
   <!-- Data columns for transaction table -->
   <Column ss:Width="<?php echo $column_widths[0]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[1]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[2]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[3]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[4]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[5]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[6]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[7]; ?>"/>
   <Column ss:Width="<?php echo $column_widths[8]; ?>"/>
   
   <!-- Title Row -->
   <Row ss:Height="25">
    <Cell ss:MergeAcross="8" ss:StyleID="s62">
     <Data ss:Type="String">ADVANCE BOOK (BUKU PIUTANG)</Data>
    </Cell>
   </Row>
   
   <!-- Project Info -->
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Project Code:</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="s70">
     <Data ss:Type="String"><?php echo htmlspecialchars($header['kode_proyek']); ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Project Name:</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="s70">
     <Data ss:Type="String"><?php echo htmlspecialchars($header['nama_proyek']); ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Period:</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="s70">
     <Data ss:Type="String"><?php echo $month_name . ' ' . $year; ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Exchange Rate:</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="s70">
     <Data ss:Type="Number"><?php echo $header['exrate']; ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Created By:</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="s70">
     <Data ss:Type="String"><?php echo htmlspecialchars($header['creator_name'] ?: 'System'); ?></Data>
    </Cell>
   </Row>
   
   <!-- Empty Row -->
   <Row ss:Height="15"></Row>
   
   <!-- Table Header -->
   <Row ss:Height="30">
    <Cell ss:StyleID="s64"><Data ss:Type="String">Date</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Reference</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Description</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Recipient</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Debit (IDR)</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Credit (IDR)</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Balance (IDR)</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Debit (USD)</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Credit (USD)</Data></Cell>
   </Row>
   
   <!-- Beginning Balance Row -->
   <Row ss:Height="20">
    <Cell ss:StyleID="s65"><Data ss:Type="String"></Data></Cell>
    <Cell ss:StyleID="s65"><Data ss:Type="String"></Data></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="s69">
     <Data ss:Type="String">Beginning Balance</Data>
    </Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">0</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">0</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number"><?php echo $header['beginning_balance_idr']; ?></Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">0</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">0</Data></Cell>
   </Row>
   
   <?php 
   // Transaction rows
   foreach ($details as $detail): 
   ?>
   <Row ss:Height="20">
    <Cell ss:StyleID="s67">
     <Data ss:Type="String"><?php echo date('d/m/Y', strtotime($detail['tgl_trx'])); ?></Data>
    </Cell>
    <Cell ss:StyleID="s65">
     <Data ss:Type="String"><?php echo htmlspecialchars($detail['reff']); ?></Data>
    </Cell>
    <Cell ss:StyleID="s65">
     <Data ss:Type="String"><?php echo htmlspecialchars($detail['description']); ?></Data>
    </Cell>
    <Cell ss:StyleID="s65">
     <Data ss:Type="String"><?php echo htmlspecialchars($detail['recipient']); ?></Data>
    </Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php echo $detail['debit_idr']; ?></Data>
    </Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php echo $detail['credit_idr']; ?></Data>
    </Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php echo $detail['balance_idr']; ?></Data>
    </Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php echo $detail['debit_usd']; ?></Data>
    </Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php echo $detail['credit_usd']; ?></Data>
    </Cell>
   </Row>
   <?php endforeach; ?>
   
   <!-- Ending Balance Row -->
   <Row ss:Height="22">
    <Cell ss:StyleID="s68"><Data ss:Type="String"></Data></Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="String"></Data></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="s68">
     <Data ss:Type="String">Ending Balance</Data>
    </Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="String"></Data></Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="String"></Data></Cell>
    <Cell ss:StyleID="s68">
     <Data ss:Type="Number"><?php echo $header['ending_balance_idr']; ?></Data>
    </Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="String"></Data></Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="String"></Data></Cell>
   </Row>
   
   <!-- Empty Rows -->
   <Row ss:Height="15"></Row>
   <Row ss:Height="15"></Row>
   
   <!-- Summary Section -->
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Total Debit (IDR):</Data></Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php 
        $total_debit_idr = array_sum(array_column($details, 'debit_idr'));
        echo $total_debit_idr;
     ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Total Credit (IDR):</Data></Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php 
        $total_credit_idr = array_sum(array_column($details, 'credit_idr'));
        echo $total_credit_idr;
     ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Net Change (IDR):</Data></Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php 
        $net_change_idr = $header['ending_balance_idr'] - $header['beginning_balance_idr'];
        echo $net_change_idr;
     ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="15"></Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Total Debit (USD):</Data></Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php 
        $total_debit_usd = array_sum(array_column($details, 'debit_usd'));
        echo $total_debit_usd;
     ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Total Credit (USD):</Data></Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php 
        $total_credit_usd = array_sum(array_column($details, 'credit_usd'));
        echo $total_credit_usd;
     ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="s69"><Data ss:Type="String">Net Change (USD):</Data></Cell>
    <Cell ss:StyleID="s66">
     <Data ss:Type="Number"><?php 
        $net_change_usd = $header['ending_balance_usd'] - $header['beginning_balance_usd'];
        echo $net_change_usd;
     ?></Data>
    </Cell>
   </Row>
   
   <!-- Footer -->
   <Row ss:Height="15"></Row>
   <Row ss:Height="15"></Row>
   <Row ss:Height="18">
    <Cell ss:MergeAcross="2">
     <Data ss:Type="String">Prepared by: <?php echo htmlspecialchars($header['creator_name'] ?: 'System'); ?></Data>
    </Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:MergeAcross="2">
     <Data ss:Type="String">Generated on: <?php echo date('d/m/Y H:i:s'); ?></Data>
    </Cell>
   </Row>
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <PageSetup>
    <Layout x:Orientation="<?php echo $page_orientation; ?>"/>
    <Header x:Margin="0.3"/>
    <Footer x:Margin="0.3"/>
    <PageMargins x:Bottom="0.75" x:Left="0.7" x:Right="0.7" x:Top="0.75"/>
   </PageSetup>
   <FitToPage/>
   <Print>
    <FitWidth>1</FitWidth>
    <FitHeight>0</FitHeight>
    <ValidPrinterInfo/>
    <PaperSizeIndex>1</PaperSizeIndex>
    <HorizontalResolution>600</HorizontalResolution>
    <VerticalResolution>600</VerticalResolution>
   </Print>
   <Selected/>
   <Panes>
    <Pane>
     <Number>3</Number>
     <ActiveRow>0</ActiveRow>
     <ActiveCol>0</ActiveCol>
    </Pane>
   </Panes>
   <ProtectObjects>False</ProtectObjects>
   <ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
