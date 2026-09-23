<?php
/**
 * WoodCon - Xuất dữ liệu CSV / Excel (.xlsx)
 * GIAI ĐOẠN 5: hỗ trợ xuất báo cáo, đơn hàng, sản phẩm, khách hàng.
 */

declare(strict_types=1);

if (!function_exists('export_file')) {

    /** Làm sạch tên file (chỉ giữ chữ a-z, số, . _ -) */
    function export_clean_filename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._\-]/', '_', $name);
        return trim((string)$name, '_-') ?: 'export';
    }

    /**
     * Xuất file theo định dạng rồi kết thúc script.
     * @param string $format csv | xlsx
     */
    function export_file(string $format, string $filename, array $headers, array $rows): never
    {
        if (strtolower($format) === 'xlsx') {
            export_xlsx($filename, $headers, $rows);
        }
        export_csv($filename, $headers, $rows);
    }

    /** Xuất CSV (BOM utf-8, mở được bằng Excel) */
    function export_csv(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . export_clean_filename($filename) . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM utf-8
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /** Xuất Excel .xlsx bằng PhpSpreadsheet */
    function export_xlsx(string $filename, array $headers, array $rows): never
    {
        require_once BASE_PATH . '/vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(array_values($headers), null, 'A1', true);

        $idx = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), null, 'A' . $idx, true);
            $idx++;
        }

        $colCount = count($headers);
        $lastCol = $colCount > 0
            ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount)
            : 'A';

        $style = $sheet->getStyle('A1:' . $lastCol . '1');
        $style->getFont()->setBold(true);
        $style->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F3F4F6');

        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->setAutoFilter('A1:' . $lastCol . '1');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . export_clean_filename($filename) . '.xlsx"');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}