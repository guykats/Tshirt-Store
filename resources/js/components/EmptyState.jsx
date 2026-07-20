import DesignArt from './DesignArt';

// A reusable "nothing here yet" block — pairs one of DesignArt's line-art
// motifs with a heading and a short body line, so an empty list reads as an
// intentional, on-brand moment (see NotFound.jsx) rather than a bare
// sentence that could be mistaken for a loading glitch or a bug.
//
// `motifLabel` is passed straight through to DesignArt's `label` prop, so the
// motif gets the same `role="img"`/`aria-label` treatment DesignArt already
// gives any primary (non-decorative) use of a mark — see DesignArt.jsx.
export default function EmptyState({ motif, motifLabel, title, body, action, className = '' }) {
    return (
        <div className={`flex flex-col items-center px-6 py-12 text-center ${className}`}>
            <DesignArt motif={motif} label={motifLabel} className="mb-6 h-24 w-24 rounded" />
            <h2 className="font-serif text-lg">{title}</h2>
            {body && <p className="mt-2 max-w-sm text-sm text-ink-soft">{body}</p>}
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
