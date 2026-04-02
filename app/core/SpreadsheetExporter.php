<?php

declare(strict_types=1);

final class SpreadsheetExporter
{
    public function downloadXls(string $filename, string $worksheetName, array $headers, array $rows, string $subtitle = ''): never
    {
        $safeFilename = $this->sanitizeFilename($filename, 'xls');
        $xml = $this->buildSpreadsheetXml($worksheetName, $headers, $rows, $subtitle);

        $this->prepareDownloadHeaders(
            'application/vnd.ms-excel; charset=UTF-8',
            $safeFilename,
            (string) strlen($xml)
        );

        echo $xml;
        exit;
    }

    private function buildSpreadsheetXml(string $worksheetName, array $headers, array $rows, string $subtitle): string
    {
        $worksheetName = $this->truncateSheetName($worksheetName);

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<?mso-application progid="Excel.Sheet"?>';
        $xml[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml[] = ' xmlns:o="urn:schemas-microsoft-com:office:office"';
        $xml[] = ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
        $xml[] = ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml[] = ' xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml[] = '<Styles>';
        $xml[] = '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Borders/><Font ss:FontName="Calibri" ss:Size="11"/><Interior/><NumberFormat/><Protection/></Style>';
        $xml[] = '<Style ss:ID="sTitle"><Font ss:Bold="1" ss:Size="14"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        $xml[] = '<Style ss:ID="sSubtitle"><Font ss:Italic="1" ss:Size="10"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        $xml[] = '<Style ss:ID="sHeader"><Font ss:Bold="1"/><Interior ss:Color="#D9E2F3" ss:Pattern="Solid"/><Borders>';
        $xml[] = '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        $xml[] = '<Style ss:ID="sText"><Borders>';
        $xml[] = '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        $xml[] = '<Style ss:ID="sNumber"><Borders>';
        $xml[] = '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $xml[] = '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><NumberFormat ss:Format="Standard"/></Style>';
        $xml[] = '</Styles>';
        $xml[] = '<Worksheet ss:Name="' . $this->xml($worksheetName) . '">';
        $xml[] = '<Table>';

        $columnCount = max(1, count($headers));
        foreach (range(1, $columnCount) as $_) {
            $xml[] = '<Column ss:AutoFitWidth="1" ss:Width="120"/>';
        }

        $xml[] = '<Row ss:Height="22"><Cell ss:MergeAcross="' . ($columnCount - 1) . '" ss:StyleID="sTitle"><Data ss:Type="String">' . $this->xml($worksheetName) . '</Data></Cell></Row>';
        if ($subtitle !== '') {
            $xml[] = '<Row ss:Height="18"><Cell ss:MergeAcross="' . ($columnCount - 1) . '" ss:StyleID="sSubtitle"><Data ss:Type="String">' . $this->xml($subtitle) . '</Data></Cell></Row>';
        }
        $xml[] = '<Row/>';

        $xml[] = '<Row>';
        foreach ($headers as $header) {
            $xml[] = '<Cell ss:StyleID="sHeader"><Data ss:Type="String">' . $this->xml((string) $header) . '</Data></Cell>';
        }
        $xml[] = '</Row>';

        foreach ($rows as $row) {
            $xml[] = '<Row>';
            foreach ($headers as $index => $_header) {
                $value = $row[$index] ?? '';
                $style = 'sText';
                $type = 'String';
                $data = (string) $value;

                if ($this->isNumericValue($value)) {
                    $style = 'sNumber';
                    $type = 'Number';
                    $data = $this->normalizeNumber($value);
                }

                $xml[] = '<Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . $this->xml($data) . '</Data></Cell>';
            }
            $xml[] = '</Row>';
        }

        $xml[] = '</Table>';
        $xml[] = '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">';
        $xml[] = '<ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios>';
        $xml[] = '</WorksheetOptions>';
        $xml[] = '</Worksheet>';
        $xml[] = '</Workbook>';

        return implode('', $xml);
    }

    private function prepareDownloadHeaders(string $contentType, string $filename, string $contentLength): void
    {
        if (headers_sent($file, $line)) {
            throw new RuntimeException('Header download gagal dikirim karena output sudah lebih dulu dikirim di ' . $file . ':' . $line . '.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header_remove();
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: public');
        header('Content-Length: ' . $contentLength);
    }

    private function sanitizeFilename(string $filename, string $defaultExtension): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            $filename = 'export.' . $defaultExtension;
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: ('export.' . $defaultExtension);
        if (!str_contains($filename, '.')) {
            $filename .= '.' . $defaultExtension;
        }

        return $filename;
    }

    private function truncateSheetName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            $name = 'Sheet1';
        }

        $name = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name) ?: 'Sheet1';
        return mb_substr($name, 0, 31);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function isNumericValue(mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);
        return $value !== '' && preg_match('/^-?\d+(\.\d+)?$/', $value) === 1;
    }

    private function normalizeNumber(mixed $value): string
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return number_format((float) $value, 2, '.', '');
    }
}