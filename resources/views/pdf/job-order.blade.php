{{-- Job Order PDF for candidates (mPDF), in the site's colours: ink
     title band, red accents, washi labels. Latin text in FreeSans,
     Japanese in BIZ UD Gothic. Data: App\Support\JobOrder; header and
     footer are set in App\Support\JobOrderPdf. --}}
<html>
<head>
<style>
    body { font-family: freesans; font-size: 9.5pt; color: #1D1730; line-height: 1.4; }
    .jp { font-family: bizud; }

    .hero { background-color: #1D1730; color: #FFFAF2; padding: 12pt 16pt 14pt; }
    table.chips { border-collapse: separate; border-spacing: 0; margin-bottom: 8pt; }
    table.chips td { background-color: #FFFAF2; color: #1D1730; font-size: 7.5pt; font-weight: bold; padding: 2.5pt 7pt; }
    table.chips td.first { background-color: #F1AF2E; }
    table.chips td.gap { background-color: #1D1730; padding: 0; width: 4pt; }
    .hero-title { font-size: 22pt; font-weight: bold; line-height: 1.15; }
    .hero-jp { font-size: 10pt; color: #F5C3C8; margin-top: 3pt; }
    .hero-sub { font-size: 9.5pt; color: #E8DAC3; margin-top: 8pt; }
    .stripe { background-color: #B32510; height: 4pt; font-size: 1pt; }

    table.facts { width: 100%; border-collapse: separate; border-spacing: 5pt 0; margin: 11pt -5pt 2pt; }
    table.facts td { border: 1pt solid #1D1730; border-top: 3pt solid #B32510; background-color: #FFFAF2; padding: 6pt 8pt 7pt; vertical-align: top; }
    .fact-label { font-size: 6.8pt; color: #7d7062; font-weight: bold; }
    .fact-main { font-size: 11pt; font-weight: bold; color: #1D1730; margin-top: 3pt; line-height: 1.2; }
    .fact-unit { font-size: 8pt; color: #7d7062; }

    {{-- The section title is the table's header row and each section is kept on one page, so a title never sits alone at the bottom. --}}
    table.sec { width: 100%; border-collapse: collapse; margin-top: 14pt; }
    table.sec th { text-align: left; padding: 0 0 5pt; font-weight: normal; }
    .mark { color: #B32510; font-size: 10pt; }
    .name { font-size: 11.5pt; font-weight: bold; }
    .name-jp { font-size: 8pt; color: #7d7062; }
    td.label, td.value { padding: 5.5pt 8pt 6pt; vertical-align: top; border-bottom: 0.5pt solid #D8C9B2; }
    td.label { width: 28%; background-color: #F5EDE0; font-weight: bold; border-left: 1pt solid #1D1730; border-right: 0.5pt solid #D8C9B2; }
    td.value { border-right: 1pt solid #1D1730; }
    td.top { border-top: 1pt solid #1D1730; }
    .label-jp { font-size: 7pt; font-weight: normal; color: #7d7062; }

    {{-- Bullet points as a two-column table, so wrapped lines stay indented under their text. --}}
    table.list { border-collapse: collapse; }
    table.list td { padding: 0 0 1.5pt; vertical-align: top; }
    table.list td.dot { width: 9pt; color: #B32510; font-weight: bold; }
    p.line { margin: 0 0 1.5pt; }
</style>
</head>
<body>
    <div class="hero">
        @if($chips)
            <table class="chips"><tr>
                @foreach($chips as $chip)
                    @if(! $loop->first)<td class="gap">&nbsp;</td>@endif
                    <td class="{{ $loop->first ? 'first' : '' }}">{{ $chip }}</td>
                @endforeach
            </tr></table>
        @endif
        <div class="hero-title">{{ $jobTitle }}</div>
        @if($jobTitleJa)
            <div class="hero-jp jp">{{ $jobTitleJa }}</div>
        @endif
        @if($subtitle)
            <div class="hero-sub">{{ $subtitle }}</div>
        @endif
    </div>
    <div class="stripe">&nbsp;</div>

    @if($highlights)
        <table class="facts">
            <tr>
                @foreach($highlights as $h)
                    @php($fig = \App\Support\JobOrder::figure($h['value']))
                    <td style="width: {{ round(100 / count($highlights)) }}%;">
                        <div class="fact-label">{{ mb_strtoupper($h['label']) }}</div>
                        <div class="fact-main">{{ $fig['main'] }}@if($fig['unit'])<span class="fact-unit"> / {{ $fig['unit'] }}</span>@endif</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @foreach($sections as $section)
        <table class="sec" style="page-break-inside: avoid;" autosize="1">
            <thead>
                <tr><th colspan="2"><span class="mark">&#9632;</span>&nbsp;<span class="name">{{ $section['title'] }}</span>&nbsp; <span class="name-jp jp">{{ $section['jp'] }}</span></th></tr>
            </thead>
            <tbody>
                @foreach($section['rows'] as $row)
                    @php($edge = $loop->first ? ' top' : '')
                    {{-- Inline: mPDF lets the thin row line win over a .bottom class. --}}
                    @php($close = $loop->last ? 'border-bottom: 1pt solid #1D1730;' : '')
                    @php($lines = is_array($row['value'])
                        ? array_map(fn ($item) => ['bullet' => true, 'text' => $item], $row['value'])
                        : \App\Support\JobOrder::lines($row['value']))
                    <tr>
                        <td class="label{{ $edge }}" style="{{ $close }}">{{ $row['label'] }}<br><span class="label-jp jp">{{ $row['jp'] }}</span></td>
                        <td class="value{{ $edge }}" style="{{ $close }}">
                            @if(collect($lines)->contains('bullet', true))
                                <table class="list">
                                    @foreach($lines as $line)
                                        <tr>
                                            @if($line['bullet'])
                                                <td class="dot">•</td><td>{{ $line['text'] }}</td>
                                            @else
                                                <td colspan="2">{{ $line['text'] }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                @foreach($lines as $line)<p class="line">{{ $line['text'] }}</p>@endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
