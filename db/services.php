<?php
// Services definition for local_flatarchive.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_flatarchive_get_tree' => [
        'classname'   => 'local_flatarchive\\external\\get_tree',
        'methodname'  => 'execute',
        'description' => 'Return course sections and selectable page modules.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/flatarchive:archive',
    ],
    'local_flatarchive_start_job' => [
        'classname'   => 'local_flatarchive\\external\\start_job',
        'methodname'  => 'execute',
        'description' => 'Start an archive job for selected pages.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/flatarchive:archive',
    ],
    'local_flatarchive_get_status' => [
        'classname'   => 'local_flatarchive\\external\\get_status',
        'methodname'  => 'execute',
        'description' => 'Get status for a job.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/flatarchive:archive',
    ],
];
