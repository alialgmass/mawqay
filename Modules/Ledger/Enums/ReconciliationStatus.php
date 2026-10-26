<?php

namespace Modules\Ledger\Enums;

enum ReconciliationStatus: string
{
    case Passed = 'passed';
    case Failed = 'failed';
}
