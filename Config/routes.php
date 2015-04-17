<?php
Router::connect(
    '/google/auth/',
    array('controller' => 'GoogleCalendar', 'plugin' => 'GoogleCalendar','action'=>'auth')
);
?>
