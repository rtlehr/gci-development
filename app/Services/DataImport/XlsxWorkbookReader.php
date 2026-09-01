<?php

namespace App\Services\DataImport;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class XlsxWorkbookReader
{
    private const MAIN_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const MAX_WORKSHEETS = 25;
    private const MAX_DATA_ROWS = 25000;
    private const MAX_COLUMNS = 500;
    private const MAX_XML_ENTRY_BYTES = 50 * 1024 * 1024;
    private const MAX_TOTAL_XML_BYTES = 100 * 1024 * 1024;
    private const MAX_COMPRESSION_RATIO = 200;

    public function __construct(private readonly HeaderNormalizer $normalizer) {}

    public function inspect(string $path, int $sampleRows = 3): array
    {
        $zip = $this->openWorkbook($path);

        try {
            $this->validateArchive($zip);
            $sharedStrings = $this->sharedStrings($zip);
            $relationships = $this->relationships($zip);
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $workbook->registerXPathNamespace('m', self::MAIN_NAMESPACE);
            $workbook->registerXPathNamespace('r', self::RELATIONSHIP_NAMESPACE);

            $workbookSheets = $workbook->xpath('//m:sheets/m:sheet') ?: [];
            if (count($workbookSheets) > self::MAX_WORKSHEETS) {
                throw new RuntimeException('The Excel workbook contains too many worksheets. The maximum supported is '.self::MAX_WORKSHEETS.'.');
            }

            $sheets = [];
            foreach ($workbookSheets as $index => $sheet) {
                $attributes = $sheet->attributes();
                $relationshipAttributes = $sheet->attributes(self::RELATIONSHIP_NAMESPACE);
                $relationshipId = (string) ($relationshipAttributes['id'] ?? '');
                $target = $relationships[$relationshipId] ?? null;
                if (! $target) {
                    continue;
                }

                $sheetPath = $this->worksheetPath($target);
                $sheetXml = $this->xml($zip, $sheetPath);
                $allRows = $this->xpath($sheetXml, '//m:sheetData/m:row');
                $dataRowCount = max(count($allRows) - 1, 0);

                if ($dataRowCount > self::MAX_DATA_ROWS) {
                    throw new RuntimeException("Worksheet '{$attributes['name']}' contains {$dataRowCount} data rows. The maximum supported is ".self::MAX_DATA_ROWS.'.');
                }

                $rows = $this->readRows($sheetXml, $sharedStrings, max($sampleRows, 0) + 1);
                $rawHeaders = array_shift($rows) ?? [];
                $this->assertColumnLimit($rawHeaders, (string) $attributes['name']);
                $headers = array_map(fn ($header) => $this->normalizer->normalize((string) $header), $rawHeaders);

                $sheets[] = [
                    'index' => $index,
                    'name' => (string) $attributes['name'],
                    'row_count' => $dataRowCount,
                    'column_count' => count($headers),
                    'headers' => $headers,
                    'original_headers' => array_map(fn ($header) => (string) $header, $rawHeaders),
                    'sample_rows' => $rows,
                ];
            }

            return ['sheets' => $sheets];
        } finally {
            $zip->close();
        }
    }

    public function readWorksheet(string $path, int $worksheetIndex): array
    {
        $zip = $this->openWorkbook($path);

        try {
            $this->validateArchive($zip);
            $sharedStrings = $this->sharedStrings($zip);
            $relationships = $this->relationships($zip);
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $workbook->registerXPathNamespace('m', self::MAIN_NAMESPACE);
            $workbook->registerXPathNamespace('r', self::RELATIONSHIP_NAMESPACE);
            $sheets = $workbook->xpath('//m:sheets/m:sheet') ?: [];

            if (count($sheets) > self::MAX_WORKSHEETS) {
                throw new RuntimeException('The Excel workbook contains too many worksheets. The maximum supported is '.self::MAX_WORKSHEETS.'.');
            }

            $sheet = $sheets[$worksheetIndex] ?? null;
            if (! $sheet) {
                throw new RuntimeException('The selected worksheet is no longer available in the workbook.');
            }

            $relationshipAttributes = $sheet->attributes(self::RELATIONSHIP_NAMESPACE);
            $relationshipId = (string) ($relationshipAttributes['id'] ?? '');
            $target = $relationships[$relationshipId] ?? null;
            if (! $target) {
                throw new RuntimeException('The selected worksheet relationship could not be resolved.');
            }

            $sheetPath = $this->worksheetPath($target);
            $sheetXml = $this->xml($zip, $sheetPath);
            $sheetRows = $this->xpath($sheetXml, '//m:sheetData/m:row');
            $dataRowCount = max(count($sheetRows) - 1, 0);
            if ($dataRowCount > self::MAX_DATA_ROWS) {
                throw new RuntimeException('The selected worksheet exceeds the '.self::MAX_DATA_ROWS.' row import limit.');
            }

            $rows = $this->readRows($sheetXml, $sharedStrings, self::MAX_DATA_ROWS + 1);
            $rawHeaders = array_shift($rows) ?? [];
            $this->assertColumnLimit($rawHeaders, (string) $sheet->attributes()['name']);
            $headers = array_map(fn ($header) => $this->normalizer->normalize((string) $header), $rawHeaders);

            return [
                'name' => (string) $sheet->attributes()['name'],
                'headers' => $headers,
                'original_headers' => array_map(fn ($header) => (string) $header, $rawHeaders),
                'rows' => $rows,
            ];
        } finally {
            $zip->close();
        }
    }

