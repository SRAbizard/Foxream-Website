{{-- Passion Japan 履歴書 (Japanese CV), rendered with mPDF. The grid follows
     the agency's Word template: basic information, education & work
     history on page 1; licences, self-PR and notes on page 2.
     Data: App\Support\Rirekisho::data(). --}}
@php
    [$ty, $tm, $td] = \App\Support\Rirekisho::dateParts($cv['today']);
    $v = fn ($value) => filled($value) ? e($value) : '';
    foreach (\App\Support\Rirekisho::ROWS as $key => $rows) {
        $cv[$key] = array_pad($cv[$key], max($rows, count($cv[$key])), null);
    }
@endphp
<html>
<head>
<style>
    body { font-family: bizmincho; font-size: 10pt; color: #222; }
    .head td { vertical-align: bottom; padding: 0; }
    .title { font-size: 20pt; font-weight: bold; letter-spacing: 6pt; }
    .title-en { font-size: 8pt; font-weight: bold; color: #666; }
    .brand { font-size: 11pt; font-weight: bold; color: #9E1B32; text-align: right; }
    .asof { font-size: 8.5pt; color: #444; text-align: right; }
    .rule { border-bottom: 2pt solid #9E1B32; height: 1pt; margin: 4pt 0 9pt; }

    .sec { border-left: 4pt solid #9E1B32; padding: 1pt 0 1pt 5pt; margin: 6pt 0 0; font-size: 10.5pt; font-weight: bold; color: #9E1B32; }
    .sec span { font-size: 7pt; font-weight: normal; color: #888; }

    table.grid { width: 100%; border-collapse: collapse; border: 0.9pt solid #9E1B32; }
    table.grid td { border: 0.5pt solid #d9d9d9; padding: 3pt 5pt; vertical-align: middle; }
    table.grid td.l { background: #F6E8EA; color: #9E1B32; font-weight: bold; text-align: center; font-size: 8.5pt; }
    table.grid td.c { text-align: center; }
    table.grid td.small { font-size: 7pt; color: #777; text-align: center; }
    table.grid td.unit { font-size: 8pt; color: #888; text-align: center; }
    table.grid tr.hd td { background: #9E1B32; color: #fff; font-weight: bold; text-align: center; border-color: #b8475a; font-size: 8.5pt; }
    table.grid tr.band td { background: #F6E8EA; color: #9E1B32; font-weight: bold; text-align: center; letter-spacing: 8pt; }
    .name { font-size: 14pt; text-align: center; }
    .note { font-size: 7pt; color: #777; margin-top: 3pt; }
</style>
</head>
<body>

<table class="head" width="100%">
    <tr>
        <td><span class="title">履歴書</span>&nbsp;&nbsp;<span class="title-en">CURRICULUM VITAE</span></td>
        <td style="text-align: right;">
            <div class="brand">PASSION JAPAN INDONESIA</div>
            <div class="asof">{{ $ty }} 年 {{ $tm }} 月 {{ $td }} 日 現在</div>
        </td>
    </tr>
</table>
<div class="rule"></div>

{{-- 基本情報 --}}
<div class="sec">基本情報 <span>Personal Information</span></div>
<table class="grid">
    <tr style="height: 7.5mm;">
        <td class="l" width="12.2%">フリガナ</td>
        <td colspan="2" width="45.5%">{!! $v($cv['name_kana']) !!}</td>
        <td class="l" width="10%">性別</td>
        <td class="l" width="15.6%">国籍</td>
        <td rowspan="4" width="16.7%" class="c" style="padding: 2pt;">
            @if($cv['photo'])
                <img src="data:image/jpeg;base64,{{ base64_encode(\App\Support\Photo::passportJpeg($cv['photo']) ?? '') }}" style="width: 30mm; height: 40mm;">
            @else
                <span style="font-size: 7pt; color: #888;">写真貼付<br>縦4cm × 横3cm</span>
            @endif
        </td>
    </tr>
    <tr style="height: 13mm;">
        <td class="l">氏　名</td>
        <td colspan="2" class="name">{!! $v($cv['name']) !!}</td>
        <td class="c" style="font-size: 12pt; font-weight: bold;">{!! $v($cv['gender']) !!}</td>
        <td class="c">{!! $v($cv['nationality']) !!}</td>
    </tr>
    <tr style="height: 7.5mm;">
        <td class="l">生年月日</td>
        <td colspan="2">
            西暦&nbsp; {{ $cv['birth']?->year }} 年&nbsp; {{ $cv['birth']?->month }} 月&nbsp; {{ $cv['birth']?->day }} 日生&nbsp;&nbsp;（満 {{ $cv['age'] }} 歳）
        </td>
        <td colspan="2" class="small">※二重国籍の場合は両方記載</td>
    </tr>
    <tr style="height: 7.5mm;">
        <td class="l">応募職種</td>
        <td colspan="4">{!! $v($cv['desired_job']) !!}</td>
    </tr>
    <tr style="height: 7.5mm;">
        <td class="l">フリガナ</td>
        <td colspan="3">{!! $v($cv['address_kana']) !!}</td>
        <td colspan="2">TEL：{!! $v($cv['phone']) !!}</td>
    </tr>
    <tr style="height: 7.5mm;">
        <td class="l" rowspan="2">現住所</td>
        <td colspan="3" rowspan="2">{!! $v($cv['address']) !!}</td>
        <td colspan="2">E-mail：{!! $v($cv['email']) !!}</td>
    </tr>
    <tr style="height: 7.5mm;">
        <td colspan="2">LINE：{!! $v($cv['line']) !!}</td>
    </tr>
    <tr style="height: 7.5mm;">
        <td class="l">来日歴</td>
        <td colspan="5">
            留学：&nbsp;{{ $cv['visits'][0] }} 回&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            就職（ワーホリ含む）：&nbsp;{{ $cv['visits'][1] }} 回&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            その他（旅行等）：&nbsp;{{ $cv['visits'][2] }} 回
        </td>
    </tr>
    <tr style="height: 7.5mm;">
        <td class="l">日本語力</td>
        <td colspan="2">{!! $v($cv['japanese']) !!}</td>
        <td colspan="2" class="l">その他語学力</td>
        <td>{!! $v($cv['other_lang']) !!}</td>
    </tr>
    <tr style="height: 7.5mm;">
        <td class="l">健康状態</td>
        <td colspan="5">
            {{ $cv['healthy'] ? '■' : '□' }} 良好&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            {{ $cv['healthy'] ? '□' : '■' }} その他（詳細：{!! $cv['healthy'] ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : $v($cv['health_note']) !!}）
        </td>
    </tr>
</table>
<table class="grid" style="border-top: 0;">
    <tr style="height: 7.5mm;">
        <td class="l" width="12.2%">身　長</td>
        <td class="c" width="8.6%">{!! $v($cv['height']) !!}</td>
        <td class="unit" width="5%">cm</td>
        <td class="l" width="10%">体　重</td>
        <td class="c" width="8.7%">{!! $v($cv['weight']) !!}</td>
        <td class="unit" width="5%">kg</td>
        <td class="l" width="10%">宗　教</td>
        <td class="c" width="17.8%">{!! $v($cv['religion']) !!}</td>
        <td class="l" width="10%">血液型</td>
        <td class="c" width="7.3%">{!! $v($cv['blood']) !!}</td>
        <td class="unit" width="5.4%">型</td>
    </tr>
</table>

{{-- 学歴・職歴 --}}
<div class="sec" style="margin-top: 7pt;">学歴・職歴 <span>Education &amp; Work Experience</span></div>
<table class="grid">
    <tr class="hd">
        <td colspan="2" width="15%">入学・入社</td>
        <td colspan="2" width="15%">卒業・退社</td>
        <td rowspan="2">学校名・学部・学科 ／ 会社名・職種</td>
    </tr>
    <tr class="hd">
        <td width="8.5%">年</td><td width="6.5%">月</td><td width="8.5%">年</td><td width="6.5%">月</td>
    </tr>
    <tr class="band"><td colspan="5">学歴</td></tr>
    @foreach($cv['education'] as $row)
        <tr style="height: 7.3mm;">
            <td class="c" style="height: 7.3mm;">{{ $row['from_y'] ?? '' }}</td><td class="c">{{ $row['from_m'] ?? '' }}</td>
            <td class="c">{{ $row['to_y'] ?? '' }}</td><td class="c">{{ $row['to_m'] ?? '' }}</td>
            <td>{{ $row['text'] ?? '' }}</td>
        </tr>
    @endforeach
    <tr class="band"><td colspan="5">職歴</td></tr>
    @foreach($cv['work'] as $row)
        <tr style="height: 7.3mm;">
            <td class="c" style="height: 7.3mm;">{{ $row['from_y'] ?? '' }}</td><td class="c">{{ $row['from_m'] ?? '' }}</td>
            <td class="c">{{ $row['to_y'] ?? '' }}</td><td class="c">{{ $row['to_m'] ?? '' }}</td>
            <td>{{ $row['text'] ?? '' }}</td>
        </tr>
    @endforeach
</table>
<div class="note">※ 在学中・在職中の場合は「卒業・退社」欄に「現在」と記入してください。</div>

<pagebreak />

{{-- 免許・資格 --}}
<div class="sec">免許・資格 <span>Licenses &amp; Certifications</span></div>
<table class="grid">
    <tr class="hd"><td width="11%">取得年</td><td width="8%">月</td><td>免許・資格</td></tr>
    @foreach($cv['licenses'] as $row)
        <tr style="height: 7.3mm;">
            <td class="c" style="height: 7.3mm;">{{ $row['y'] ?? '' }}</td><td class="c">{{ $row['m'] ?? '' }}</td><td>{{ $row['text'] ?? '' }}</td>
        </tr>
    @endforeach
</table>

{{-- 自己PR --}}
<div class="sec" style="margin-top: 7pt;">自己PR <span>Self Introduction</span></div>
<table class="grid">
    <tr><td style="height: 55mm; vertical-align: top; padding: 6pt 8pt; line-height: 1.6;">{!! nl2br($v($cv['self_pr'])) !!}</td></tr>
</table>

{{-- その他 --}}
<div class="sec" style="margin-top: 7pt;">その他 <span>Others　（受け入れ側で配慮が必要な生活習慣などある場合　例）ベジタリアン、アレルギー等）</span></div>
<table class="grid">
    <tr><td style="height: 18mm; vertical-align: top; padding: 5pt 8pt; line-height: 1.6;">{!! nl2br($v($cv['other'])) !!}</td></tr>
</table>

</body>
</html>
