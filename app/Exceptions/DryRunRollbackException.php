<?php

namespace App\Exceptions;

use App\Models\SewingReturn;

/**
 * Dilempar di akhir dry-run transaction untuk memaksa rollback
 * sambil membawa data SewingReturn yang "seharusnya" tersimpan.
 */
class DryRunRollbackException extends \RuntimeException
{
    public function __construct(public readonly SewingReturn $sewingReturn)
    {
        parent::__construct('dry-run-rollback');
    }
}
