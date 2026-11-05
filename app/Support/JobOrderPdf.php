<?php

namespace App\Support;

use App\Models\JobVacancy;

/** Renders a vacancy's Job Order as the candidate-facing PDF. */
class JobOrderPdf
{
    public static function render(JobVacancy $vacancy): string
    {
        $vacancy->loadMissing(['company', 'visaCategory', 'jobCategory']);

        $mpdf = Pdf::make([
            'margin_left'   => 16,
            'margin_right'  => 16,
            'margin_top'    => 30,
            'margin_bottom' => 20,
            'margin_header' => 9,
            'margin_footer' => 8,
        ]);
        $title = JobOrder::documentTitle($vacancy);
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor(\App\Support\Brand::company());

        $code = trim((string) $vacancy->job_order_code);
        $date = ($vacancy->published_at ?? now())->locale('id')->translatedFormat('j F Y');
        $logo = resource_path('images/logo-pdf.png');
        $mpdf->SetHTMLHeader('<table width="100%" style="font-family: freesans; border-bottom: 1pt solid #1D1730; padding-bottom: 3pt;"><tr>'
            .'<td><img src="'.$logo.'" style="height: 13mm;"></td>'
            .'<td style="text-align: right; vertical-align: bottom;">'
            .'<span style="font-size: 13pt; font-weight: bold; color: #B32510;">JOB ORDER</span> '
            .'<span style="font-family: bizud; font-size: 9pt; color: #7d7062;">求人票</span><br>'
            .'<span style="font-size: 7.5pt; color: #7d7062;">'.e(trim(($code !== '' ? $code.' · ' : '').$date)).'</span>'
            .'</td></tr></table>');
        $mpdf->SetHTMLFooter('<table width="100%" style="font-family: freesans; font-size: 7pt; color: #7d7062; border-top: 0.5pt solid #D8C9B2; padding-top: 3pt;"><tr>'
            .'<td>'.\App\Support\Brand::company().' · Isi dapat berubah sesuai keputusan perusahaan penerima.</td>'
            .'<td style="text-align: right;">{PAGENO} / {nbpg}</td></tr></table>');

        $titleId = $vacancy->t('title', 'id');
        $titleJa = data_get($vacancy->title, 'ja');

        $mpdf->WriteHTML(view('pdf.job-order', [
            'jobTitle'   => $titleId,
            'jobTitleJa' => filled($titleJa) && $titleJa !== $titleId ? $titleJa : null,
            'subtitle'   => collect([JobOrder::companyName($vacancy), $vacancy->location_city ? $vacancy->location_display : null])->filter()->join(' · '),
            'chips'      => collect([$vacancy->visaCategory?->t('name', 'id'), $vacancy->jobCategory?->t('name', 'id')])->filter()->all(),
            'highlights' => JobOrder::highlights($vacancy),
            'sections'   => JobOrder::forDocument($vacancy),
        ])->render());

        return $mpdf->OutputBinaryData();
    }

    public static function filename(JobVacancy $vacancy): string
    {
        return JobOrder::documentTitle($vacancy).'.pdf';
    }
}
