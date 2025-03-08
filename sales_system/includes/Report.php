<?php
/**
 * Report Generation class
 */
class Report {
    private $db;
    private $data;
    private $title;
    private $headers;
    private $filename;
    private $orientation;
    private $pageSize;
    
    /**
     * Constructor
     * @param string $title Report title
     * @param array $headers Column headers
     */
    public function __construct($title, $headers) {
        $this->db = Database::getInstance();
        $this->title = $title;
        $this->headers = $headers;
        $this->orientation = 'P'; // Portrait
        $this->pageSize = 'A4';
    }
    
    /**
     * Set report data
     * @param array $data Report data
     */
    public function setData($data) {
        $this->data = $data;
    }
    
    /**
     * Set filename
     * @param string $filename Filename without extension
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }
    
    /**
     * Set page orientation
     * @param string $orientation P for Portrait, L for Landscape
     */
    public function setOrientation($orientation) {
        $this->orientation = $orientation;
    }
    
    /**
     * Set page size
     * @param string $pageSize Page size (A4, Letter, etc.)
     */
    public function setPageSize($pageSize) {
        $this->pageSize = $pageSize;
    }
    
    /**
     * Generate PDF report
     * @return string PDF content
     */
    public function generatePDF() {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        try {
            // Create new PDF document
            $pdf = new TCPDF($this->orientation, 'mm', $this->pageSize, true, 'UTF-8');
            
            // Set document information
            $pdf->SetCreator(SYSTEM_NAME);
            $pdf->SetAuthor(SYSTEM_NAME);
            $pdf->SetTitle($this->title);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('dejavusans', '', 10);
            
            // Add title
            $pdf->SetFont('dejavusans', 'B', 16);
            $pdf->Cell(0, 10, $this->title, 0, 1, 'C');
            $pdf->Ln(5);
            
            // Add date
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i'), 0, 1, 'R');
            $pdf->Ln(5);
            
            // Calculate columns width
            $width = ($pdf->GetPageWidth() - 30) / count($this->headers);
            
            // Add headers
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->SetFillColor(200, 200, 200);
            foreach ($this->headers as $header) {
                $pdf->Cell($width, 7, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Add data
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->SetFillColor(255, 255, 255);
            
            foreach ($this->data as $row) {
                foreach ($row as $cell) {
                    $pdf->Cell($width, 7, $cell, 1, 0, 'L');
                }
                $pdf->Ln();
            }
            
            // Return PDF content
            return $pdf->Output($this->filename . '.pdf', 'S');
            
        } catch (Exception $e) {
            error_log("PDF generation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate Excel report
     * @return string Excel content
     */
    public function generateExcel() {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        try {
            // Create new Spreadsheet object
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            
            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator(SYSTEM_NAME)
                ->setLastModifiedBy(SYSTEM_NAME)
                ->setTitle($this->title);
            
            // Get active sheet
            $sheet = $spreadsheet->getActiveSheet();
            
            // Add title
            $sheet->setCellValue('A1', $this->title);
            $sheet->mergeCells('A1:' . $this->getColumnName(count($this->headers)) . '1');
            
            // Style title
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
            
            // Add date
            $sheet->setCellValue('A2', 'Gerado em: ' . date('d/m/Y H:i'));
            $sheet->mergeCells('A2:' . $this->getColumnName(count($this->headers)) . '2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal('right');
            
            // Add headers
            $col = 'A';
            $row = 4;
            foreach ($this->headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('CCCCCC');
                $col++;
            }
            
            // Add data
            $row = 5;
            foreach ($this->data as $dataRow) {
                $col = 'A';
                foreach ($dataRow as $cell) {
                    $sheet->setCellValue($col . $row, $cell);
                    $col++;
                }
                $row++;
            }
            
            // Auto size columns
            foreach (range('A', $this->getColumnName(count($this->headers))) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Create Excel writer
            $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Save to temp file and return content
            $tempFile = tempnam(sys_get_temp_dir(), 'excel');
            $writer->save($tempFile);
            
            $content = file_get_contents($tempFile);
            unlink($tempFile);
            
            return $content;
            
        } catch (Exception $e) {
            error_log("Excel generation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate HTML report
     * @return string HTML content
     */
    public function generateHTML() {
        try {
            $html = '<!DOCTYPE html>
            <html lang="pt-BR">
            <head>
                <meta charset="UTF-8">
                <title>' . htmlspecialchars($this->title) . '</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { text-align: center; }
                    .date { text-align: right; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <h1>' . htmlspecialchars($this->title) . '</h1>
                <div class="date">Gerado em: ' . date('d/m/Y H:i') . '</div>
                
                <table>
                    <thead>
                        <tr>';
            
            // Add headers
            foreach ($this->headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            
            $html .= '</tr>
                    </thead>
                    <tbody>';
            
            // Add data
            foreach ($this->data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</tbody>
                </table>
                
                <div class="no-print" style="margin-top: 20px; text-align: center;">
                    <button onclick="window.print()">Imprimir</button>
                </div>
            </body>
            </html>';
            
            return $html;
            
        } catch (Exception $e) {
            error_log("HTML generation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Excel column name from number
     * @param int $number Column number
     * @return string Column name (A, B, C, ..., Z, AA, AB, ...)
     */
    private function getColumnName($number) {
        $column = '';
        while ($number > 0) {
            $modulo = ($number - 1) % 26;
            $column = chr(65 + $modulo) . $column;
            $number = floor(($number - $modulo) / 26);
        }
        return $column;
    }
}
?>
