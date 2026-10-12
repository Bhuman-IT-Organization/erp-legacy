<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| JWT Secure Key
|--------------------------------------------------------------------------
| Uses the application encryption key for signing tokens
*/
$config['jwt_key'] = APP_ENC_KEY;

/*
|--------------------------------------------------------------------------
| JWT Algorithm Type
|--------------------------------------------------------------------------
*/
$config['jwt_algorithm'] = 'HS256';

/*
|--------------------------------------------------------------------------
| Token Request Header Name
|--------------------------------------------------------------------------
| The header name to look for the token in requests
*/
$config['token_header'] = 'Authorization';

/*
|--------------------------------------------------------------------------
| Token Expire Time (in seconds)
|--------------------------------------------------------------------------
| ( 1 Day )   : 86400
| ( 7 Days )  : 604800
| ( 30 Days ) : 2592000
*/
$config['token_expire_time'] = 604800;
