<?php
// Capabilities for local_flatarchive.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/flatarchive:archive' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
    ],
];
