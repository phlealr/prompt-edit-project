/**
 * An amount already formatted by the server's Money class (ADR 0007): the 4-decimal user format and
 * the exact amount shown on hover. The frontend does no money arithmetic.
 */
export type Money = {
    user: string;
    exact: string;
};

/**
 * The user's balance, shared with every signed-in page (App\Data\Credits\Balance).
 */
export type Balance = {
    amount: Money;
    belowZero: boolean;
};

/**
 * One line of the Credits activity (App\Data\Credits\ActivityEntry).
 */
export type ActivityEntry = {
    kind: 'purchase' | 'charge';
    amount: Money;
    prompt: string | null;
    createdAt: string;
};
