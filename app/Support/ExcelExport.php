<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** An .xlsx download with a bold header row, for the admin's "Download (Excel)" buttons. */
class ExcelExport
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): BinaryFileResponse
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'export-'.bin2hex(random_bytes(6)).'.xlsx';
        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headers, (new Style())->setFontBold()));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(fn ($v) => $v ?? '', $row)));
        }
        $writer->close();

        return response()->download($path, $filename.'-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }
}
