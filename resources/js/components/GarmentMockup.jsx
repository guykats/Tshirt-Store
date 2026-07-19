import { useId } from 'react';
import DesignArt from './DesignArt';

// Flat-lay garment mockups, built entirely as SVG + CSS gradients/filters — no external
// photography or image-generation tooling was available for this task (see the
// "Real garment mockup imagery" task notes). This renders a real garment silhouette
// (crew-neck tee or hoodie, chosen by product name) with fabric shading, a subtle woven
// grain, collar/hood/rib detail, and the brand's existing DesignArt line-art mark
// composited on the chest as the print — a materially higher-fidelity stand-in for a
// product photo than a bare motif icon floating in a colored square, while staying
// inside the same restrained parchment/ink/brass, single-stroke design language.

const PALETTES = {
    Black: {
        highlight: '#332d24',
        base: '#201c17',
        shadow: '#0f0d0a',
        trim: '#3a332a',
        hood: '#151310',
        tone: 'dark',
    },
    Sand: {
        highlight: '#f3ead9',
        base: '#e7dac2',
        shadow: '#d8c9a8',
        trim: '#c9b891',
        hood: '#e0d2b6',
        tone: 'light',
    },
};

function paletteFor(color) {
    return PALETTES[color] || PALETTES.Sand;
}

// Garment silhouette (tee vs. hoodie) isn't a stored column today — the demo seeder
// (database/seeders/DatabaseSeeder.php) only used an in-memory `type` while building
// products, it never reached the `products` table. Rather than add a migration for a
// single display flag, this reads the same signal already encoded in the product name.
export function garmentTypeFromProduct(product) {
    return (product?.name || '').toLowerCase().includes('hoodie') ? 'hoodie' : 'tee';
}

const TEE_BODY =
    'M112,32 C112,32 74,50 46,84 L36,100 L66,146 L92,114 L92,270 L208,270 L208,114 L234,146 L264,100 L254,84 ' +
    'C226,50 188,32 188,32 C186,52 172,68 150,68 C128,68 114,52 112,32 Z';

const TEE_COLLAR = 'M112,32 C114,52 128,68 150,68 C172,68 186,52 188,32';

const HOODIE_BODY =
    'M150,20 C120,20 100,36 96,58 C70,62 46,78 40,100 L32,112 L62,158 L88,126 L88,272 L212,272 L212,126 ' +
    'L238,158 L268,112 L260,100 C254,78 230,62 204,58 C200,36 180,20 150,20 Z';

const HOOD_SHAPE =
    'M96,58 C100,36 120,20 150,20 C180,20 200,36 204,58 C196,50 178,44 150,44 C122,44 104,50 96,58 Z';

