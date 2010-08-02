<?php defined('SYSPATH') or die('No direct script access.');

Route::set('kohana-static-css', 
        'static/<action>/<filenames>',
        array(
            'filenames'=>'[^/]+'
        )
    )
    ->defaults(array(
        'controller' => 'static',
    ));
