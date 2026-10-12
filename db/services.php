<?php
// Services definition for local_flatarchive.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_flatarchive_get_tree' => [
        'classname'   => 'local_flatarchive\\external\\get_tree',
        'methodname'  => 'execute',
        'description' => 'Return course sections, supported activities and export availability.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/flatarchive:archive',
    ],
];
