{{-- Shared component styles for every brochure template, themed via $theme colors. --}}
{{-- IMPORTANT: this dompdf version has NO flexbox support at all — `display:flex` --}}
{{-- silently hides the element instead of degrading gracefully. Every row layout here --}}
{{-- uses `display:table`/`table-cell` (dompdf's actual, well-supported column model) --}}
{{-- with `border-spacing` for real gaps between cells. It also has no CSS gradients or --}}
{{-- box-shadow, and no `object-fit` (images are pre-cropped server-side instead). --}}
<style>
  @page { size: A4; margin: 0; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: {{ $theme['font'] }}; color: {{ $theme['text'] }}; background: {{ $theme['bg'] }}; }
  .page { width: 210mm; height: 297mm; position: relative; overflow: hidden; page-break-after: avoid; page-break-inside: avoid; background: {{ $theme['bg'] }}; }
  .page + .page { page-break-before: always; }

  .content-head { background: {{ $theme['primary'] }}; color: {{ $theme['on_primary'] }}; padding: 8mm 14mm; }
  .content-head h2 { font-size: 17pt; font-weight: 800; line-height: 1.25; }
  .content-head h2 span { color: {{ $theme['accent'] }}; }

  .hook { background: {{ $theme['accent'] }}; color: {{ $theme['on_accent'] }}; text-align: center; padding: 5mm 14mm; font-size: 13pt; font-weight: 800; }

  .cards { display: table; table-layout: fixed; width: 182mm; margin: 6mm 14mm 0; border-collapse: separate; border-spacing: 4mm 0; }
  .card { display: table-cell; vertical-align: top; background: {{ $theme['panel'] }}; border-top: 3px solid {{ $theme['accent'] }}; padding: 4.5mm; }
  .card .t { font-size: 11pt; font-weight: 800; color: {{ $theme['heading'] }}; margin-bottom: 2mm; }
  .card .d { font-size: 9pt; line-height: 1.5; color: {{ $theme['text'] }}; }

  .quote { margin: 6mm 14mm 0; text-align: center; font-size: 12pt; font-weight: 700; color: {{ $theme['heading'] }}; line-height: 1.5; padding: 0 6mm; }
  .quote span { color: {{ $theme['accent'] }}; }

  .gal { display: table; table-layout: fixed; width: 182mm; margin: 6mm 14mm 0; border-collapse: separate; border-spacing: 3mm 0; }
  .gal figure { display: table-cell; vertical-align: top; }
  .gal img { width: 100%; height: 45mm; display: block; }
  .gal figcaption { font-size: 7.5pt; color: {{ $theme['muted'] }}; margin-top: 1.5mm; text-align: center; }

  .trust { margin: 6mm 14mm 0; background: {{ $theme['panel'] }}; border-left: 4px solid {{ $theme['accent'] }}; padding: 4.5mm 5mm; font-size: 9.5pt; line-height: 1.55; color: {{ $theme['text'] }}; }
  .trust b { color: {{ $theme['heading'] }}; }

  .stats { display: table; table-layout: fixed; width: 182mm; margin: 6mm 14mm 0; border-collapse: separate; border-spacing: 4mm 0; }
  .stat { display: table-cell; vertical-align: top; background: {{ $theme['panel'] }}; border-top: 3px solid {{ $theme['accent'] }}; padding: 4mm; text-align: center; }
  .stat .n { font-size: 15pt; font-weight: 800; color: {{ $theme['heading'] }}; }
  .stat .d { font-size: 7.7pt; color: {{ $theme['muted'] }}; margin-top: 1.5mm; line-height: 1.35; }

  .steps { display: table; table-layout: fixed; width: 182mm; margin: 6mm 14mm 0; border-collapse: separate; border-spacing: 4mm 0; }
  .step { display: table-cell; vertical-align: top; text-align: center; }
  .step .num { width: 11mm; height: 11mm; line-height: 11mm; border-radius: 50%; background: {{ $theme['accent'] }}; color: {{ $theme['on_accent'] }}; font-weight: 800; font-size: 13pt; margin: 0 auto 2.5mm; text-align: center; }
  .step .t { font-size: 10pt; font-weight: 800; color: {{ $theme['heading'] }}; }
  .step .d { font-size: 8.5pt; color: {{ $theme['muted'] }}; line-height: 1.45; margin-top: 1.5mm; }

  .croquis { display: table; width: 182mm; margin: 6mm 14mm 0; border-collapse: separate; border-spacing: 5mm 0; }
  .croquis .map { display: table-cell; vertical-align: top; width: 63%; background: {{ $theme['panel'] }}; border-top: 3px solid {{ $theme['accent'] }}; padding: 3mm; }
  .croquis .map svg { width: 100%; height: auto; display: block; }
  .croquis .plano { display: table-cell; vertical-align: top; width: 37%; }
  .croquis .plano img { width: 100%; height: 62mm; display: block; }
  .croquis figcaption { font-size: 7.5pt; color: {{ $theme['muted'] }}; margin-top: 1.5mm; text-align: center; }

  .property-summary { margin: 4mm 14mm 0; background: {{ $theme['panel'] }}; border-left: 4px solid {{ $theme['accent'] }}; color: {{ $theme['text'] }}; padding: 3.5mm 5mm; }
  .property-summary strong { color: {{ $theme['heading'] }}; font-size: 9.5pt; text-transform: uppercase; letter-spacing: .8px; }
  .property-summary p { font-size: 8.8pt; line-height: 1.42; margin-top: 1.2mm; }

  .ficha { margin: 5mm 14mm 0; width: 182mm; border-collapse: collapse; font-size: 9pt; }
  .ficha td { border: 0.3mm solid {{ $theme['muted'] }}; padding: 2mm 3.5mm; color: {{ $theme['text'] }}; }
  .ficha td:first-child { background: {{ $theme['panel'] }}; font-weight: 700; color: {{ $theme['heading'] }}; width: 38%; }

  .faq { padding: 5mm 14mm 0; }
  .faq h3 { font-size: 11pt; color: {{ $theme['heading'] }}; text-transform: uppercase; letter-spacing: 1.5px; border-bottom: 2px solid {{ $theme['accent'] }}; padding-bottom: 2mm; margin-bottom: 3mm; }
  .faq p { font-size: 9.5pt; line-height: 1.55; margin-bottom: 2.5mm; color: {{ $theme['text'] }}; }
  .faq b { color: {{ $theme['heading'] }}; }

  .photo-sheet { margin: 6mm 14mm 0; }
  .photo-sheet-row { border-collapse: separate; border-spacing: 3mm 0; display: table; table-layout: fixed; width: 182mm; margin-bottom: 3mm; }
  .photo-sheet-row figure { display: table-cell; vertical-align: top; width: 50%; }
  .photo-sheet-row img { display: block; width: 100%; }

  .cta { position: absolute; bottom: 0; left: 0; width: 210mm; background: {{ $theme['primary'] }}; color: {{ $theme['on_primary'] }}; padding: 6mm 14mm; display: table; }
  .cta .ctaleft { display: table-cell; vertical-align: middle; }
  .cta-logo { display: inline-block; vertical-align: middle; height: 15mm; margin-right: 5mm; }
  .cta-logo-text { display: inline-block; vertical-align: middle; }
  .cta .contact { display: table-cell; vertical-align: middle; text-align: right; font-size: 9.5pt; line-height: 1.6; }
  .cta .name { font-size: 12.5pt; font-weight: 800; }
  .cta .role { font-size: 8.5pt; color: {{ $theme['accent'] }}; text-transform: uppercase; letter-spacing: 2px; margin-top: 1mm; }
  .cta .addr { font-size: 7.5pt; opacity: 0.8; margin-top: 1.5mm; }
  .cta .contact b { color: {{ $theme['accent'] }}; font-size: 13pt; }

  {{-- Slim one-line footer for the cover page, which is already tightly packed
       (hero image + hook + cards + quote): the full .cta panel is reserved for
       the content pages below, where there is room to spare. --}}
  .foot1 { position: absolute; bottom: 0; left: 0; width: 210mm; background: {{ $theme['primary'] }}; color: {{ $theme['on_primary'] }}; font-size: 8.5pt; padding: 3.5mm 14mm; display: table; }
  .foot1 span { display: table-cell; vertical-align: middle; }
  .foot1 span:last-child { text-align: right; }
  .foot1 b { color: {{ $theme['accent'] }}; }
  .foot1-logo { display: inline-block; vertical-align: middle; height: 9mm; margin-right: 2.5mm; }
</style>
