<?php

header('Content-type: application/json');
define('MODE_STRICT', 1);
error_reporting(E_ALL | E_STRICT); 
set_time_limit(1);  // can only run 1 seconds. Thats much anyway.

$GLOBALS['debug'] = 0;
ini_set('display_errors', $GLOBALS['debug']);   

// ------------------------------------------------------
function MS()
{
	return intval(microtime(true) * 1000);	
}

$GLOBALS['sts'] = MS();

// ------------------------------------------------------
function TIMESTAMP()
{
	return date('Y-m-d H:i:s').'.'.sprintf('%03d', (MS() % 1000));	
}


// --------------------------------------------------------------
function AGENT_INFO()
{
    $info = array();
    $info['ip'] = $_SERVER['REMOTE_ADDR'];
    $info['host'] = gethostbyaddr($_SERVER['REMOTE_ADDR']);
    $info['agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '?';
    $info['lang'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '?';  // gives info about the language of the user
    return $info; 
}

// --------------------------------------------------------------
function AGENT_INFO_STR()
{
    return var_export(AGENT_INFO(), true);
}


// --------------------------------------------------------------
function err($msg)
{
    @error_log(TIMESTAMP().': ERROR: '.$msg);
    @error_log('AGENT: '.AGENT_INFO_STR());
    $ts = MS() - $GLOBALS['sts'];
    @error_log('Script Runtime: '.$ts.'ms');
    @session_destroy();
    usleep(rand(100000, 300000));  // thats between 100ms and 300ms 
    if($GLOBALS['debug']) echo 'ERROR: ',$msg;
    die();
}

// --------------------------------------------------------------
function DECRYPT($pwd, $jsondata)
{
    try
    {
        $salt = hex2bin($jsondata['s']);
        $iv  = hex2bin($jsondata['i']);
        $ciphertext = base64_decode($jsondata['c']);
        $key = hash_pbkdf2('sha256', $pwd, $salt, 999, 64);
        $key_bin = hex2bin($key);
        return openssl_decrypt($ciphertext, 'aes-256-cbc', $key_bin, OPENSSL_RAW_DATA, $iv);
    } 
    catch(Exception $e) 
    { 
        return ''; 
    }
}

// --------------------------------------------------------------
function ENCRYPT($pwd, $plain_text)
{
    try
    {
        $salt = openssl_random_pseudo_bytes(256);
        $iv = openssl_random_pseudo_bytes(16);
        $key = hash_pbkdf2('sha256', $pwd, $salt, 999, 64);
        $encrypted_data = openssl_encrypt($plain_text, 'aes-256-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);
        return array('c' => base64_encode($encrypted_data), 'i' => bin2hex($iv), 's' => bin2hex($salt));
    } 
    catch(Exception $e) 
    { 
        return array(); 
    }
}


$ok_come_from_api = 1;
require('config_dont_touch.php');

$GLOBALS['fp_log'] = $GLOBALS['config']['dir_sec_l'].'log_'.date('ymd').'_mail_checkin_php.y7';

ini_set('log_errors', 1);
ini_set('error_log', $fp_log);

//-------------------------------------------------
// ---------------------------- from here on logging shall work
//-------------------------------------------------

$input = file_get_contents('php://input');

$d = json_decode($input, true);  

if(!isset($d['m'])) err('m');

$m = ''.$d['m']; 

$mode = 0;

if($m == 'I confirm to use this code in intended ways only')
{
    $mode = 1;
    session_start();

    if(!isset($_SESSION['_MAIL']['r0'])) err('r0');
    $r0 = (int)$_SESSION['_MAIL']['r0'];
    unset($_SESSION['_MAIL']['r0']);

    if(!isset($_SESSION['_MAIL']['ts0'])) err('ts0');

    $ts0 = $_SESSION['_MAIL']['ts0'];
    $ts1 = MS();

    unset($_SESSION['_MAIL']['ts0']);
    $_SESSION['_MAIL']['ts1'] = $ts1;

    $ms = $ts1 - $ts0;
    if($ms < 10 || $ms > 5000) err('ms-imp');  // Time of script call to here cant be less than 5 ms and bigger 5sec

    if(!isset($d['rx'])) err('rx');
    if(!isset($d['tx'])) err('tx');
    
    $rx = (int)$d['rx'];
    $tx = (int)$d['tx'];
    
    if(''.$rx != $d['rx']) err('rxs');
    if(''.$tx != $d['tx']) err('txs');
    
    if($rx < 200000 || $rx > 2000000000) err('rxi');
    if($tx < 200000 || $tx > 2000000000) err('txi');
    
    $px = ''.$r0.'OK'.$rx.'_I_am_a_person_with_dignity_'.$tx; 
        
    $token = hash('sha256', rand(200000, 2000000000).'_I_do_respect_the_right_to_privacy'.rand(200000, 2000000000), false); 
    
    $_SESSION['_MAIL']['token'] = $token;
        
    $t = ENCRYPT($px, $token);
    
    unset($token);
    unset($px);

    usleep(rand(10000, 50000));  // thats between 10ms and 50ms 
    echo json_encode(array(1, $t));
    die();    
}
else if($m == 'I feel sick if I disrespect the privacy of others')
{
    $mode = 2;
}
else err("m-eth");

// -----------------------------------------------------------------------------------
// Form here on with $mode == 2

session_start();

if(!isset($_SESSION['_MAIL']['ts1'])) err('ts1');

$ts1 = $_SESSION['_MAIL']['ts1'];
unset($_SESSION['_MAIL']['ts1']);

$ts2 = MS();
$ms = $ts2 - $ts1;
if($ms < 10 || $ms > 5000) err('ms-imp');  // Time of script call to here cant be less than 10 ms and bigger 5sec

$_SESSION['_MAIL']['ts2'] = $ts2;

// ----------

if(!isset($_SESSION['_MAIL']['token'])) err('tok');

$tok = ''.$_SESSION['_MAIL']['token']; // will be needed again in mail_send.php

if(strlen($tok) != 64) err('cap-tok-64');

if(!isset($d['s'])) err('tok-s');
$s = $d['s'];
if(!isset($s['c']) || !isset($s['i']) || !isset($s['s']) ) err('tok-cis');

$msg = DECRYPT($tok, $s);

$ts = TIMESTAMP();

$pub_toc = hash('sha256', $ts.rand(2000000,2000000000), false); 

$tsf = preg_replace('/[\W]/', '_', $ts);

$fn = 'token_'.$tsf.'_'.$pub_toc.'.y7';
$fp = $GLOBALS['config']['dir_sec_d'].$fn;

if(file_exists($fp)) err('tok-f!!');

$content = array('ts' => $ts2,
                 'fn' => $fn,
                 'm' => $msg,
                 'a' => AGENT_INFO());

file_put_contents($fp, json_encode($content));

if(!file_exists($fp)) err('tok-f2');

$_SESSION['_MAIL']['pup_tok'] = $pub_toc;
$_SESSION['_MAIL']['tsf'] = $tsf;


usleep(rand(10000, 50000));  // thats between 10ms and 50ms 
echo json_encode(array(1, $pub_toc));
die();    
