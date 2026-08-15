<?php

declare(strict_types=1);

/**
 * English validation overrides. Laravel's built-in messages are already
 * in English — we only need attribute aliases so users see friendly
 * field names instead of raw snake_case identifiers.
 */
return [
    'attributes' => [
        'imei_serial'          => 'IMEI / Serial Number',
        'service_id'           => 'service',
        'transfer_reference'   => 'transfer reference',
        'transfer_date'        => 'transfer date',
        'bank_account_id'      => 'bank account',
        'rejection_reason'     => 'rejection reason',
        'bank_name'            => 'bank name',
        'account_number'       => 'account number',
        'account_name'         => 'account holder',
    ],
];
