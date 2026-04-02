<?php

declare(strict_types=1);

final class XlsxReader
{
    public function readFirstSheet(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('File Excel tidak ditemukan untuk diproses.');
        }
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP ZipArchive/php-zip belum aktif di server. Aktifkan ekstensi zip agar file .xlsx bisa diimport.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('File Excel tidak dapat dibuka. Pastikan file berformat .xlsx yang valid.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetPath = $this->resolveFirstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new RuntimeException('Sheet pertama pada file Excel tidak ditemukan.');
            }

            return $this->parseSheetXml($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    private function resolveFirstSheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = $this->loadXml($workbookXml);
        $mainNs = $this->mainNamespace($workbook);
        $relNs = $this->relationshipNamespace($workbook);
        $sheets = $mainNs !== '' ? $workbook->children($mainNs)->sheets : $workbook->sheets;
        $sheetNodes = $mainNs !== '' ? $sheets->children($mainNs)->sheet : $sheets->sheet;

        $firstSheet = null;
        foreach ($sheetNodes as $sheetNode) {
            $firstSheet = $sheetNode;
            break;
        }
        if (!$firstSheet instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = '';
        if ($relNs !== '') {
            $attributes = $firstSheet->attributes($relNs);
            $relationshipId = (string) ($attributes['id'] ?? '');
        }
        if ($relationshipId === '') {
            $relationshipId = (string) ($firstSheet['id'] ?? '');
        }
        if ($relationshipId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $rels = $this->loadXml($relsXml);
        foreach ($rels->Relationship as $relationship) {
            if ((string) ($relationship['Id'] ?? '') !== $relationshipId) {
                continue;
            }
            $target = ltrim((string) ($relationship['Target'] ?? ''), '/');
            if ($target === '') {
                break;
            }
            return str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $reader = $this->loadXml($xml);
        $ns = $this->mainNamespace($reader);
        $items = $ns !== '' ? $reader->children($ns)->si : $reader->si;

        $strings = [];
        foreach ($items as $item) {
            $text = '';
            $node = $ns !== '' ? $item->children($ns) : $item;
            if (isset($node->t)) {
                $text = (string) $node->t;
            } elseif (isset($node->r)) {
                foreach ($node->r as $run) {
                    $runNode = $ns !== '' ? $run->children($ns) : $run;
                    $text .= (string) ($runNode->t ?? '');
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function parseSheetXml(string $sheetXml, array $sharedStrings): array
    {
        $sheet = $this->loadXml($sheetXml);
        $rows = [];
        $ns = $this->mainNamespace($sheet);
        $sheetData = $ns !== '' ? $sheet->children($ns)->sheetData : $sheet->sheetData;
        if (!isset($sheetData->row)) {
            return [];
        }

        foreach ($sheetData->row as $rowNode) {
            $row = [];
            $cells = $ns !== '' ? $rowNode->children($ns)->c : $rowNode->c;
            foreach ($cells as $cell) {
                $cellRef = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnIndexFromReference($cellRef);
                $row[$columnIndex] = $this->extractCellValue($cell, $sharedStrings, $ns);
            }

            if ($row === []) {
                continue;
            }

            $maxIndex = max(array_keys($row));
            $normalized = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $normalized[] = trim((string) ($row[$i] ?? ''));
            }
            $rows[] = $normalized;
        }

        return $rows;
    }

    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings, string $ns): string
    {
        $type = (string) ($cell['t'] ?? '');
        $node = $ns !== '' ? $cell->children($ns) : $cell;

        if ($type === 'inlineStr' && isset($node->is)) {
            $inline = $ns !== '' ? $node->is->children($ns) : $node->is;
            if (isset($inline->t)) {
                return (string) $inline->t;
            }
        }

        $value = isset($node->v) ? (string) $node->v : '';
        if ($type === 's' && $value !== '') {
            $index = (int) $value;
            return (string) ($sharedStrings[$index] ?? '');
        }

        return $value;
    }

    private function columnIndexFromReference(string $reference): int
    {
        if ($reference === '') {
            return 0;
        }

        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper((string) ($matches[0] ?? 'A'));
        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $parsed = simplexml_load_string($xml);
            if (!$parsed instanceof SimpleXMLElement) {
                throw new RuntimeException('Isi file Excel tidak dapat dibaca.');
            }
            return $parsed;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function mainNamespace(SimpleXMLElement $xml): string
    {
        $namespaces = $xml->getNamespaces(true);
        return (string) ($namespaces['x'] ?? $namespaces[''] ?? '');
    }

    private function relationshipNamespace(SimpleXMLElement $xml): string
    {
        $namespaces = $xml->getNamespaces(true);
        return (string) ($namespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    }
}
