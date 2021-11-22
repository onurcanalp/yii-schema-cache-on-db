<?php

return array(
    'import' => array(
        'application.components.*',
    ),
    'components' => array(
        'db' => array(
            'connectionString' => "oci:dbname=XXX;charset=UTF8",
            'username' => 'Onur',
            'password' => 'Test',
            'driverMap' => array(
                'oci' => 'application.components.MyOciSchema',
            ),
        )
    )
);
