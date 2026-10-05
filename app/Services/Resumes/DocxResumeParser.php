<?php

namespace App\Services\Resumes;

use DOMDocument;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class DocxResumeParser
{
    public function parse(string $path, array $mappings, array $columnOrders = []): array
    {
        if (! class_exists(ZipArchive::class)) {
            $this->fail('The PHP ZIP extension is required for DOCX imports.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->fail('This file is not a readable DOCX document.');
        }
        try {
            $info = $zip->statName('word/document.xml');
            if (! $info || $info['size'] > 8 * 1024 * 1024) {
                $this->fail('Missing or oversized Word document content.');
            }
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }
        if (! is_string($xml) || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            $this->fail('Unsupported Word XML content.');
        }
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $dom->loadXML($xml, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $loaded) {
            $this->fail('Invalid Word document XML.');
        }
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $values = [];
        $warnings = [];
        foreach ($xp->query('//w:tbl/w:tr') as $row) {
            $cells = $xp->query('./w:tc', $row);
            if ($cells->length < 2) {
                continue;
            }
            $label = $this->normalize($this->cell($xp, $cells->item(0)));
            foreach ($mappings as $field => $aliases) {
                foreach (explode('|', $aliases) as $alias) {
                    if (trim($alias) === '') {
                        continue;
                    }
                    if ($label === $this->normalize($alias)) {
                        $text = $this->cell($xp, $cells->item(1));
                        $values[$field] = isset($values[$field]) ? $values[$field]."\n".$text : $text;
                        break;
                    }
                }
            }
        }
        if (! $values) {
            $this->fail('No configured labels were found. Select another format or check the two-column table labels.');
        }
        $data = array_fill_keys(ResumeFields::SCALARS, '');
        foreach ($data as $field => $_) {
            $data[$field] = $values[$field] ?? '';
        }
        if ($data['requirement_id'] === '' && str_contains($data['labor_category'], ',')) {
            $parts = explode(',', $data['labor_category']);
            $data['requirement_id'] = trim(array_pop($parts));
            $data['labor_category'] = trim(implode(',', $parts));
            $warnings[] = 'Requirement ID was inferred from the final comma-separated value in Proposed Labor Category. Confirm both fields.';
        }
        $entries = [];
        foreach (ResumeFields::REPEATING as $section => $columns) {
            $importColumns = $columnOrders[$section] ?? $columns;
            $entries[$section] = [];
            $sectionText = $values[$section] ?? '';
            if ($section === 'languages') {
                $sectionText = preg_replace('/\r?\n\s*Evaluation Date:\s*/i', ' Evaluation Date: ', $sectionText);
            }
            foreach (preg_split('/\r?\n/', $sectionText) as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $entry = array_fill_keys($columns, '');
                if ($section === 'languages' && preg_match('/^(.*?)\s*\((.*?)\/(.*?)\)\s*(?:Evaluation Date:\s*)?(.*)$/i', $line, $m)) {
                    $entry = ['language' => trim($m[1]), 'reading_score' => trim($m[2]), 'writing_score' => trim($m[3]), 'evaluation_date' => trim($m[4])];
                } elseif ($section === 'technologies') {
                    foreach (preg_split('/[,;]+/', $line) as $skill) {
                        if (trim($skill) !== '') {
                            $entries[$section][] = ['technology' => trim($skill)];
                        }
                    }

                    continue;
                } else {
                    $parts = str_getcsv($line, ',', '"', '');
                    foreach ($importColumns as $i => $column) {
                        $entry[$column] = trim($parts[$i] ?? '');
                    }
                    if (count($parts) !== count($columns)) {
                        $warnings[] = "Review $section: an entry does not match the expected columns. Original: $line";
                    }
                }
                $entries[$section][] = $entry;
            }
        }
        foreach ($mappings as $field => $aliases) {
            if (trim($aliases) !== '' && ! isset($values[$field])) {
                $warnings[] = "Label not found: $aliases ($field).";
            }
        }

        return ['data' => $data, 'entries' => $entries, 'warnings' => array_values(array_unique($warnings))];
    }

    private function cell(DOMXPath $xp, \DOMNode $cell): string
    {
        $lines = [];
        foreach ($xp->query('./w:p', $cell) as $paragraph) {
            $text = '';
            foreach ($xp->query('.//w:t | .//w:br | .//w:tab', $paragraph) as $node) {
                $text .= $node->localName === 't' ? $node->textContent : ($node->localName === 'br' ? "\n" : ' ');
            }
            if (trim($text) !== '') {
                $lines[] = trim($text);
            }
        }

        return implode("\n", $lines);
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/[\s:"\'“”]+/u', ' ', $text)));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
