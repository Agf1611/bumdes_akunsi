<?php

declare(strict_types=1);

final class AssetSpreadsheetExporter
{
    /**
     * @param array<int,array{name:string,rows:array<int,array<int,mixed>>,header_rows?:int,column_widths?:array<int,float|int>,freeze_row?:int,auto_filter?:bool}> $sheets
     */
    public function download(string $filename, array $sheets): never
    {
        $safeFilename = $this->sanitizeFilename($filename);
        $tmpFile = tempnam(sys_get_temp_dir(), 'asset_xlsx_');
        if ($tmpFile === false) {
            throw new RuntimeException('File sementara untuk export aset tidak dapat dibuat.');
        }

        $xlsxPath = $tmpFile . '.xlsx';
        @unlink($tmpFile);

        try {
            $binary = $this->buildWorkbookBinary($sheets);
            if (file_put_contents($xlsxPath, $binary) === false) {
                throw new RuntimeException('File Excel aset tidak dapat dibuat di server.');
            }
            $this->streamFile($xlsxPath, $safeFilename);
        } finally {
            if (is_file($xlsxPath)) {
                @unlink($xlsxPath);
            }
        }
    }

    /**
     * @param array<int,array{name:string,rows:array<int,array<int,mixed>>,header_rows?:int,column_widths?:array<int,float|int>,freeze_row?:int,auto_filter?:bool}> $sheets
     */
    public function buildWorkbookBinary(array $sheets): string
    {
        $sheetDefs = [];
        foreach (array_values($sheets) as $index => $sheet) {
            $rows = [];
            foreach ((array) ($sheet['rows'] ?? []) as $row) {
                $rows[] = array_values(is_array($row) ? $row : [(string) $row]);
            }
            $headerRows = max(0, (int) ($sheet['header_rows'] ?? 1));
            $sheetDefs[] = [
                'id' => $index + 1,
                'name' => $this->sanitizeSheetName((string) ($sheet['name'] ?? ('Sheet' . ($index + 1)))),
                'rows' => $rows,
                'header_rows' => $headerRows,
                'column_widths' => array_values((array) ($sheet['column_widths'] ?? [])),
                'freeze_row' => max(0, (int) ($sheet['freeze_row'] ?? ($headerRows > 0 ? 1 : 0))),
                'auto_filter' => (bool) ($sheet['auto_filter'] ?? false),
            ];
        }

        if ($sheetDefs === []) {
            throw new RuntimeException('Workbook aset tidak memiliki sheet untuk diunduh.');
        }

        $files = [];
        $files['[Content_Types].xml'] = $this->contentTypesXml(count($sheetDefs));
        $files['_rels/.rels'] = $this->rootRelsXml();
        $files['docProps/app.xml'] = $this->appPropsXml($sheetDefs);
        $files['docProps/core.xml'] = $this->corePropsXml();
        $files['xl/workbook.xml'] = $this->workbookXml($sheetDefs);
        $files['xl/_rels/workbook.xml.rels'] = $this->workbookRelsXml($sheetDefs);
        $files['xl/styles.xml'] = $this->stylesXml();

        foreach ($sheetDefs as $sheet) {
            $files['xl/worksheets/sheet' . $sheet['id'] . '.xml'] = $this->worksheetXml(
                $sheet['rows'],
                $sheet['header_rows'],
                $sheet['column_widths'],
                $sheet['freeze_row'],
                $sheet['auto_filter']
            );
        }

        return $this->zipFiles($files);
    }

