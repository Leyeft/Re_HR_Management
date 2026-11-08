<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A request that is valid in form but breaks a company rule
 * (e.g. not enough leave left, deleting the last Admin).
 * The API returns it as 422 with a specific code the frontend can react to.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'business_rule',
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
