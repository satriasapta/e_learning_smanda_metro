<?php
defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\core\event\role_assigned',
        'callback'    => 'local_onerole_before_role_assign',
    ),
);