<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DataExporter {

    /**
     * Exporter en PDF
     */
    public static function exportPDF($data, $title, $headers, $filename = 'export.pdf') {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // Paysage
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
            ]);

            // En-tête
            $html = '<style>
                body { font-family: DejaVu Sans, sans-serif; }
                h1 { color: #C1272D; text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #C1272D; color: white; padding: 10px; text-align: left; }
                td { padding: 8px; border-bottom: 1px solid #ddd; }
                tr:hover { background-color: #f5f5f5; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>';

            $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
            $html .= '<p style="text-align: center; color: #666;">Généré le ' . date('d/m/Y à H:i') . '</p>';

            // Tableau
            $html .= '<table>';
            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead>';

            $html .= '<tbody>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';

            // Footer
            $html .= '<div class="footer">';
            $html .= '<p>Source : Maroc Inflation - Données HCP</p>';
            $html .= '<p>www.marocinflation.com</p>';
            $html .= '</div>';

            $mpdf->WriteHTML($html);

            // Télécharger
            $mpdf->Output($filename, 'D'); // D = Download

        } catch (Exception $e) {
            die('Erreur PDF : ' . $e->getMessage());
        }
    }

    /**
     * Exporter en Excel
     */
    public static function exportExcel($data, $title, $headers, $filename = 'export.xlsx') {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Titre
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

            // Date
            $sheet->setCellValue('A2', 'Généré le ' . date('d/m/Y à H:i'));
            $sheet->mergeCells('A2:' . chr(64 + count($headers)) . '2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

            // En-têtes
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '4', $header);
                $sheet->getStyle($col . '4')->getFont()->setBold(true);
                $sheet->getStyle($col . '4')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFC1272D');
                $sheet->getStyle($col . '4')->getFont()->getColor()->setARGB('FFFFFFFF');
                $col++;
            }

            // Données
            $row = 5;
            foreach ($data as $dataRow) {
                $col = 'A';
                foreach ($dataRow as $cell) {
                    $sheet->setCellValue($col . $row, $cell);
                    $col++;
                }
                $row++;
            }

            // Auto-size colonnes
            foreach (range('A', chr(64 + count($headers))) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Footer
            $footerRow = $row + 2;
            $sheet->setCellValue('A' . $footerRow, 'Source : Maroc Inflation - Données HCP');
            $sheet->mergeCells('A' . $footerRow . ':' . chr(64 + count($headers)) . $footerRow);
            $sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal('center');

            // Télécharger
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            die('Erreur Excel : ' . $e->getMessage());
        }
    }
}