    private function openWorkbook(string $path): ZipArchive
    {
        if (! class_exists(ZipArchive::class) || ! function_exists('simplexml_load_string')) {
            throw new RuntimeException('Excel import requires the PHP ZIP and SimpleXML extensions.');
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The Excel workbook is not available for reading.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The Excel workbook could not be opened.');
        }

        return $zip;
    }

    private function validateArchive(ZipArchive $zip): void
    {
        $totalXmlBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (! is_array($stat)) {
                continue;
            }

            $name = (string) ($stat['name'] ?? '');
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }

            if (str_contains($name, '../') || str_starts_with($name, '/') || str_contains($name, "\\")) {
                throw new RuntimeException('The Excel workbook contains an unsafe archive path.');
            }

            if (! str_ends_with(strtolower($name), '.xml') && ! str_ends_with(strtolower($name), '.rels')) {
                continue;
            }

            $size = (int) ($stat['size'] ?? 0);
            $compressedSize = (int) ($stat['comp_size'] ?? 0);

            if ($size > self::MAX_XML_ENTRY_BYTES) {
                throw new RuntimeException('The Excel workbook contains an XML part that is too large to process safely.');
            }

            $totalXmlBytes += $size;
            if ($totalXmlBytes > self::MAX_TOTAL_XML_BYTES) {
                throw new RuntimeException('The Excel workbook expands beyond the safe processing limit.');
            }

            if ($compressedSize > 0 && $size > 1024 * 1024 && ($size / $compressedSize) > self::MAX_COMPRESSION_RATIO) {
                throw new RuntimeException('The Excel workbook has an unsafe compression ratio and cannot be processed.');
            }
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return [];
        }

        $xml = $this->parseXml($content, 'xl/sharedStrings.xml');
        $strings = [];
        foreach ($this->xpath($xml, '//m:si') as $item) {
            $parts = [];
            foreach ($this->xpath($item, './/m:t') as $text) {
                $parts[] = (string) $text;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function relationships(ZipArchive $zip): array
    {
        $xml = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
        $map = [];
        foreach ($xml->Relationship as $relationship) {
            $type = (string) ($relationship['Type'] ?? '');
            $targetMode = strtolower((string) ($relationship['TargetMode'] ?? ''));

            if ($targetMode === 'external') {
                continue;
            }

            if ($type !== '' && ! str_ends_with($type, '/worksheet')) {
                continue;
            }

            $map[(string) $relationship['Id']] = (string) $relationship['Target'];
        }

        return $map;
    }

    private function readRows(SimpleXMLElement $xml, array $sharedStrings, int $limit): array
    {
        $rows = [];
        foreach (array_slice($this->xpath($xml, '//m:sheetData/m:row'), 0, $limit) as $row) {
            $values = [];
            foreach ($this->xpath($row, './m:c') as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/^[A-Z]+/i', $reference, $match);
                $columnLetters = strtoupper($match[0] ?? 'A');
                $columnIndex = $this->columnIndex($columnLetters);
                if ($columnIndex >= self::MAX_COLUMNS) {
                    throw new RuntimeException('The Excel worksheet exceeds the '.self::MAX_COLUMNS.' column import limit.');
                }

                $type = (string) $cell['t'];
                $value = (string) ($cell->v ?? '');
                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $parts = [];
                    foreach ($this->xpath($cell, './/m:t') as $text) {
                        $parts[] = (string) $text;
                    }
                    $value = implode('', $parts);
                }
                $values[$columnIndex] = $value;
            }

            $max = $values ? max(array_keys($values)) : -1;
            $rows[] = $max >= 0 ? array_map(fn ($i) => $values[$i] ?? '', range(0, $max)) : [];
        }

        return $rows;
    }

    private function xpath(SimpleXMLElement $xml, string $expression): array
    {
        $xml->registerXPathNamespace('m', self::MAIN_NAMESPACE);

        return $xml->xpath($expression) ?: [];
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function xml(ZipArchive $zip, string $name): SimpleXMLElement
    {
        $stat = $zip->statName($name);
        if (! is_array($stat)) {
            throw new RuntimeException("Excel workbook is missing {$name}.");
        }

        if ((int) ($stat['size'] ?? 0) > self::MAX_XML_ENTRY_BYTES) {
            throw new RuntimeException("Excel workbook XML part {$name} is too large to process safely.");
        }

        $content = $zip->getFromName($name);
        if ($content === false) {
            throw new RuntimeException("Excel workbook is missing {$name}.");
        }

        return $this->parseXml($content, $name);
    }

    private function parseXml(string $content, string $name): SimpleXMLElement
    {
        if (stripos($content, '<!DOCTYPE') !== false || stripos($content, '<!ENTITY') !== false) {
            throw new RuntimeException("Excel workbook contains unsafe XML in {$name}.");
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if (! $xml) {
                throw new RuntimeException("Excel workbook contains invalid XML in {$name}.");
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function worksheetPath(string $target): string
    {
        $target = str_replace('\\', '/', trim($target));
        if ($target === '' || preg_match('#(^|/)\.\.(/|$)#', $target)) {
            throw new RuntimeException('The Excel workbook contains an unsafe worksheet relationship.');
        }

        $path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
        if (! str_starts_with($path, 'xl/worksheets/')) {
            throw new RuntimeException('The Excel workbook contains an unsupported worksheet relationship.');
        }

        return $path;
    }

    private function assertColumnLimit(array $headers, string $sheetName): void
    {
        if (count($headers) > self::MAX_COLUMNS) {
            throw new RuntimeException("Worksheet '{$sheetName}' contains more than ".self::MAX_COLUMNS.' columns and cannot be imported.');
        }
    }
}
