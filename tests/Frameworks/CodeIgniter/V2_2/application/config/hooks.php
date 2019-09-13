<?php

use DDTrace\Configuration;
use DDTrace\SpanData;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/general/hooks.html
|
*/

$hooks = array();

require dirname(dirname(ini_get('ddtrace.request_init_hook'))) . '/bridge/dd_init.php';

/* End of file hooks.php */
/* Location: ./application/config/hooks.php */
