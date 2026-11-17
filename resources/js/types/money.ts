/**
 * An amount already formatted by the server's Money class (ADR 0007): the 4-decimal user format and
 * the exact amount shown on hover. The frontend does no money arithmetic.
 */
export type Money = {
    user: string;
    exact: string;
};
