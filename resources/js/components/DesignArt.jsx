function Frame({ children, ring = true }) {
    return (
        <svg viewBox="0 0 200 200" className="h-full w-full">
            {ring && <circle cx="100" cy="100" r="92" fill="none" stroke="var(--color-line)" strokeWidth="1" />}
            {children}
        </svg>
    );
}

function StarOfDavid() {
    const tri = (rot) => {
        const pts = [0, 120, 240].map((a) => {
            const rad = ((a + rot) * Math.PI) / 180;
            const r = 52;
            return `${100 + r * Math.sin(rad)},${100 - r * Math.cos(rad)}`;
        });
        return pts.join(' ');
    };
    return (
        <Frame>
            <polygon points={tri(0)} fill="none" stroke="var(--color-ink)" strokeWidth="2.5" strokeLinejoin="round" />
            <polygon points={tri(60)} fill="none" stroke="var(--color-ink)" strokeWidth="2.5" strokeLinejoin="round" />
        </Frame>
    );
}

function Menorah() {
    // Seven branches: center stem tallest (shamash), others stepping down symmetrically.
    const dropPerStep = 12;
    const stems = [0, 1, 2, 3, 2, 1, 0].map((step, i) => ({
        x: 40 + i * 20,
        yTop: 40 + step * dropPerStep,
    }));

    return (
        <Frame>
            <line x1="35" y1="152" x2="165" y2="152" stroke="var(--color-ink)" strokeWidth="2.5" strokeLinecap="round" />
            <line x1="100" y1="152" x2="100" y2="172" stroke="var(--color-ink)" strokeWidth="2.5" strokeLinecap="round" />
            {stems.map(({ x, yTop }, i) => (
                <g key={i}>
                    <line x1={x} y1="150" x2={x} y2={yTop} stroke="var(--color-ink)" strokeWidth={i === 3 ? 2.5 : 1.8} strokeLinecap="round" />
                    <circle cx={x} cy={yTop - 7} r="4" fill="var(--color-brass)" />
                </g>
            ))}
        </Frame>
    );
}

function HebrewMark({ text, fontSize = 72 }) {
    return (
        <Frame>
            <text
                x="100"
                y="100"
                textAnchor="middle"
                dominantBaseline="central"
                fontSize={fontSize}
                fontFamily="var(--font-serif)"
                fill="var(--color-ink)"
            >
                {text}
            </text>
        </Frame>
    );
}

function Hamsa() {
    const stroke = { fill: 'none', stroke: 'var(--color-ink)', strokeWidth: 2, strokeLinejoin: 'round', strokeLinecap: 'round' };
    return (
        <Frame>
            <rect x="70" y="95" width="60" height="60" rx="16" {...stroke} />
            <rect x="94" y="38" width="12" height="62" rx="6" {...stroke} />
            <rect x="76" y="50" width="12" height="52" rx="6" {...stroke} />
            <rect x="112" y="50" width="12" height="52" rx="6" {...stroke} />
            <ellipse cx="62" cy="115" rx="10" ry="20" transform="rotate(-20 62 115)" {...stroke} />
            <ellipse cx="138" cy="115" rx="10" ry="20" transform="rotate(20 138 115)" {...stroke} />
            <circle cx="100" cy="122" r="7" fill="none" stroke="var(--color-brass)" strokeWidth="2" />
        </Frame>
    );
}

function Pomegranate() {
    const crown = [-60, -30, 0, 30, 60].map((angle, i) => {
        const rad = (angle * Math.PI) / 180;
        const x1 = 100 + 8 * Math.sin(rad);
        const y1 = 82 - 8 * Math.cos(rad);
        const x2 = 100 + 24 * Math.sin(rad);
        const y2 = 82 - 24 * Math.cos(rad);
        return <line key={i} x1={x1} y1={y1} x2={x2} y2={y2} stroke="var(--color-brass)" strokeWidth="3" strokeLinecap="round" />;
    });
    const seeds = [
        [88, 108], [112, 112], [96, 128], [110, 136], [84, 132],
    ];
    return (
        <Frame>
            <circle cx="100" cy="122" r="42" fill="none" stroke="var(--color-ink)" strokeWidth="2.5" />
            {crown}
            {seeds.map(([cx, cy], i) => (
                <circle key={i} cx={cx} cy={cy} r="2.5" fill="var(--color-ink)" opacity="0.6" />
            ))}
        </Frame>
    );
}

const REGISTRY = {
    'star-of-david': StarOfDavid,
    menorah: Menorah,
    chai: () => <HebrewMark text="חי" />,
    shalom: () => <HebrewMark text="שלום" fontSize={48} />,
    hamsa: Hamsa,
    pomegranate: Pomegranate,
    aleph: () => <HebrewMark text="א" fontSize={90} />,
};

export default function DesignArt({ motif, className = '' }) {
    const Art = REGISTRY[motif] || REGISTRY['star-of-david'];

    return (
        <div className={`flex items-center justify-center bg-parchment-dim ${className}`}>
            <div className="aspect-square w-full max-w-full p-6">
                <Art />
            </div>
        </div>
    );
}
