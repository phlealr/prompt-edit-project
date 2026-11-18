<?php

namespace App\Data\Reports;

/**
 * One user's figures, for a day or for the whole range.
 */
final readonly class UserFigures
{
    public function __construct(
        public string $email,
        public ReportFigures $figures,
    ) {}
}
