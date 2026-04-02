<?php

declare(strict_types=1);

final class ReportPdf
{
    private const MM_TO_PT = 72 / 25.4;

    private float $pageWidthPt;
    private float $pageHeightPt;
    private float $pageWidthMm;
    private float $pageHeightMm;
    private float $marginLeft = 12.0;
    private float $marginRight = 12.0;
    private float $marginTop = 12.0;
    private float $marginBottom = 12.0;
    private float $cursorY = 12.0;
    private array $pages = [];
    private string $currentContent = '';
    private array $currentPageImages = [];
    private string $orientation;
    private array $images = [];
    private int $imageCounter = 0;
    private string $footerLeftText = '';
    private string $footerCenterText = '';

    public function __construct(string $orientation = 'P')
    {
        $this->orientation = strtoupper($orientation) === 'L' ? 'L' : 'P';
        $this->configurePage();
    }

    public function setMargins(float $left, float $top, float $right, float $bottom): void
    {
        $this->marginLeft = $left;
        $this->marginTop = $top;
        $this->marginRight = $right;
        $this->marginBottom = $bottom;
        $this->cursorY = $top;
    }

    public function enablePageFooter(string $leftText, string $centerText = ''): void
    {
        $this->footerLeftText = $leftText;
        $this->footerCenterText = $centerText;
    }

    public function addPage(): void
    {
        if ($this->currentContent !== '') {
            $this->pages[] = [
                'content' => $this->currentContent,
                'images' => array_values(array_unique($this->currentPageImages)),
            ];
        }
        $this->currentContent = "0.5 w\n";
        $this->currentPageImages = [];
        $this->cursorY = $this->marginTop;
    }

    public function getCursorY(): float
    {
        return $this->cursorY;
    }

    public function setCursorY(float $y): void
    {
        $this->cursorY = max($this->marginTop, $y);
    }

    public function ln(float $height): void
    {
        $this->cursorY += $height;
    }

    public function getUsableWidth(): float
    {
        return $this->pageWidthMm - $this->marginLeft - $this->marginRight;
    }

