<?php

/*
| The default operator, created by OperatorSeeder so reviewers can open the operator report
| (ADR 0016). The defaults are the login published in README.md; set OPERATOR_* to use another.
*/

return [

    'email' => env('OPERATOR_EMAIL', 'operator@promptedit.test'),

    'name' => env('OPERATOR_NAME', 'Operator'),

    'password' => env('OPERATOR_PASSWORD', 'Review-pmBOtj2rLEsj!9a'),

];
