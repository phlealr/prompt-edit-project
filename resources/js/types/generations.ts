import type { Money } from './money';

/**
 * One generation as its history card shows it (App\Data\Generations\GenerationCard).
 */
export type GenerationCard = {
    id: number;
    prompt: string;
    size: string;
    quality: string;
    referenceImageCount: number;
    status: 'pending' | 'queued' | 'processing' | 'succeeded' | 'failed';
    hint: string | null;
    estimateMinimum: Money;
    estimateMaximum: Money;
    charge: Money | null;
    imageUrl: string | null;
    imageUrlExpiresAt: string | null;
    createdAt: string;
};

/**
 * One page of a Laravel paginator, as Inertia receives it.
 */
export type Paginated<T> = {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};
