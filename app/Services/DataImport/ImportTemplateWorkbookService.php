<?php

namespace App\Services\DataImport;

use App\Models\JobTitle;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use RuntimeException;
use ZipArchive;

class ImportTemplateWorkbookService
{
    private const MAX_TEMPLATE_ROWS = 1000;

    public function __construct(private readonly ImportMappingRegistry $registry) {}

    /**
     * Build an Insight-native XLSX import template and return its temporary path.
     */
    public function build(Workflow $workflow): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Excel template downloads require the PHP ZIP extension.');
        }

        $groups = collect($this->registry->groups($workflow))
            ->reject(fn (array $group) => $group['key'] === 'ignore')
            ->values()
            ->all();

        $columns = $this->columns($groups);
        $references = $this->references($columns);
        $definedNames = $this->definedNames($references);

        $path = tempnam(storage_path('app'), 'insight-import-template-');
        if ($path === false) {
            throw new RuntimeException('Insight could not create a temporary Excel template file.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('Insight could not create the Excel template workbook.');
        }

        try {
            $zip->addFromString('[Content_Types].xml', $this->contentTypes());
            $zip->addFromString('_rels/.rels', $this->rootRelationships());
            $zip->addFromString('docProps/app.xml', $this->appProperties());
            $zip->addFromString('docProps/core.xml', $this->coreProperties());
            $zip->addFromString('xl/workbook.xml', $this->workbook($definedNames));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
            $zip->addFromString('xl/styles.xml', $this->styles());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->templateSheet($columns, $references));
            $zip->addFromString('xl/worksheets/sheet2.xml', $this->instructionsSheet($workflow));
            $zip->addFromString('xl/worksheets/sheet3.xml', $this->metadataSheet($workflow, $columns));
            $zip->addFromString('xl/worksheets/sheet4.xml', $this->referenceSheet($references));
        } finally {
            $zip->close();
        }

        return $path;
    }

    private function columns(array $groups): array
    {
        $labelCounts = collect($groups)
            ->flatMap(fn (array $group) => collect($group['items'])->pluck('label'))
            ->countBy();

        $columns = [];

        foreach ($groups as $groupIndex => $group) {
            foreach ($group['items'] as $item) {
                $label = $item['label'];
                if (($labelCounts[$label] ?? 0) > 1) {
                    $label = $group['label'].' — '.$label;
                }

                $columns[] = [
                    'header' => $label,
                    'group_key' => $group['key'],
                    'group_label' => $group['label'],
                    'group_style' => 3 + ($groupIndex % 6),
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'type' => $item['type'] ?? 'text',
                    'meta' => $item['meta'] ?? [],
                ];
            }
        }

        return $columns;
    }

    private function references(array $columns): array
    {
        $lists = [];

        $lists['staffing_states'] = [
            'label' => 'Staffing States',
            'values' => ['Vacant', 'Selected', 'Filled', 'Departing', 'On-Hold'],
        ];
        $lists['candidate_statuses'] = [
            'label' => 'Candidate Statuses',
            'values' => ['submitted', 'selected', 'approved', 'assigned'],
        ];
        $lists['assignment_statuses'] = [
            'label' => 'Assignment Statuses',
            'values' => ['active', 'planned', 'ended'],
        ];
        $lists['boolean_values'] = [
            'label' => 'Boolean Values',
            'values' => ['Yes', 'No'],
        ];
        $lists['job_titles'] = [
            'label' => 'Job Titles',
            'values' => JobTitle::query()->where('is_active', true)->orderBy('name')->pluck('name')->filter()->values()->all(),
        ];
        $lists['project_managers'] = [
            'label' => 'Project Managers',
            'values' => User::query()->orderBy('name')->get(['name', 'email'])
                ->map(fn (User $user) => filled($user->email) ? $user->email : $user->name)
                ->filter()->unique()->values()->all(),
        ];
        $lists['organizations'] = [
            'label' => 'Organizations',
            'values' => Organization::query()->orderBy('name')->pluck('name')->filter()->unique()->values()->all(),
        ];

        foreach ($columns as $column) {
            $key = $column['key'];
            $meta = $column['meta'];

            if (str_starts_with($key, 'workflow.') && ($meta['property'] ?? null) === 'status') {
                $listKey = $this->listKey('workflow_'.$meta['step_code'].'_statuses');
                $lists[$listKey] = [
                    'label' => 'Workflow '.($meta['step_name'] ?? 'Workflow Step').' Statuses',
                    'values' => collect($meta['value_options'] ?? [])->pluck('value')->filter()->unique()->values()->all(),
                ];
            }

            if (str_starts_with($key, 'custom.') && ! empty($meta['options'])) {
                $listKey = $this->listKey('custom_'.($meta['entity_type'] ?? 'field').'_'.($meta['custom_field_key'] ?? md5($key)));
                $lists[$listKey] = [
                    'label' => ucfirst((string) ($meta['entity_type'] ?? 'Custom')).' '.$column['label'].' Options',
                    'values' => collect($meta['options'])->pluck('value')->filter(fn ($value) => $value !== null && $value !== '')->unique()->values()->all(),
                ];
            }
        }

        return collect($lists)
            ->filter(fn (array $list) => $list['values'] !== [])
            ->values()
            ->map(function (array $list, int $index) use ($lists) {
                return $list;
            })
            ->all();
    }

    private function referenceMap(array $references): array
    {
        $map = [];
        foreach ($references as $index => $reference) {
            $map[$this->listKey($reference['label'])] = $index;
        }
        return $map;
    }

    private function validationKey(array $column): ?string
    {
        $key = $column['key'];
        $meta = $column['meta'];

        return match (true) {
            $key === 'position.staffing_state' => $this->listKey('Staffing States'),
            $key === 'position.job_title' => $this->listKey('Job Titles'),
            $key === 'position.project_manager' => $this->listKey('Project Managers'),
            in_array($key, ['position.position_organization', 'position.sponsoring_organization', 'position.funding_organization'], true) => $this->listKey('Organizations'),
            $key === 'candidate.status' => $this->listKey('Candidate Statuses'),
            $key === 'assignment.assignment_status' => $this->listKey('Assignment Statuses'),
            $column['type'] === 'boolean' => $this->listKey('Boolean Values'),
            str_starts_with($key, 'workflow.') && ($meta['property'] ?? null) === 'status' => $this->listKey('Workflow '.($meta['step_name'] ?? 'Workflow Step').' Statuses'),
            str_starts_with($key, 'custom.') && ! empty($meta['options']) && $column['type'] !== 'checkbox' => $this->listKey(ucfirst((string) ($meta['entity_type'] ?? 'Custom')).' '.$column['label'].' Options'),
            default => null,
        };
    }

    private function definedNames(array $references): array
    {
        $names = [];
        foreach ($references as $index => $reference) {
            $column = $this->columnLetter($index + 1);
            $endRow = count($reference['values']) + 1;
            $names[] = [
                'name' => $this->listKey($reference['label']),
                'formula' => "'Reference Data'!\${$column}\$2:\${$column}\${$endRow}",
            ];
        }
        return $names;
    }

    private function templateSheet(array $columns, array $references): string
    {
        $lastColumn = $this->columnLetter(count($columns));
        $referenceMap = $this->referenceMap($references);

        $columnXml = '';
        foreach ($columns as $index => $column) {
            $width = min(max(mb_strlen($column['header']) + 3, 14), 34);
            $style = in_array($column['type'], ['date', 'datetime'], true) ? 2 : 0;
            $number = $index + 1;
            $columnXml .= '<col min="'.$number.'" max="'.$number.'" width="'.$width.'" customWidth="1"'.($style ? ' style="'.$style.'"' : '').'/>';
        }

        $headerCells = '';
        foreach ($columns as $index => $column) {
            $cell = $this->columnLetter($index + 1).'1';
            $headerCells .= $this->inlineStringCell($cell, $column['header'], $column['group_style']);
        }

        $validations = [];
        foreach ($columns as $index => $column) {
            $validationKey = $this->validationKey($column);
            if ($validationKey === null || ! array_key_exists($validationKey, $referenceMap)) continue;

            $letter = $this->columnLetter($index + 1);
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorStyle="stop" errorTitle="Invalid value" error="Choose a value from the current Insight reference list." sqref="'.$letter.'2:'.$letter.self::MAX_TEMPLATE_ROWS.'"><formula1>'.$this->xml($validationKey).'</formula1></dataValidation>';
        }

        $validationXml = $validations === [] ? '' : '<dataValidations count="'.count($validations).'">'.implode('', $validations).'</dataValidations>';

        return $this->xmlHeader().'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.$columnXml.'</cols>'
            .'<sheetData><row r="1" ht="30" customHeight="1">'.$headerCells.'</row></sheetData>'
            .'<autoFilter ref="A1:'.$lastColumn.'1"/>'
            .$validationXml
            .'<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }

    private function instructionsSheet(Workflow $workflow): string
    {
        $rows = [
            ['Insight Data Import Template', ''],
            ['Candidate Workflow', $workflow->name],
            ['Workflow Code', $workflow->code],
            ['Generated', now()->toIso8601String()],
            ['', ''],
            ['How to use this workbook', 'Enter one staffing record per row on the Import Template sheet. Do not rename or remove Row 1 headers unless you intend to remap them manually during import.'],
            ['Person matching', 'Insight matches People by Person Code first and email second. Names alone are never used for automatic matching.'],
            ['Existing records', 'Existing People, Positions, and Candidates are flagged for review before import.'],
            ['Blank cells', 'For updates, blank cells leave the current Insight value unchanged.'],
            ['N/A values', 'Blank, N/A, NA, none, null, - and — are treated as no imported value.'],
            ['Lookup values', 'Use the Reference Data sheet for current Job Titles, Project Managers, Organizations, statuses, workflow statuses, and custom-field options. Unknown values are flagged during validation.'],
            ['Workflow columns', 'Workflow columns were generated from the selected workflow and include only properties enabled on each active step.'],
            ['Custom fields', 'Active Position and Person custom fields are included automatically. Option-based fields use current configured choices where Excel supports a single-selection dropdown.'],
            ['Checkbox custom fields', 'Checkbox/multi-select custom fields list their current valid options on the Reference Data sheet for reference during data entry.'],
            ['Dates', 'Enter recognizable Excel dates. Insight normalizes supported Excel/date values during validation.'],
        ];

        return $this->simpleSheet($rows, [24, 110], true);
    }

    private function metadataSheet(Workflow $workflow, array $columns): string
    {
        $rows = [
            ['Key', 'Value', 'Destination Key'],
            ['template_version', '1', ''],
            ['generated_by', 'Insight Data Import', ''],
            ['workflow_name', $workflow->name, ''],
            ['workflow_code', $workflow->code, ''],
            ['generated_at', now()->toIso8601String(), ''],
            ['', '', ''],
            ['Column Header', 'Group', 'Destination Key'],
        ];

        foreach ($columns as $column) {
            $rows[] = [$column['header'], $column['group_label'], $column['key']];
        }

        return $this->simpleSheet($rows, [38, 34, 64], true);
    }

    private function referenceSheet(array $references): string
    {
        $referenceCounts = array_map(fn (array $list) => count($list['values']), $references);
        $maxRows = $referenceCounts === [] ? 0 : max($referenceCounts);
        $rows = [];
        $rows[] = array_map(fn (array $list) => $list['label'], $references);

        for ($row = 0; $row < $maxRows; $row++) {
            $rows[] = array_map(fn (array $list) => $list['values'][$row] ?? '', $references);
        }

        return $this->simpleSheet($rows, array_fill(0, max(count($references), 1), 28), true);
    }

    private function simpleSheet(array $rows, array $widths, bool $freezeTop): string
    {
        $cols = '';
        foreach ($widths as $index => $width) {
            $number = $index + 1;
            $cols .= '<col min="'.$number.'" max="'.$number.'" width="'.$width.'" customWidth="1"/>';
        }

        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $r = $rowIndex + 1;
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                if ($value === '') continue;
                $style = $r === 1 ? 3 : 0;
                $cells .= $this->inlineStringCell($this->columnLetter($columnIndex + 1).$r, (string) $value, $style);
            }
            $sheetRows .= '<row r="'.$r.'">'.$cells.'</row>';
        }

        $views = $freezeTop
            ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            : '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';

        return $this->xmlHeader().'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$views.'<cols>'.$cols.'</cols><sheetData>'.$sheetRows.'</sheetData>'
            .'<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }

    private function workbook(array $definedNames): string
    {
        $defined = '';
        if ($definedNames !== []) {
            $defined = '<definedNames>'.collect($definedNames)->map(
                fn (array $name) => '<definedName name="'.$this->xml($name['name']).'">'.$this->xml($name['formula']).'</definedName>'
            )->implode('').'</definedNames>';
        }

        return $this->xmlHeader().'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView/></bookViews><sheets>'
            .'<sheet name="Import Template" sheetId="1" r:id="rId1"/>'
            .'<sheet name="Instructions" sheetId="2" r:id="rId2"/>'
            .'<sheet name="Insight Metadata" sheetId="3" r:id="rId3"/>'
            .'<sheet name="Reference Data" sheetId="4" r:id="rId4"/>'
            .'</sheets>'.$defined.'<calcPr calcId="191029"/></workbook>';
    }

    private function workbookRelationships(): string
    {
        return $this->xmlHeader().'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>'
            .'<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/>'
            .'<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function contentTypes(): string
    {
        return $this->xmlHeader().'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return $this->xmlHeader().'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        $fills = [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FF334155"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF3E8FF"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFFFE4E6"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFE0F2FE"/><bgColor indexed="64"/></patternFill></fill>',
        ];

        $cellXfs = [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>',
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>',
            '<xf numFmtId="14" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>',
        ];

        for ($fillId = 3; $fillId <= 8; $fillId++) {
            $cellXfs[] = '<xf numFmtId="0" fontId="1" fillId="'.$fillId.'" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>';
        }

        return $this->xmlHeader().'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="0"/><fonts count="2"><font><sz val="11"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font></fonts>'
            .'<fills count="'.count($fills).'">'.implode('', $fills).'</fills>'
            .'<borders count="2"><border/><border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.count($cellXfs).'">'.implode('', $cellXfs).'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function appProperties(): string
    {
        return $this->xmlHeader().'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Insight</Application></Properties>';
    }

    private function coreProperties(): string
    {
        $created = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return $this->xmlHeader().'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Insight</dc:creator><cp:lastModifiedBy>Insight</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:modified><dc:title>Insight Data Import Template</dc:title></cp:coreProperties>';
    }

    private function inlineStringCell(string $reference, string $value, int $style = 0): string
    {
        return '<c r="'.$reference.'" t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
    }

    private function columnLetter(int $number): string
    {
        $letters = '';
        while ($number > 0) {
            $number--;
            $letters = chr(65 + ($number % 26)).$letters;
            $number = intdiv($number, 26);
        }
        return $letters;
    }

    private function listKey(string $value): string
    {
        $key = preg_replace('/[^A-Za-z0-9_]/', '_', $value) ?: 'List';
        $key = trim(preg_replace('/_+/', '_', $key) ?: $key, '_');
        if ($key === '' || ctype_digit($key[0])) $key = 'INSIGHT_'.$key;
        return substr($key, 0, 220);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlHeader(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    }
}
