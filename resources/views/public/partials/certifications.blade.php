{{-- Las insignias van dibujadas en SVG (no como imagen) para que se vean nítidas
     en cualquier pantalla, respeten el modo oscuro del navegador y no sumen
     descargas a la portada. --}}
<section class="certifications" aria-labelledby="certificaciones-titulo">
    <h2 class="certifications-title" id="certificaciones-titulo">{{ \App\Support\SiteSettings::certificationsTitle($settings) }}</h2>
    <ul class="certification-list">
        <li class="certification">
            <span class="certification-mark" aria-hidden="true">
                <svg viewBox="0 0 64 64" role="img" focusable="false">
                    <path d="M8 34 32 15l24 19" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M15 32v18h34V32" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                    <rect x="26" y="37" width="12" height="13" fill="none" stroke="currentColor" stroke-width="2.4"/>
                    <path d="M6 55c8-6 18-9 26-9s18 3 26 9" fill="none" stroke="currentColor" stroke-width="2.4" opacity=".55"/>
                </svg>
            </span>
            <strong>Luxury Homes Certification</strong>
            <span>Especialización en propiedades de lujo</span>
        </li>
        <li class="certification">
            <span class="certification-mark" aria-hidden="true">
                <svg viewBox="0 0 64 64" role="img" focusable="false">
                    <path d="M32 6 55 14v20c0 13-10 21-23 24C19 55 9 47 9 34V14z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                    <text x="32" y="32" text-anchor="middle" font-size="16" font-weight="700" fill="currentColor" font-family="inherit">CRS</text>
                    <path d="M14 38h36" stroke="currentColor" stroke-width="2.4" opacity=".55"/>
                </svg>
            </span>
            <strong>Certified Residential Specialist</strong>
            <span>Especialista residencial certificada</span>
        </li>
        <li class="certification">
            <span class="certification-mark" aria-hidden="true">
                <svg viewBox="0 0 64 64" role="img" focusable="false">
                    <path d="M32 8 58 28 32 56 6 28z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M6 28h52M32 8l-9 20 9 28 9-28z" fill="none" stroke="currentColor" stroke-width="2.2" opacity=".7"/>
                </svg>
            </span>
            <strong>Industrial Specialist</strong>
            <span>Especialista en propiedades industriales</span>
        </li>
        <li class="certification">
            <span class="certification-mark" aria-hidden="true">
                <svg viewBox="0 0 64 64" role="img" focusable="false">
                    <circle cx="32" cy="32" r="25" fill="none" stroke="currentColor" stroke-width="3"/>
                    <ellipse cx="32" cy="32" rx="11" ry="25" fill="none" stroke="currentColor" stroke-width="2.4"/>
                    <path d="M8 22h48M8 42h48M32 7v50" fill="none" stroke="currentColor" stroke-width="2.4"/>
                </svg>
            </span>
            <strong>Commercial Certification</strong>
            <span>Certificación en propiedades comerciales</span>
        </li>
    </ul>
</section>
