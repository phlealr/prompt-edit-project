import type { Money } from './money';

/** App\Data\Reports\ReportFigures */
export type ReportFigures = {
    generations: number;
    userSpend: Money;
    providerCost: Money;
    profit: Money;
};

/** App\Data\Reports\UserFigures */
export type UserFigures = {
    email: string;
    figures: ReportFigures;
};

/** App\Data\Reports\DayReport */
export type DayReport = {
    date: string;
    figures: ReportFigures;
    users: UserFigures[];
};

/** App\Data\Reports\OperatorReport */
export type OperatorReport = {
    start: string;
    end: string;
    totals: ReportFigures;
    days: DayReport[];
    users: UserFigures[];
};
