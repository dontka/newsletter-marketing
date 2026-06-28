<?php

class Xlsx
{
    public static function exportSubscribers(array $subscribers, string $filename): void
    {
        $rows = [];
        $rows[] = ['Email', 'Nom', 'Statut', 'Créé le'];

        foreach ($subscribers as $subscriber) {
            $rows[] = [
                $subscriber['email'] ?? '',
                $subscriber['name'] ?? '',
                $subscriber['status'] ?? '',
                $subscriber['created_at'] ?? '',
            ];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'subscribers');
        if ($tmpFile === false) {
            throw new RuntimeException('Impossible de créer un fichier XLSX temporaire.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible d’ouvrir le fichier XLSX temporaire.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($rows));
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }

    public static function importSubscribers(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Impossible d’ouvrir le fichier XLSX.');
        }

        $sheetPath = self::findFirstSheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        $sharedStrings = self::readSharedStrings($zip);
        $rows = self::parseSheetRows($sheetXml, $sharedStrings);
        $zip->close();

        return $rows;
    }

    private static function findFirstSheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml === false) {
            throw new RuntimeException('Structure XLSX invalide : workbook.xml introuvable.');
        }

        $workbook = simplexml_load_string($workbookXml);
        if ($workbook === false) {
            throw new RuntimeException('Impossible de lire workbook.xml.');
        }

        $sheets = $workbook->sheets->sheet;
        if (empty($sheets)) {
            throw new RuntimeException('Aucune feuille détectée dans le fichier XLSX.');
        }

        $relationshipId = (string) $sheets[0]['{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id'];
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            throw new RuntimeException('Structure XLSX invalide : workbook rels introuvable.');
        }

        $rels = simplexml_load_string($relsXml);
        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                $target = (string) $relationship['Target'];
                if (str_starts_with($target, 'worksheets/')) {
                    return 'xl/' . $target;
                }

                if (str_starts_with($target, '/')) {
                    return ltrim($target, '/');
                }

                return 'xl/' . ltrim($target, '/');
            }
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }

        if (in_array('xl/worksheets/sheet1.xml', $names, true)) {
            return 'xl/worksheets/sheet1.xml';
        }

        throw new RuntimeException('Feuille XLSX introuvable.');
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sharedStringsXml);
        if ($xml === false) {
            return [];
        }

        $values = [];
        foreach ($xml->si as $sharedString) {
            $parts = [];
            foreach ($sharedString->t as $textNode) {
                $parts[] = (string) $textNode;
            }
            $values[] = implode('', $parts);
        }

        return $values;
    }

    private static function parseSheetRows(?string $sheetXml, array $sharedStrings): array
    {
        if ($sheetXml === false || $sheetXml === '') {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false) {
            throw new RuntimeException('Impossible de lire les données de la feuille XLSX.');
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $values[] = self::parseCellValue($cell, $sharedStrings);
            }
            $rows[] = $values;
        }

        return $rows;
    }

    private static function parseCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $cellType = (string) $cell['t'];

        if ($cellType === 'inlineStr') {
            $text = [];
            foreach ($cell->is->t as $textNode) {
                $text[] = (string) $textNode;
            }
            return implode('', $text);
        }

        if ($cellType === 's') {
            $index = (int) (string) $cell->v;
            return $sharedStrings[$index] ?? '';
        }

        return (string) $cell->v;
    }

    private static function sheetXml(array $rows): string
    {
        $rowsXml = [];
        $columnCount = 0;
        foreach ($rows as $row) {
            $columnCount = max($columnCount, count($row));
        }

        foreach ($rows as $index => $row) {
            $cellsXml = [];
            foreach ($row as $colIndex => $value) {
                $cellRef = self::columnLetter($colIndex + 1) . ($index + 1);
                $escapedValue = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $styleIndex = $index === 0 ? 2 : 0;
                $cellsXml[] = '<c r="' . $cellRef . '" s="' . $styleIndex . '" t="inlineStr"><is><t>' . $escapedValue . '</t></is></c>';
            }

            $rowsXml[] = '<row r="' . ($index + 1) . '">' . implode('', $cellsXml) . '</row>';
        }

        $dimension = '<dimension ref="A1:' . self::columnLetter($columnCount) . (count($rows) + 1) . '"/>';
        $colsXml = '';
        for ($i = 0; $i < $columnCount; $i++) {
            $colsXml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="24" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            $dimension .
            '<cols>' . $colsXml . '</cols>' .
            '<sheetData>' . implode('', $rowsXml) . '</sheetData>' .
            '</worksheet>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Subscribers" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2">' .
            '<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/><family val="2"/></font>' .
            '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>' .
            '</fonts>' .
            '<fills count="3">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/><bgColor rgb="FF2563EB"/></patternFill></fill>' .
            '</fills>' .
            '<borders count="1"><border/></borders>' .
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
            '<cellXfs count="3">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' .
            '<xf numFmtId="0" fontId="0" fillId="1" borderId="0" xfId="0"/>' .
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>' .
            '</cellXfs>' .
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' .
            '</styleSheet>';
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
