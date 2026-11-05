<?php

namespace App\Support;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Fills the agency's own Word template (resources/templates/rirekisho.docx)
 * with a student's data, so fonts, sizes and layout stay exactly as
 * designed. Each value is written the way it is typed in Word: a run that
 * copies the paragraph's own formatting. Dropdowns (性別, 宗教, 血液型) and
 * the 健康状態 checkboxes stay real Word controls.
 */
class RirekishoDocx
{
    private const W   = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const W14 = 'http://schemas.microsoft.com/office/word/2010/wordml';
    private const R   = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private DOMDocument $dom;
    private DOMXPath $xp;
    /** @var array<int, DOMElement> */
    private array $tables = [];

    public static function template(): string
    {
        return resource_path('templates/rirekisho.docx');
    }

    /** Returns the filled .docx as a string. */
    public static function render(User $user, ?string $desiredJob = null): string
    {
        return (new self())->build(Rirekisho::data($user, $desiredJob));
    }

    public function build(array $cv): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'rk');
        copy(self::template(), $tmp);
        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            throw new RuntimeException('Cannot open the 履歴書 template.');
        }

        try {
            $this->dom = new DOMDocument();
            $this->dom->preserveWhiteSpace = true;
            $this->dom->loadXML($zip->getFromName('word/document.xml'));
            $this->xp = new DOMXPath($this->dom);
            $this->xp->registerNamespace('w', self::W);
            $this->xp->registerNamespace('w14', self::W14);
            foreach ($this->xp->query('/w:document/w:body/w:tbl') as $tbl) {
                $this->tables[] = $tbl;
            }

            $this->fillHeader($cv);
            $this->fillBasics($cv);
            $this->fillHistory($cv);
            $this->fillLicenses($cv);
            $this->setText($this->cell(5, 0, 0), $cv['self_pr']);
            $this->setText($this->cell(6, 0, 0), $cv['other']);

            if ($cv['photo'] && ($jpeg = Photo::passportJpeg($cv['photo']))) {
                $this->insertPhoto($zip, $jpeg);
            }

            $zip->addFromString('word/document.xml', $this->brand($this->dom->saveXML()));
            // The template names the company in its footer and file properties too.
            foreach (['word/footer1.xml', 'docProps/core.xml', 'docProps/app.xml'] as $part) {
                if (($xml = $zip->getFromName($part)) !== false) {
                    $zip->addFromString($part, $this->brand($xml));
                }
            }
            $zip->close();

            return file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    // ── Sections ────────────────────────────────────────────────────────

    private function fillHeader(array $cv): void
    {
        [$y, $m, $d] = Rirekisho::dateParts($cv['today']);
        // Second paragraph of the right-hand header cell: "2026年　　月　　日 現在"
        $p = $this->xp->query('w:p', $this->cell(1, 0, 1))->item(1);
        $this->replaceParagraphText($p, "{$y}年　 {$m}月　 {$d}日 現在");
    }

    private function fillBasics(array $cv): void
    {
        $this->setText($this->cell(2, 0, 1), $cv['name_kana']);
        $this->setText($this->cell(2, 1, 1), $cv['name']);
        $this->center($this->cell(2, 1, 1));
        $this->setDropdown($this->cell(2, 1, 2), $cv['gender']);
        // Already reads インドネシア in the template; keep that run's formatting.
        $this->replaceParagraphText($this->firstParagraph($this->cell(2, 1, 3)), $cv['nationality']);

        if ($cv['birth']) {
            $b = $cv['birth'];
            $this->replaceParagraphText($this->firstParagraph($this->cell(2, 2, 1)),
                "西暦　{$b->year}年　{$b->month}月　{$b->day}日生　（満 {$cv['age']} 歳）");
        }

        $this->setText($this->cell(2, 3, 1), $cv['desired_job']);
        $this->setText($this->cell(2, 4, 1), $cv['address_kana']);
        $this->appendText($this->cell(2, 4, 2), $cv['phone']);
        $this->setText($this->cell(2, 5, 1), $cv['address']);
        $this->appendText($this->cell(2, 5, 2), $cv['email']);
        $this->appendText($this->cell(2, 6, 2), $cv['line']);

        [$study, $work, $other] = $cv['visits'];
        $this->replaceParagraphText($this->firstParagraph($this->cell(2, 7, 1)),
            "留学：　{$study} 回　　　　就職（ワーホリ含む）：　{$work} 回　　　　その他（旅行等）：　{$other} 回");

        $this->setText($this->cell(2, 8, 1), $cv['japanese']);
        $this->setText($this->cell(2, 8, 3), $cv['other_lang']);

        $this->setHealth($this->cell(2, 9, 1), $cv['healthy'], $cv['health_note']);

        $this->setText($this->cell(2, 10, 1), $cv['height']);
        $this->setText($this->cell(2, 10, 4), $cv['weight']);
        $this->setDropdown($this->cell(2, 10, 7), $cv['religion']);
        $this->setDropdown($this->cell(2, 10, 9), $cv['blood']);
    }

    /** 学歴 rows 3–6 and 職歴 rows 8–17 of table 3; work first so row indices stay valid. */
    private function fillHistory(array $cv): void
    {
        $fill = function (DOMElement $tr, ?array $row) {
            $cells = $this->xp->query('w:tc', $tr);
            foreach (['from_y', 'from_m', 'to_y', 'to_m', 'text'] as $i => $key) {
                $this->setText($cells->item($i), $row[$key] ?? null);
            }
        };
        $this->fillRows(3, 8, Rirekisho::ROWS['work'], $cv['work'], $fill);
        $this->fillRows(3, 3, Rirekisho::ROWS['education'], $cv['education'], $fill);
    }

    private function fillLicenses(array $cv): void
    {
        $this->fillRows(4, 1, Rirekisho::ROWS['licenses'], $cv['licenses'], function (DOMElement $tr, ?array $row) {
            $cells = $this->xp->query('w:tc', $tr);
            foreach (['y', 'm', 'text'] as $i => $key) {
                $this->setText($cells->item($i), $row[$key] ?? null);
            }
        });
    }

    // ── Building blocks ─────────────────────────────────────────────────

    private function cell(int $table, int $row, int $col): DOMElement
    {
        $tr = $this->xp->query('w:tr', $this->tables[$table - 1])->item($row);
        $tc = $tr ? $this->xp->query('w:tc', $tr)->item($col) : null;
        if (! $tc instanceof DOMElement) {
            throw new RuntimeException("履歴書 template cell {$table}.{$row}.{$col} not found.");
        }

        return $tc;
    }

    private function firstParagraph(DOMElement $tc): DOMElement
    {
        return $this->xp->query('w:p', $tc)->item(0);
    }

    /**
     * Replace the cell's first paragraph content with $text, using the
     * paragraph mark's formatting (what Word does when you type into it).
     */
    private function setText(DOMElement $tc, mixed $text): void
    {
        $p = $this->firstParagraph($tc);
        foreach ($this->xp->query('w:r | w:sdt | w:hyperlink', $p) as $old) {
            $p->removeChild($old);
        }
        if ($text === null || $text === '') {
            return;
        }
        $p->appendChild($this->run((string) $text, $this->xp->query('w:pPr/w:rPr', $p)->item(0)));
    }

    /** Centre the cell's first paragraph (w:jc sits before these in w:pPr's schema order). */
    private function center(DOMElement $tc): void
    {
        $p   = $this->firstParagraph($tc);
        $pPr = $this->xp->query('w:pPr', $p)->item(0)
            ?? $p->insertBefore($this->dom->createElementNS(self::W, 'w:pPr'), $p->firstChild);
        $jc  = $this->xp->query('w:jc', $pPr)->item(0);
        if (! $jc) {
            $next = $this->xp->query('w:textDirection | w:textAlignment | w:textboxTightWrap | w:outlineLvl | w:divId | w:cnfStyle | w:rPr | w:sectPr | w:pPrChange', $pPr)->item(0);
            $jc = $pPr->insertBefore($this->dom->createElementNS(self::W, 'w:jc'), $next);
        }
        $jc->setAttributeNS(self::W, 'w:val', 'center');
    }

    /** Add a value after a label in the same paragraph ("TEL：" + number), in the label's formatting. */
    private function appendText(DOMElement $tc, mixed $text): void
    {
        if ($text === null || $text === '') {
            return;
        }
        $p     = $this->firstParagraph($tc);
        $label = $this->xp->query('w:r[last()]/w:rPr', $p)->item(0);
        $p->appendChild($this->run((string) $text, $label));
    }

    /** Put all of $text into the paragraph's first run, keeping that run's formatting. */
    private function replaceParagraphText(DOMElement $p, string $text): void
    {
        $runs  = $this->xp->query('w:r', $p);
        $first = $runs->item(0);
        $rPr   = $first ? $this->xp->query('w:rPr', $first)->item(0) : null;
        foreach ($runs as $r) {
            $p->removeChild($r);
        }
        $p->appendChild($this->run($text, $rPr ?? $this->xp->query('w:pPr/w:rPr', $p)->item(0)));
    }

    /** Choose a dropdown item by its display text (ignored if it is not one of the options). */
    private function setDropdown(DOMElement $tc, ?string $value): void
    {
        $sdt = $this->xp->query('.//w:sdt', $tc)->item(0);
        if (! $sdt || blank($value)) {
            return;
        }
        $options = [];
        foreach ($this->xp->query('w:sdtPr/w:dropDownList/w:listItem', $sdt) as $item) {
            $options[] = $item->getAttributeNS(self::W, 'displayText');
        }
        if (! in_array($value, $options, true)) {
            return;
        }
        foreach ($this->xp->query('w:sdtPr/w:showingPlcHdr', $sdt) as $ph) {
            $ph->parentNode->removeChild($ph);
        }
        $t = $this->xp->query('w:sdtContent//w:t', $sdt)->item(0);
        $t->nodeValue = '';
        $t->appendChild($this->dom->createTextNode($value));
    }

    /** 健康状態: tick 良好, or tick その他 and write the details. */
    private function setHealth(DOMElement $tc, bool $healthy, ?string $note): void
    {
        $boxes = $this->xp->query('.//w:sdt[w:sdtPr/w14:checkbox]', $tc);
        $this->setCheckbox($boxes->item(0), $healthy);
        $this->setCheckbox($boxes->item(1), ! $healthy);

        if (! $healthy && filled($note)) {
            foreach ($this->xp->query('.//w:r/w:t', $tc) as $t) {
                if (str_contains($t->nodeValue, '詳細：')) {
                    $t->nodeValue = '';
                    $t->appendChild($this->dom->createTextNode(' その他（詳細：'.$note.'）'));
                }
            }
        }
    }

    private function setCheckbox(?DOMElement $sdt, bool $checked): void
    {
        if (! $sdt) {
            return;
        }
        $this->xp->query('w:sdtPr/w14:checkbox/w14:checked', $sdt)->item(0)
            ?->setAttributeNS(self::W14, 'w14:val', $checked ? '1' : '0');
        $r = $this->xp->query('w:sdtContent/w:r', $sdt)->item(0);
        if ($checked) {
            // Word shows the checked glyph in the checkbox's own font (MS Gothic).
            $fonts = $this->xp->query('w:rPr/w:rFonts', $r)->item(0);
            foreach (['ascii', 'eastAsia', 'hAnsi'] as $a) {
                $fonts?->setAttributeNS(self::W, "w:{$a}", 'MS Gothic');
            }
            $fonts?->setAttributeNS(self::W, 'w:hint', 'eastAsia');
            $fonts?->removeAttributeNS(self::W, 'cs');
        }
        $t = $this->xp->query('w:t', $r)->item(0);
        $t->nodeValue = $checked ? '☑' : '☐';
    }

    /**
     * Fill a block of template rows; when there are more entries than
     * rows, copy the block's last (empty) row as often as needed.
     */
    private function fillRows(int $table, int $first, int $count, array $items, callable $fill): void
    {
        $rows = iterator_to_array($this->xp->query('w:tr', $this->tables[$table - 1]));
        $block = array_slice($rows, $first, $count);
        $last = end($block);
        for ($i = $count; $i < count($items); $i++) {
            $copy = $last->cloneNode(true);
            $last->parentNode->insertBefore($copy, $last->nextSibling);
            $block[] = $last = $copy;
        }
        foreach ($block as $i => $tr) {
            $fill($tr, $items[$i] ?? null);
        }
    }

    /** A run with the given formatting; newlines become line breaks. */
    private function run(string $text, ?DOMElement $rPr): DOMElement
    {
        $r = $this->dom->createElementNS(self::W, 'w:r');
        if ($rPr) {
            $copy = $rPr->cloneNode(true);
            // Paragraph-mark only properties don't belong on a run.
            foreach ($this->xp->query('w:ins | w:del | w:moveFrom | w:moveTo', $copy) as $n) {
                $copy->removeChild($n);
            }
            $r->appendChild($copy);
        }
        foreach (preg_split('/\R/u', $text) as $i => $line) {
            if ($i > 0) {
                $r->appendChild($this->dom->createElementNS(self::W, 'w:br'));
            }
            $t = $this->dom->createElementNS(self::W, 'w:t');
            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            $t->appendChild($this->dom->createTextNode($line));
            $r->appendChild($t);
        }

        return $r;
    }

    /** Replace the 写真貼付 placeholder with the photo at the template's 2.7 × 3.6 cm size. */
    /** The template writes {BRAND} where the brand's name goes. */
    private function brand(string $xml): string
    {
        return str_replace('{BRAND}', htmlspecialchars(mb_strtoupper(Brand::name()), ENT_XML1), $xml);
    }

    private function insertPhoto(ZipArchive $zip, string $jpeg): void
    {
        $zip->addFromString('word/media/rirekisho-photo.jpeg', $jpeg);

        $rels = new DOMDocument();
        $rels->loadXML($zip->getFromName('word/_rels/document.xml.rels'));
        $rel = $rels->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
        $rel->setAttribute('Id', 'rIdRirekishoPhoto');
        $rel->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
        $rel->setAttribute('Target', 'media/rirekisho-photo.jpeg');
        $rels->documentElement->appendChild($rel);
        $zip->addFromString('word/_rels/document.xml.rels', $rels->saveXML());

        $types = $zip->getFromName('[Content_Types].xml');
        if (! str_contains($types, 'Extension="jpeg"')) {
            $types = str_replace('<Default Extension="xml"', '<Default Extension="jpeg" ContentType="image/jpeg"/><Default Extension="xml"', $types);
            $zip->addFromString('[Content_Types].xml', $types);
        }

        $tc = $this->cell(2, 0, 4);
        $paragraphs = iterator_to_array($this->xp->query('w:p', $tc));
        foreach (array_slice($paragraphs, 1) as $extra) {
            $tc->removeChild($extra);
        }
        $p = $paragraphs[0];
        foreach ($this->xp->query('w:r', $p) as $old) {
            $p->removeChild($old);
        }

        [$cx, $cy] = [972185, 1296670]; // EMU, as in the filled-in example
        $frag = $this->dom->createDocumentFragment();
        $frag->appendXML(
            '<w:r xmlns:w="'.self::W.'"><w:rPr><w:noProof/></w:rPr>'
            .'<w:drawing><wp:inline xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" distT="0" distB="0" distL="0" distR="0">'
            ."<wp:extent cx=\"{$cx}\" cy=\"{$cy}\"/><wp:effectExtent l=\"0\" t=\"0\" r=\"0\" b=\"0\"/>"
            .'<wp:docPr id="501" name="Photo"/>'
            .'<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:nvPicPr><pic:cNvPr id="501" name="Photo"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip xmlns:r="'.self::R.'" r:embed="rIdRirekishoPhoto"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            ."<pic:spPr><a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext cx=\"{$cx}\" cy=\"{$cy}\"/></a:xfrm><a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom></pic:spPr>"
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>'
        );
        $p->appendChild($frag);
    }
}