    private function streamFile(string $path, string $filename): never
    {
        if (headers_sent($file, $line)) {
            throw new RuntimeException('Header download gagal dikirim karena output sudah lebih dulu dikirim di ' . $file . ':' . $line . '.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header_remove();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    private function zipFiles(array $files): string
    {
        $data = '';
        $centralDirectory = '';
        $offset = 0;
        $dosTime = $this->dosTime();
        $dosDate = $this->dosDate();

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', (string) $name);
            $contents = (string) $contents;
            $crc = $this->uInt32(crc32($contents));
            $length = strlen($contents);

            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $length, $length, strlen($name), 0);
            $data .= $localHeader . $name . $contents;

            $centralHeader = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $length, $length, strlen($name), 0, 0, 0, 0, 0, $offset);
            $centralDirectory .= $centralHeader . $name;

            $offset += strlen($localHeader) + strlen($name) + $length;
        }

        $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($centralDirectory), strlen($data), 0);
        return $data . $centralDirectory . $eocd;
    }

    private function contentTypesXml(int $sheetCount): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml[] = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml[] = '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml[] = '<Default Extension="xml" ContentType="application/xml"/>';
        $xml[] = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml[] = '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $xml[] = '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        $xml[] = '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml[] = '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $xml[] = '</Types>';
        return implode('', $xml);
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function appPropsXml(array $sheets): string
    {
        $titles = '';
        foreach ($sheets as $sheet) {
            $titles .= '<vt:lpstr>' . $this->xml((string) $sheet['name']) . '</vt:lpstr>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>BUMDes Asset Module</Application>'
            . '<DocSecurity>0</DocSecurity>'
            . '<ScaleCrop>false</ScaleCrop>'
            . '<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>' . count($sheets) . '</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            . '<TitlesOfParts><vt:vector size="' . count($sheets) . '" baseType="lpstr">' . $titles . '</vt:vector></TitlesOfParts>'
            . '<Company>BUMDes</Company>'
            . '</Properties>';
    }

    private function corePropsXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>BUMDes Asset Module</dc:creator>'
            . '<cp:lastModifiedBy>BUMDes Asset Module</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function workbookXml(array $sheets): string
    {
        $parts = [];
        foreach ($sheets as $sheet) {
            $parts[] = '<sheet name="' . $this->xml((string) $sheet['name']) . '" sheetId="' . $sheet['id'] . '" r:id="rId' . $sheet['id'] . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12800"/></bookViews>'
            . '<sheets>' . implode('', $parts) . '</sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(array $sheets): string
    {
        $rels = [];
        foreach ($sheets as $sheet) {
            $rels[] = '<Relationship Id="rId' . $sheet['id'] . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheet['id'] . '.xml"/>';
        }
        $rels[] = '<Relationship Id="rId' . (count($sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . implode('', $rels) . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="10"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEFF4FB"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function worksheetXml(array $rows, int $headerRows, array $columnWidths, int $freezeRow, bool $autoFilter): string
    {
        $rowCount = max(1, count($rows));
        $maxCols = 1;
        foreach ($rows as $row) {
            $maxCols = max($maxCols, count($row));
        }

        $dimension = 'A1:' . $this->columnLetter($maxCols) . $rowCount;
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml[] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml[] = '<dimension ref="' . $dimension . '"/>';
        $xml[] = '<sheetViews><sheetView workbookViewId="0">';
        if ($freezeRow > 0) {
            $xml[] = '<pane ySplit="' . $freezeRow . '" topLeftCell="A' . ($freezeRow + 1) . '" activePane="bottomLeft" state="frozen"/>';
        }
        $xml[] = '</sheetView></sheetViews>';

        if ($columnWidths !== []) {
            $xml[] = '<cols>';
            foreach ($columnWidths as $index => $width) {
                $col = $index + 1;
                $w = max(8, min(80, (float) $width));
                $xml[] = '<col min="' . $col . '" max="' . $col . '" width="' . number_format($w, 2, '.', '') . '" customWidth="1"/>';
            }
            $xml[] = '</cols>';
        }

        $xml[] = '<sheetData>';
        if ($rows === []) {
            $rows = [[]];
        }
        foreach ($rows as $rowIndex => $row) {
            $xml[] = '<row r="' . ($rowIndex + 1) . '">';
            foreach (array_values($row) as $colIndex => $value) {
                $cellRef = $this->columnLetter($colIndex + 1) . ($rowIndex + 1);
                $styleId = $this->styleIdForCell($rowIndex, $headerRows, $colIndex);
                $xml[] = $this->cellXml($cellRef, $value, $styleId);
            }
            $xml[] = '</row>';
        }
        $xml[] = '</sheetData>';

        if ($autoFilter && $rowCount > 1) {
            $xml[] = '<autoFilter ref="A1:' . $this->columnLetter($maxCols) . $rowCount . '"/>';
        }

        $xml[] = '</worksheet>';
        return implode('', $xml);
    }

    private function cellXml(string $cellRef, mixed $value, int $styleId): string
    {
        if ($value === null) {
            return '<c r="' . $cellRef . '" s="' . $styleId . '" t="inlineStr"><is><t></t></is></c>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $cellRef . '" s="' . $styleId . '"><v>' . $this->xml($this->normalizeNumber($value)) . '</v></c>';
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return '<c r="' . $cellRef . '" s="' . $styleId . '" t="inlineStr"><is><t></t></is></c>';
        }

        if ($this->looksNumeric($stringValue)) {
            return '<c r="' . $cellRef . '" s="' . $styleId . '"><v>' . $this->xml($this->normalizeNumber($stringValue)) . '</v></c>';
        }

        return '<c r="' . $cellRef . '" s="' . $styleId . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xml($stringValue) . '</t></is></c>';
    }

    private function styleIdForCell(int $rowIndex, int $headerRows, int $colIndex): int
    {
        if ($rowIndex < $headerRows) {
            return 1;
        }
        return 3;
    }

    private function sanitizeSheetName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            $name = 'Sheet1';
        }
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name);
        if ($name === '') {
            $name = 'Sheet1';
        }
        return function_exists('mb_substr') ? mb_substr($name, 0, 31) : substr($name, 0, 31);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            $filename = 'aset.xlsx';
        }
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'aset.xlsx';
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }
        return $filename;
    }

    private function columnLetter(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }
        return $letters !== '' ? $letters : 'A';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function normalizeNumber(int|float|string $value): string
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }

    private function looksNumeric(string $value): bool
    {
        return preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1;
    }

    private function dosTime(): int
    {
        $time = getdate();
        return (($time['hours'] & 0x1f) << 11) | (($time['minutes'] & 0x3f) << 5) | ((int) floor($time['seconds'] / 2) & 0x1f);
    }

    private function dosDate(): int
    {
        $time = getdate();
        return (((max(1980, (int) $time['year']) - 1980) & 0x7f) << 9) | (($time['mon'] & 0x0f) << 5) | ($time['mday'] & 0x1f);
    }

    private function uInt32(int|string $value): int
    {
        if (is_string($value)) {
            $value = (int) $value;
        }
        return $value < 0 ? $value + 4294967296 : $value;
    }
}
