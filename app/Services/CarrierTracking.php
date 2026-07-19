<?php

namespace App\Services;

class CarrierTracking
{
    /**
     * Known carrier name (lowercased) => sprintf tracking URL pattern, with a
     * single %s placeholder for the URL-encoded tracking number. Only real,
     * documented carrier tracking URL patterns belong here — never a
     * fabricated/guessed URL for a carrier we don't actually recognize.
     */
    private const PATTERNS = [
        'usps' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=%s',
        'ups' => 'https://www.ups.com/track?loc=en_US&tracknum=%s',
        'fedex' => 'https://www.fedex.com/fedextrack/?trknbr=%s',
        'israel post' => 'https://mail.israelpost.co.il/itemtrace.aspx?itemcode=%s',
    ];

    /**
     * Build a clickable tracking URL for the given carrier + tracking
     * number, or null if either is missing or the carrier isn't one of the
     * patterns we actually know. Callers must fall back to showing the
     * plain tracking number and carrier name (no link) when this is null.
     */
    public static function url(?string $carrier, ?string $trackingNumber): ?string
    {
        if (! $carrier || ! $trackingNumber) {
            return null;
        }

        $pattern = self::PATTERNS[strtolower(trim($carrier))] ?? null;

        if ($pattern === null) {
            return null;
        }

        return sprintf($pattern, urlencode($trackingNumber));
    }
}