export default function GarmentMockup({ motif, product, color, className = '', label }) {
    const rawId = useId().replace(/[:]/g, '');
    const garment = garmentTypeFromProduct(product);
    const isHoodie = garment === 'hoodie';
    const resolvedColor = color || product?.variants?.[0]?.color || 'Sand';
    const palette = paletteFor(resolvedColor);
    const body = isHoodie ? HOODIE_BODY : TEE_BODY;

    const fabricId = `gm-fabric-${rawId}`;
    const grainId = `gm-grain-${rawId}`;
    const clipId = `gm-clip-${rawId}`;

    // Chest-print placement, expressed as a percentage box of the 300x300 viewBox so it
    // lines up 1:1 with the underlying <svg> regardless of rendered size.
    const printBox = isHoodie
        ? { left: '39%', top: '35.5%', size: '22%' }
        : { left: '35%', top: '41%', size: '30%' };

    return (
        <div
            className={`relative flex items-center justify-center bg-parchment-dim ${className}`}
            role={label ? 'img' : undefined}
            aria-label={label || undefined}
            aria-hidden={label ? undefined : true}
        >
            <svg viewBox="0 0 300 300" className="h-full w-full">
                <defs>
                    <linearGradient id={fabricId} x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stopColor={palette.highlight} />
                        <stop offset="55%" stopColor={palette.base} />
                        <stop offset="100%" stopColor={palette.shadow} />
                    </linearGradient>
                    <filter id={grainId} x="-20%" y="-20%" width="140%" height="140%">
                        <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="7" result="noise" />
                        <feColorMatrix
                            in="noise"
                            type="matrix"
                            values={
                                palette.tone === 'dark'
                                    ? '0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 0.05 0'
                                    : '0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 0.06 0'
                            }
                        />
                    </filter>
                    <clipPath id={clipId}>
                        <path d={body} />
                    </clipPath>
                </defs>

                <path d={body} fill={`url(#${fabricId})`} stroke={palette.trim} strokeWidth="2" strokeLinejoin="round" />

                {/* Fabric grain + fold lines, clipped to the garment silhouette. */}
                <g clipPath={`url(#${clipId})`}>
                    <rect x="0" y="0" width="300" height="300" filter={`url(#${grainId})`} />
                    {isHoodie ? (
                        <>
                            <path d="M66,166 Q96,196 88,272" fill="none" stroke={palette.trim} strokeWidth="2" opacity="0.4" strokeLinecap="round" />
                            <path d="M234,166 Q204,196 212,272" fill="none" stroke={palette.trim} strokeWidth="2" opacity="0.4" strokeLinecap="round" />
                            <path d="M96,270 L204,270" fill="none" stroke={palette.trim} strokeWidth="2" strokeDasharray="4 3" opacity="0.5" />
                        </>
                    ) : (
                        <>
                            <path d="M70,150 Q100,180 92,270" fill="none" stroke={palette.trim} strokeWidth="2" opacity="0.35" strokeLinecap="round" />
                            <path d="M230,150 Q200,180 208,270" fill="none" stroke={palette.trim} strokeWidth="2" opacity="0.35" strokeLinecap="round" />
                            <path d="M100,90 Q150,100 200,90" fill="none" stroke={palette.trim} strokeWidth="1.5" opacity="0.3" />
                            <path d="M96,266 L204,266" fill="none" stroke={palette.trim} strokeWidth="2" strokeDasharray="4 3" opacity="0.5" />
                        </>
                    )}
                </g>

                {isHoodie ? (
                    <>
                        <path d={HOOD_SHAPE} fill={palette.hood} stroke={palette.trim} strokeWidth="2" />
                        <circle cx="150" cy="70" r="3" fill="var(--color-brass-light)" />
                        <path d="M140,64 L136,90" stroke="var(--color-brass-light)" strokeWidth="1.6" strokeLinecap="round" />
                        <path d="M160,64 L164,90" stroke="var(--color-brass-light)" strokeWidth="1.6" strokeLinecap="round" />
                        <path
                            d="M112,190 Q150,206 188,190 L188,222 Q150,236 112,222 Z"
                            fill="none"
                            stroke={palette.trim}
                            strokeWidth="2"
                            opacity="0.7"
                        />
                    </>
                ) : (
                    <>
                        <path d={TEE_COLLAR} fill="none" stroke={palette.trim} strokeWidth="4" opacity="0.6" strokeLinecap="round" />
                        <path
                            d={TEE_COLLAR}
                            fill="none"
                            stroke={palette.tone === 'dark' ? palette.highlight : '#17140f'}
                            strokeWidth="1"
                            opacity="0.3"
                            strokeDasharray="1 3"
                            strokeLinecap="round"
                        />
                    </>
                )}
            </svg>

            {/* The chest print: the exact same DesignArt line-art mark used elsewhere on
                the site, composited on top of the garment rather than redrawn, so every
                surface (hero, product card, this mockup) renders one source of truth per
                motif. `background={false}` drops DesignArt's own fill so only the mark
                shows through onto the fabric beneath it. */}
            <div
                className="absolute"
                style={{
                    left: printBox.left,
                    top: printBox.top,
                    width: printBox.size,
                    height: printBox.size,
                    // DesignArt's ring stroke defaults to --color-line (a near-parchment
                    // beige) which all but disappears against the Sand fabric fill — swap
                    // it for brass here so the print ring reads on light garments too. The
                    // dark-tone path already gets a brass-light ring for free from
                    // DesignArt's own tone="dark" handling.
                    '--color-line': palette.tone === 'dark' ? undefined : 'var(--color-brass)',
                }}
            >
                <DesignArt motif={motif} tone={palette.tone === 'dark' ? 'dark' : 'light'} background={false} />
            </div>
        </div>
    );
}
