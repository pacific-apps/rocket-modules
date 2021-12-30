<?php

declare(strict_types=1);

/**
 * Handles user activation
 * Permission requires:
 */

if (!Checkpoint::proceed([

        # Type of authorization
        'type' => 'BASIC',

        # Required permission to access this endpoint
        'permissions' => ['ADMIN','WEB'],

        # Required user status to access this endpoint
        'status' => ['NEW']

    ])) {
    # NOTE: Exits the application when method returns false
    exit();
}
