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

  .content-head { background: {{ $theme['primary'] }}; color: {{ $theme['on_primary'] }}; padding: 7mm 14mm; }
  .content-head h2 { font-size: 15.5pt; font-weight: 800; line-height: 1.18; }
  .content-head h2 span { color: {{ $theme['accent'] }}; }

  .hook { background: {{ $theme['accent'] }}; color: {{ $theme['on_accent'] }}; height: 17mm; overflow: hidden; text-align: center; padding: 4mm 14mm; font-size: 11pt; font-weight: 800; line-height: 1.25; }

  {{-- 44mm, not 33: at the old height a three-line description was clipped, so the text
       had to be cut short to fit. The cover had ~33mm of unused space above the footer. --}}
  .cards { display: table; table-layout: fixed; width: 182mm; height: 44mm; overflow: hidden; margin: 5mm 14mm 0; border-collapse: separate; border-spacing: 4mm 0; }
  .card { display: table-cell; vertical-align: top; background: {{ $theme['panel'] }}; border-top: 3px solid {{ $theme['accent'] }}; padding: 3.5mm; overflow: hidden; }
  .card .t { font-size: 10pt; font-weight: 800; color: {{ $theme['heading'] }}; margin-bottom: 1.5mm; }
  .card .d { font-size: 8.2pt; line-height: 1.32; color: {{ $theme['text'] }}; }

  .quote { height: 13mm; overflow: hidden; margin: 5mm 14mm 0; text-align: center; font-size: 10.5pt; font-weight: 700; color: {{ $theme['heading'] }}; line-height: 1.3; padding: 0 6mm; }
  .quote span { color: {{ $theme['accent'] }}; }

  .gal { display: table; table-layout: fixed; width: 182mm; margin: 6mm 14mm 0; border-collapse: separate; border-spacing: 3mm 0; }
  .gal figure { display: table-cell; vertical-align: top; }
  .gal img { width: 100%; height: 45mm; display: block; }
  .gal figcaption { font-size: 7.5pt; color: {{ $theme['muted'] }}; margin-top: 1.5mm; text-align: center; }
  {{-- A light highlights page receives a deliberately roomier composition. The
       additional space is allocated to bounded blocks, never free-flow text. --}}
  .highlights-balanced .gal img { height: 70mm; }
  .highlights-balanced .stat { height: 27mm; padding: 4.5mm 3mm; }
  .highlights-balanced .property-summary { height: 35mm; }
  .highlights-balanced .steps { height: 36mm; margin-top: 35mm; }

  .trust { height: 33mm; overflow: hidden; margin: 5mm 14mm 0; background: {{ $theme['panel'] }}; border-left: 4px solid {{ $theme['accent'] }}; padding: 3.5mm 5mm; font-size: 8.6pt; line-height: 1.35; color: {{ $theme['text'] }}; }
  .trust b { color: {{ $theme['heading'] }}; }

  .stats { display: table; table-layout: fixed; width: 182mm; height: 24mm; overflow: hidden; margin: 5mm 14mm 0; border-collapse: separate; border-spacing: 4mm 0; }
  {{-- Centred, not top-aligned: the tiles are taller than their two short lines, so
       top alignment left the value floating with a large empty gap beneath it. --}}
  .stat { display: table-cell; vertical-align: middle; background: {{ $theme['panel'] }}; border-top: 3px solid {{ $theme['accent'] }}; padding: 3mm; overflow: hidden; text-align: center; }
  .stat .n { font-size: 13pt; font-weight: 800; color: {{ $theme['heading'] }}; }
  .stat .d { font-size: 7pt; color: {{ $theme['muted'] }}; margin-top: 1mm; line-height: 1.2; }

  .steps { display: table; table-layout: fixed; width: 182mm; height: 31mm; overflow: hidden; margin: 5mm 14mm 0; border-collapse: separate; border-spacing: 4mm 0; }
  .step { display: table-cell; vertical-align: top; text-align: center; }
  .step .num { display: table; width: 9mm; height: 9mm; border-radius: 50%; background: {{ $theme['accent'] }}; color: {{ $theme['on_accent'] }}; font-weight: 800; font-size: 11pt; margin: 0 auto 1.5mm; text-align: center; }
  .step .num span { display: table-cell; height: 9mm; vertical-align: middle; }
  .step .t { font-size: 8.8pt; font-weight: 800; color: {{ $theme['heading'] }}; }
  .step .d { font-size: 7.4pt; color: {{ $theme['muted'] }}; line-height: 1.25; margin-top: 1mm; }

  .croquis { display: table; width: 182mm; margin: 6mm 14mm 0; border-collapse: separate; border-spacing: 5mm 0; }
  .croquis .map { display: table-cell; vertical-align: top; width: 63%; background: {{ $theme['panel'] }}; border-top: 3px solid {{ $theme['accent'] }}; padding: 3mm; }
  .croquis .map img { width: 100%; height: 62mm; display: block; }
  .croquis .plano { display: table-cell; vertical-align: top; width: 37%; }
  .croquis .plano img { width: 100%; height: 62mm; display: block; }
  .croquis figcaption { font-size: 7.5pt; color: {{ $theme['muted'] }}; margin-top: 1.5mm; text-align: center; }

  .property-summary { height: 25mm; overflow: hidden; margin: 4mm 14mm 0; background: {{ $theme['panel'] }}; border-left: 4px solid {{ $theme['accent'] }}; color: {{ $theme['text'] }}; padding: 3mm 5mm; }
  .property-summary strong { color: {{ $theme['heading'] }}; font-size: 8.8pt; text-transform: uppercase; letter-spacing: .8px; }
  .property-summary p { font-size: 8pt; line-height: 1.3; margin-top: 1mm; }

  .ficha { margin: 4mm 14mm 0; width: 182mm; border-collapse: collapse; font-size: 8pt; }
  .ficha td { border: 0.3mm solid {{ $theme['muted'] }}; padding: 1.5mm 3mm; color: {{ $theme['text'] }}; line-height: 1.25; }
  .ficha td:first-child { background: {{ $theme['panel'] }}; font-weight: 700; color: {{ $theme['heading'] }}; width: 38%; }

  .faq { max-height: 61mm; overflow: hidden; padding: 4mm 14mm 0; }
  .faq h3 { font-size: 10pt; color: {{ $theme['heading'] }}; text-transform: uppercase; letter-spacing: 1.2px; border-bottom: 2px solid {{ $theme['accent'] }}; padding-bottom: 1.5mm; margin-bottom: 2mm; }
  .faq p { font-size: 8pt; line-height: 1.35; margin-bottom: 2mm; color: {{ $theme['text'] }}; }
  {{-- 62mm, not 39: three questions need ~41mm and were being sliced through the middle
       of the third answer, while the page still had room before the footer at 260mm. --}}
  .details-compact .faq { max-height: 62mm; }
  {{-- Without a croquis the details page has more room. Reserve it inside a
       bounded information panel so the page stays balanced and cannot flow
       into the footer. --}}
  .details-spacious .property-summary { height: 35mm; padding: 4mm 5mm; }
  .details-spacious .ficha { margin-top: 5mm; table-layout: fixed; }
  .details-spacious .ficha td { padding: 2.2mm 3mm; line-height: 1.3; overflow: hidden; }
  .details-spacious .faq { height: 100mm; overflow: hidden; margin: 7mm 14mm 0; padding: 5mm; background: {{ $theme['panel'] }}; border-left: 4px solid {{ $theme['accent'] }}; }
  .faq b { color: {{ $theme['heading'] }}; }

  .photo-sheet { margin: 6mm 14mm 0; }
  .photo-sheet-row { border-collapse: separate; border-spacing: 3mm 0; display: table; table-layout: fixed; width: 182mm; margin-bottom: 3mm; }
  .photo-sheet-row figure { display: table-cell; vertical-align: top; width: 50%; }
  .photo-sheet-row img { display: block; width: 100%; }

  {{-- Footer panels are plain BLOCKS, never `display:table`. CSS treats `height` on a
       table box as a *minimum* and does not apply `overflow` to it, so the old
       `display:table` footers silently grew past 297mm and got sliced by the page edge
       (measured: .cta painted to 309mm, .foot1 to 313mm). A block honours both the fixed
       height and `overflow:hidden`, so the panel can never leave the sheet. The columns
       live in an inner table with explicit widths — the logo needs its own cell, because
       as an inline-block it was being pushed onto a line of its own by the shrink-to-fit
       text next to it, which is what made the panels overflow in the first place. --}}
  {{-- Columns are absolutely positioned, not table cells: dompdf ignores
       `table-layout: fixed`, auto-sized the logo cell to twice its width and wrapped
       the agent name onto three lines. Absolute left/width is honoured exactly. --}}
  .cta { position: absolute; top: 260mm; left: 0; width: 210mm; height: 37mm; overflow: hidden; background: {{ $theme['primary'] }}; color: {{ $theme['on_primary'] }}; }
  .cta .ctalogo { position: absolute; left: 14mm; top: 12mm; width: 26mm; }
  .cta .ctaleft { position: absolute; left: 44mm; top: 9mm; width: 82mm; }
  .cta-nologo .ctaleft { left: 14mm; width: 112mm; }
  .cta-logo { height: 12mm; }
  .cta .contact { position: absolute; left: 128mm; top: 9mm; width: 68mm; text-align: right; font-size: 9pt; line-height: 1.45; }
  .cta .name { font-size: 12pt; font-weight: 800; line-height: 1.15; }
  .cta .role { font-size: 8pt; color: {{ $theme['accent'] }}; text-transform: uppercase; letter-spacing: 1.6px; margin-top: 1mm; line-height: 1.2; }
  {{-- The service area is a list of districts and is meant to wrap onto a second line
       rather than be cut mid-district; the panel has the vertical room for it. --}}
  .cta .addr { font-size: 7pt; opacity: 0.8; margin-top: 1mm; line-height: 1.25; max-height: 8mm; overflow: hidden; }
  .cta .contact b { color: {{ $theme['accent'] }}; font-size: 12.5pt; }

  {{-- Slim one-line footer for the cover page, which is already tightly packed
       (hero image + hook + cards + quote): the full .cta panel is reserved for
       the content pages below, where there is room to spare. --}}
  {{-- 7.5pt, not 8.5: at the larger size "Carmen Mestanza Inmobiliaria · Agente
       Inmobiliario" plus a long e-mail needed ~207mm of the 182mm available, so both
       columns wrapped and left a word dangling on a second line. The columns below are
       sized from the measured width of that real content, with room to spare. --}}
  .foot1 { position: absolute; top: 270mm; left: 0; width: 210mm; height: 27mm; overflow: hidden; background: {{ $theme['primary'] }}; color: {{ $theme['on_primary'] }}; font-size: 7.5pt; }
  .foot1 .foot1-mark { position: absolute; left: 14mm; top: 7.5mm; width: 30mm; }
  .foot1 .foot1-who { position: absolute; left: 48mm; top: 9.5mm; width: 74mm; line-height: 1.3; }
  .foot1-nologo .foot1-who { left: 14mm; width: 108mm; }
  .foot1 .foot1-contact { position: absolute; left: 124mm; top: 9.5mm; width: 72mm; text-align: right; line-height: 1.3; }
  .foot1 b { color: {{ $theme['accent'] }}; }
  {{-- The cover mark matches the content pages' 12mm logo: at 7mm it read as a smudge. --}}
  .foot1-logo { height: 12mm; }
</style>
