// The app's text is English, so times are too; they stay in the user's own time zone.
const LOCALE = 'en';

const LARGER_UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
];

/**
 * "3 minutes ago" for an ISO 8601 time.
 */
export function relativeTime(iso: string, now: Date = new Date()): string {
    const seconds = Math.round(
        (new Date(iso).getTime() - now.getTime()) / 1000,
    );
    const format = new Intl.RelativeTimeFormat(LOCALE, { numeric: 'auto' });

    for (const [unit, secondsInUnit] of LARGER_UNITS) {
        if (Math.abs(seconds) >= secondsInUnit) {
            return format.format(Math.trunc(seconds / secondsInUnit), unit);
        }
    }

    return format.format(seconds, 'second');
}

/**
 * The exact date and time, in the user's local time zone, for the hover.
 */
export function exactTime(iso: string): string {
    return new Date(iso).toLocaleString(LOCALE);
}