    public function willOverflow(float $height): bool
    {
        return ($this->cursorY + $height) > ($this->pageHeightMm - $this->marginBottom);
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $x1Pt = $this->mmToPt($x1);
        $x2Pt = $this->mmToPt($x2);
        $y1Pt = $this->pageHeightPt - $this->mmToPt($y1);
        $y2Pt = $this->pageHeightPt - $this->mmToPt($y2);
        $this->currentContent .= sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1Pt, $y1Pt, $x2Pt, $y2Pt);
    }

    public function rect(float $x, float $y, float $w, float $h, bool $fill = false, int $fillGray = 245): void
    {
        $xPt = $this->mmToPt($x);
        $yPt = $this->pageHeightPt - $this->mmToPt($y + $h);
        $wPt = $this->mmToPt($w);
        $hPt = $this->mmToPt($h);
        if ($fill) {
            $gray = max(0.0, min(1.0, $fillGray / 255));
            $this->currentContent .= sprintf("q %.3F g %.2F %.2F %.2F %.2F re f Q\n", $gray, $xPt, $yPt, $wPt, $hPt);
        }
        $this->currentContent .= sprintf("%.2F %.2F %.2F %.2F re S\n", $xPt, $yPt, $wPt, $hPt);
    }

    public function text(float $x, float $y, string $text, string $style = '', float $size = 10.0, string $align = 'L', ?float $width = null): void
    {
        $fontRef = strtoupper($style) === 'B' ? 'F2' : 'F1';
        $text = $this->sanitizeText($text);
        $textWidthMm = $this->estimateTextWidthMm($text, $size);
        if ($width !== null) {
            $align = strtoupper($align);
            if ($align === 'C') {
                $x += max(0.0, ($width - $textWidthMm) / 2);
            } elseif ($align === 'R') {
                $x += max(0.0, $width - $textWidthMm);
            }
        }
        $xPt = $this->mmToPt($x);
        $baselinePt = $this->pageHeightPt - $this->mmToPt($y) - ($size * 0.70);
        $escaped = $this->escapePdfText($text);
        $this->currentContent .= sprintf("BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $fontRef, $size, $xPt, $baselinePt, $escaped);
    }

    public function paragraph(float $x, float $y, float $width, string $text, string $style = '', float $size = 10.0, float $lineHeight = 4.8): float
    {
        $lines = $this->wrapText($text, $width, $size);
        $currentY = $y;
        foreach ($lines as $line) {
            $this->text($x, $currentY, $line, $style, $size, 'L');
            $currentY += $lineHeight;
        }
        return max($lineHeight, count($lines) * $lineHeight);
    }

    public function tableRow(array $cells, array $widths, array $aligns = [], float $fontSize = 9.0, bool $header = false, ?callable $onPageBreak = null): float
    {
        $paddingX = 1.2;
        $paddingY = 1.2;
        $lineHeight = $fontSize >= 10 ? 4.8 : 4.2;
        $wrappedCells = [];
        $maxLines = 1;
        foreach ($cells as $index => $cell) {
            $cellText = is_scalar($cell) ? (string) $cell : '';
            $wrapped = $this->wrapText($cellText, max(8.0, (float) $widths[$index] - ($paddingX * 2)), $fontSize);
            $wrappedCells[$index] = $wrapped;
            $maxLines = max($maxLines, count($wrapped));
        }
        $rowHeight = ($maxLines * $lineHeight) + ($paddingY * 2);
        if ($this->willOverflow($rowHeight)) {
            $this->addPage();
            if ($onPageBreak !== null) {
                $onPageBreak($this);
            }
        }
        $x = $this->marginLeft;
        $y = $this->cursorY;
        foreach ($cells as $index => $cell) {
            $width = (float) $widths[$index];
            $align = strtoupper((string) ($aligns[$index] ?? 'L'));
            $this->rect($x, $y, $width, $rowHeight, $header, $header ? 240 : 255);
            $lines = $wrappedCells[$index];
            $textY = $y + $paddingY + 0.2;
            foreach ($lines as $lineIndex => $line) {
                $lineWidth = max(6.0, $width - ($paddingX * 2));
                $this->text($x + $paddingX, $textY + ($lineIndex * $lineHeight), $line, $header ? 'B' : '', $fontSize, $align, $lineWidth);
            }
            $x += $width;
        }
        $this->cursorY += $rowHeight;
        return $rowHeight;
    }

    public function image(string $filePath, float $x, float $y, float $widthMm, float $heightMm = 0): void
    {
        $data = $this->prepareJpegImage($filePath);
        if ($data === null) {
            return;
        }
        [$jpegData, $pixelWidth, $pixelHeight] = $data;
        $key = sha1($jpegData);
        if (!isset($this->images[$key])) {
            $this->imageCounter++;
            $this->images[$key] = [
                'name' => 'Im' . $this->imageCounter,
                'data' => $jpegData,
                'width' => $pixelWidth,
                'height' => $pixelHeight,
            ];
        }
        $name = $this->images[$key]['name'];
        $this->currentPageImages[] = $name;

        if ($heightMm <= 0) {
            $heightMm = $widthMm * ((float) $pixelHeight / max(1.0, (float) $pixelWidth));
        }

        $xPt = $this->mmToPt($x);
        $yPt = $this->pageHeightPt - $this->mmToPt($y + $heightMm);
        $wPt = $this->mmToPt($widthMm);
        $hPt = $this->mmToPt($heightMm);
        $this->currentContent .= sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n", $wPt, $hPt, $xPt, $yPt, $name);
    }

    public function output(string $filename = 'report.pdf'): never
    {
        if ($this->currentContent !== '') {
            $this->pages[] = ['content' => $this->currentContent, 'images' => array_values(array_unique($this->currentPageImages))];
            $this->currentContent = '';
            $this->currentPageImages = [];
        }
        if ($this->pages === []) {
            $this->addPage();
            $this->pages[] = ['content' => $this->currentContent, 'images' => []];
            $this->currentContent = '';
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '__PAGES__';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $imageObjectMap = [];
        foreach ($this->images as $key => &$image) {
            $imageObjectMap[$image['name']] = count($objects) + 1;
            $objects[] = sprintf("<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream", $image['width'], $image['height'], strlen($image['data']), $image['data']);
        }
        unset($image);

        $pageObjectNumbers = [];
        $streamObjectNumbers = [];
        $nextObjectNumber = count($objects) + 1;
        foreach ($this->pages as $page) {
            $pageObjectNumbers[] = $nextObjectNumber;
            $streamObjectNumbers[] = $nextObjectNumber + 1;
            $nextObjectNumber += 2;
        }

        $totalPages = count($this->pages);
        foreach ($this->pages as $index => $page) {
            $pageNo = $pageObjectNumbers[$index];
            $streamNo = $streamObjectNumbers[$index];
            $page['content'] .= $this->buildFooterContent($index + 1, $totalPages);
            $xObjects = '';
            if ($page['images'] !== []) {
                $pairs = [];
                foreach ($page['images'] as $imageName) {
                    if (isset($imageObjectMap[$imageName])) {
                        $pairs[] = '/' . $imageName . ' ' . $imageObjectMap[$imageName] . ' 0 R';
                    }
                }
                if ($pairs !== []) {
                    $xObjects = '/XObject << ' . implode(' ', $pairs) . ' >> ';
                }
            }
            $objects[] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> %s>> /Contents %d 0 R >>', $this->pageWidthPt, $this->pageHeightPt, $xObjects, $streamNo);
            $objects[] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($page['content']), $page['content']);
        }

        $kids = implode(' ', array_map(static fn(int $n): string => $n . ' 0 R', $pageObjectNumbers));
        $objects[1] = sprintf('<< /Type /Pages /Count %d /Kids [%s] >>', count($pageObjectNumbers), $kids);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= 'startxref' . "\n" . $xrefOffset . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }


    private function buildFooterContent(int $pageNumber, int $totalPages): string
    {
        if ($this->footerLeftText === '' && $this->footerCenterText === '') {
            return '';
        }

        $y = $this->pageHeightMm - max(8.0, $this->marginBottom - 2.0);
        $lineY = $y - 2.8;
        $content = '';

        $x1Pt = $this->mmToPt($this->marginLeft);
        $x2Pt = $this->mmToPt($this->pageWidthMm - $this->marginRight);
        $yPt = $this->pageHeightPt - $this->mmToPt($lineY);
        $content .= sprintf("0.35 w %.2F %.2F m %.2F %.2F l S
", $x1Pt, $yPt, $x2Pt, $yPt);

        $leftText = $this->sanitizeText($this->footerLeftText);
        if ($leftText !== '') {
            $content .= $this->buildFooterTextCommand($this->marginLeft, $y, $leftText, 'L');
        }

        $centerText = $this->sanitizeText($this->footerCenterText);
        if ($centerText !== '') {
            $content .= $this->buildFooterTextCommand($this->pageWidthMm / 2, $y, $centerText, 'C');
        }

        $pageText = $this->sanitizeText(sprintf('Halaman %d / %d', $pageNumber, $totalPages));
        $content .= $this->buildFooterTextCommand($this->pageWidthMm - $this->marginRight, $y, $pageText, 'R');

        return $content;
    }

    private function buildFooterTextCommand(float $x, float $y, string $text, string $align = 'L', float $size = 8.2): string
    {
        if ($text === '') {
            return '';
        }

        $textWidthMm = $this->estimateTextWidthMm($text, $size);
        $align = strtoupper($align);
        if ($align === 'C') {
            $x -= $textWidthMm / 2;
        } elseif ($align === 'R') {
            $x -= $textWidthMm;
        }

        $xPt = $this->mmToPt($x);
        $baselinePt = $this->pageHeightPt - $this->mmToPt($y) - ($size * 0.70);
        return sprintf("BT /F1 %.2F Tf %.2F %.2F Td (%s) Tj ET
", $size, $xPt, $baselinePt, $this->escapePdfText($text));
    }

    private function prepareJpegImage(string $filePath): ?array
    {
        if (!is_file($filePath)) {
            return null;
        }
        $mime = mime_content_type($filePath) ?: '';
        if ($mime === 'image/jpeg') {
            $data = file_get_contents($filePath);
            $size = @getimagesize($filePath);
            if ($data === false || !$size) {
                return null;
            }
            return [$data, (int) $size[0], (int) $size[1]];
        }
        if (!extension_loaded('gd')) {
            return null;
        }
        $image = null;
        if ($mime === 'image/png') {
            $image = @imagecreatefrompng($filePath);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($filePath);
        }
        if (!$image) {
            return null;
        }
        ob_start();
        imagejpeg($image, null, 88);
        $data = (string) ob_get_clean();
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);
        return [$data, $width, $height];
    }

    private function configurePage(): void
    {
        $portraitWidth = 595.28;
        $portraitHeight = 841.89;
        if ($this->orientation === 'L') {
            $this->pageWidthPt = $portraitHeight;
            $this->pageHeightPt = $portraitWidth;
        } else {
            $this->pageWidthPt = $portraitWidth;
            $this->pageHeightPt = $portraitHeight;
        }
        $this->pageWidthMm = $this->pageWidthPt / self::MM_TO_PT;
        $this->pageHeightMm = $this->pageHeightPt / self::MM_TO_PT;
    }

    private function mmToPt(float $value): float
    {
        return $value * self::MM_TO_PT;
    }

    private function sanitizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        return $converted === false ? $text : $converted;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function estimateTextWidthMm(string $text, float $size): float
    {
        $length = strlen($text);
        return (($length * $size * 0.5) / 72) * 25.4;
    }

    private function wrapText(string $text, float $widthMm, float $size): array
    {
        $text = $this->sanitizeText($text);
        if ($text === '') {
            return [''];
        }
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($this->estimateTextWidthMm($candidate, $size) <= $widthMm) {
                $current = $candidate;
                continue;
            }
            if ($current !== '') {
                $lines[] = $current;
                $current = $word;
                continue;
            }
            $chunk = '';
            $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [$word];
            foreach ($chars as $char) {
                $candidateChunk = $chunk . $char;
                if ($this->estimateTextWidthMm($candidateChunk, $size) > $widthMm && $chunk !== '') {
                    $lines[] = $chunk;
                    $chunk = $char;
                } else {
                    $chunk = $candidateChunk;
                }
            }
            if ($chunk !== '') {
                $current = $chunk;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        return $lines === [] ? [''] : $lines;
    }
}
