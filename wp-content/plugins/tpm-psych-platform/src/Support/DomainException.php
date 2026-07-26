<?php

declare(strict_types=1);

namespace TpmPsych\Support;

/**
 * Încălcarea unei reguli de business (ex. editarea unei versiuni de
 * instrument care nu mai e draft) — distinctă de erorile de validare a
 * inputului (TpmPsych\Validation\ValidationException).
 */
final class DomainException extends \RuntimeException
{
}
