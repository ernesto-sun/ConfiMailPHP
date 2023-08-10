<?php

header('Content-type: application/json');
define('MODE_STRICT', 1);
error_reporting(E_ALL | E_STRICT); 
set_time_limit(20);  // can only run 4 seconds. Thats much anyway. PHPMailer has 1 sec timeout.

// One second PHPMailer timeout my seem strict, but if systems work well, 1 sec is a lot for one mail.
// And if systems do not work well, better scripts don't run anyway. 

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

// --------------
function err($msg)
{
    @error_log(TIMESTAMP().': ERROR: '.$msg);
    @error_log('AGENT: '.AGENT_INFO_STR());
    $ts = MS() - $GLOBALS['sts'];
    @error_log('Script Runtime: '.$ts.'ms');
    @session_destroy();
    usleep(rand(100000, 300000));  // thats between 100ms and 300ms 
    if($GLOBALS['debug']) echo 'ERROR: ', $msg, '\n\r';
    die();
}

// --------------
function msg($msg)
{
    if($GLOBALS['debug']) echo $msg, '\n\r'; 
}

// --------------
function warn($msg)
{
    @msg("WARNING: ".$msg);
    error_log(TIMESTAMP().': Warning: '.$msg);
}

// --------------
function important($msg)
{
    @msg("IMPORTANT: ".$msg);
    error_log(TIMESTAMP().': (!): '.$msg);
}


$ok_come_from_api = 1;
require('config_dont_touch.php');

$GLOBALS['fp_log'] = $GLOBALS['config']['dir_sec_l'].'log_'.date('ymd').'_mail_send_php.y7';

ini_set('log_errors', 1);
ini_set('error_log', $GLOBALS['fp_log']);

//-------------------------------------------------
// ---------------------------- from here on logging shall work
//-------------------------------------------------
$msg = "No message";
$info = "No info";
$fn = "ak";  // if ak-call, no filename otherwise

$ok_ak = 0;
if($GLOBALS['debug'])
{
    if(isset($_REQUEST['ak']))
    {
        if(!isset($GLOBALS['config']['api_key_allow']) || !$GLOBALS['config']['api_key_allow']) 
        {
            err("called by ak-param but allow_api_key is false");
        }

        if(!isset($GLOBALS['config']['api_key'])) 
        {
            err("called by ak-param but no api_key in config");
        }

        if(strlen($GLOBALS['config']['api_key']) < 10) err("api key not set or not long enough");

        if(trim($_REQUEST['ak']) === trim($GLOBALS['config']['api_key']))
        {
            important("Confi Mail PHP started using API-KEY");
            $ok_ak = 1;

            if(isset($_REQUEST['msg']))
            {
               $msg = ''.$_REQUEST['msg'];
            }
            $info = 'Sent by API-Key!!!\r\n\r\n'.AGENT_INFO_STR().'\r\nTS: '.TIMESTAMP();
        }
        else err("Invalid API-Key");
    }
}

if(!$ok_ak)
{
    $input = file_get_contents('php://input');

    $cc_input = strlen($input);
    if($cc_input < 10 || $cc_input > 1000) err('Invalid input data length: '.$cc_input); 

    $d = json_decode($input, true);  

    if(!isset($d['m'])) err('m');
    $m = ''.$d['m']; 

    if($m != 'I will experience harm if I try to crack this script') err('m-eth');

    if(!isset($d['rx'])) err('rx');
    if(!isset($d['tx'])) err('tx');

    $rx = (int)$d['rx'];
    $tx = (int)$d['tx'];

    if(''.$rx != $d['rx']) err('rxs');
    if(''.$tx != $d['tx']) err('txs');

    if($rx < 200000 || $rx > 2000000000) err('rxi');
    if($tx < 200000 || $tx > 2000000000) err('txi');

    session_start();

    if(!isset($_SESSION['_MAIL']['ts2'])) err('ts0');

    $ts2 = $_SESSION['_MAIL']['ts2'];
    unset($_SESSION['_MAIL']['ts2']);

    $ts = MS();

    $ms = $ts - $ts2;
    if($ms < 10 || $ms > 5000) err('ms-imp');  // Time of script call cant be less than 10 ms and bigger 5sec

    if(!isset($_SESSION['_MAIL']['pup_tok'])) err('pub-tok');
    $pub_toc = ''.$_SESSION['_MAIL']['pup_tok'];
    if(strlen($pub_toc) != 64) err('puptok64');
    unset($_SESSION['_MAIL']['pup_tok']);

    if(!isset($_SESSION['_MAIL']['tsf'])) err('tsf');
    $tsf = ''.$_SESSION['_MAIL']['tsf'];
    if(strlen($tsf) > 30) err('tsf-len');
    unset($_SESSION['_MAIL']['tsf']);

    if(!isset($d['t'])) err('tok-param');
    $t = ''.$d['t']; 
    if(strlen($t) != 64) err('tok-param64');

    if($t != $pub_toc) err('tok-chk failed: '.$t.'  CORRECT: '.$pub_toc);

    if(!isset($d['th'])) err('th-param');
    $th = ''.$d['th']; 
    if(strlen($th) > 64) err('th-g64');

    if(!isset($_SESSION['_MAIL']['token'])) err('tok-ses');

    $token = ''.$_SESSION['_MAIL']['token'];
    unset($_SESSION['_MAIL']['token']);
    if(strlen($token) != 64) err('tok-ses-64');

    $thm = ''.$rx.$pub_toc.$tx; 

    $th_check = base64_encode(hash_hmac('sha256', $thm, $token, true));
    if($th_check != $th) err('th-check');

    // ok, by here we should be safe. TODO: Expert Analysis

    $fn = 'token_'.$tsf.'_'.$pub_toc.'.y7';
    $fp = $GLOBALS['config']['dir_sec_d'].$fn;
    if(!file_exists($fp)) err('File needs to exist, but doesnt: '.$fp);

    $tok = json_decode(file_get_contents($fp), true);

    if(!isset($tok['m'])) err('tok-m');

    $msg = ''.$tok['m'];
    $info = AGENT_INFO_STR().'\r\nTS: '.TIMESTAMP();


    // -------------------------------
    // check for honeybot

    $hb_got = 0;

    $obj = json_decode($msg, true);
    if(is_array($obj))
    {
        if(count($obj) > 1)
        {
            $hb = $obj[count($obj) - 1];
            if(is_string($hb))
            {
                if(substr($hb, 0, 4) == "txt-")
                {
                    // seems we got an honeybot

                    $obj2 = array();
                    $hbv = '';

                    foreach($obj as $row)
                    {
                        if(is_array($row))
                        {
                            if($row[0] == $hb)
                            {
                                $hb_got = 1;
                                $hbv = $row[3];
                            }
                            else
                            {
                                $obj2[] = $row;   
                            }
                        }
                    }

                    $msg = json_encode($obj2);

                    if($hb_got)
                    {
                        if(!empty($hbv))
                        {
                            important("HONEYBOT-Alert. Something entered: '{$hbv}' into '{$hb}'");
                            $msg .= "\n\r\n\rHACKER ALERT!! Some script seems to have filled out the form.";
                        }    
                    }
                    else
                    {
                        err("Could not find the honeybot value, even it looked like!");
                    }            
                }
            }
        }
    } 


    if(!$hb_got)
    {
        if(!$GLOBALS['config']['allow_no_honeybot'])
        {
            err("No honeybot given but required!");
        }
    }

    // -------------------------------

}

// ---------------------------------------------------------------------------
// ---------------------------------------------------------------------------
// ---------------------------------------------------------------------------
// read config...

$smtp_use = 0;
if(isset($GLOBALS['config']['smtp_use']) && $GLOBALS['config']['smtp_use']) 
{
    $smtp_use = 1;
}

$email_from = trim($GLOBALS['config']['email_from']);
if(strlen($email_from) < 1) 
{
    err("Invalid Config, email_from is required!");
}
$name_from = $GLOBALS['config']['name_from'];

$email_to = trim($GLOBALS['config']['email_to']);
if(strlen($email_to) < 1) 
{
    err("Invalid Config, email_to is required!");
}
$name_to = $GLOBALS['config']['name_to'];

$email_reply = trim($GLOBALS['config']['email_reply']);
if(strlen($email_reply) < 1) 
{
    $email_reply = $email_from;
    $name_reply = $name_from;
}
else $name_reply = $GLOBALS['config']['name_reply'];

$subject_prefix = '[sys] ';
if(isset($GLOBALS['config']['subject_prefix'])) $subject_prefix = $GLOBALS['config']['subject_prefix'];

// ---------------------------------------------------------------------------

include '_3p/PHPMailer/PHPMailer.php';
include '_3p/PHPMailer/SMTP.php';
include '_3p/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Instantiation and passing `true` enables exceptions
$mail = new PHPMailer(true);

try 
{
    $mail->Timeout = 1000;

    if($smtp_use)
    {
        $smtp_host = $GLOBALS['config']['smtp_host'];
        $smtp_user = $GLOBALS['config']['smtp_user'];
        $smtp_pwd = $GLOBALS['config']['smtp_pwd'];
        $smtp_port = (int)$GLOBALS['config']['smtp_port'];
        
        if(strlen($smtp_host) < 1 ||
          strlen($smtp_user) < 1 ||
          strlen($smtp_pwd) < 1) err("SMTP Config!");

        //Server settings
        $mail->SMTPDebug = $GLOBALS['debug'] ? ($GLOBALS['verbose'] ? SMTP::DEBUG_LOWLEVEL : SMTP::DEBUG_SERVER) : SMTP::DEBUG_OFF;

        $mail->isSMTP();                                            //Send using SMTP
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         

        if($smtp_port < 1)
        {
            if($mail->SMTPSecure == PHPMailer::ENCRYPTION_SMTPS) $smtp_port = 465; // ENCRYPTION_SMTPS = 'ssl';
            else $smtp_port = 465; // ENCRYPTION_STARTTLS = 'tls';
        } 
    
        $mail->Host       = $smtp_host;                     //Set the SMTP server to send through
        $mail->Username   = $smtp_user;  
        $mail->Password   = $smtp_pwd;    
        $mail->Port       = $smtp_port;
    }
    
    $mail->setFrom($email_from, $name_from);
    $mail->addAddress($email_to, $name_to);     // This command can be repeated
    $mail->addReplyTo($email_reply, $name_reply);

    // $mail->addCC();
    // $mail->addBCC();
    // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
    // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

    // $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = $subject_prefix.' New mail from website';
    $mail->Body    = "Hello friend!\n\r\n\rA web-user sent this message: \n\r\n\r {$msg} \n\r\n\rAgent-Info: {$info}\n\r\n\rHave a nice day!\n\r";
    // $mail->AltBody = $mail->Body; // TODO: Care for HTML/Text Handling

    $mail->send();

    $ts = MS() - $GLOBALS['sts'];
    @error_log('OK: Sending Mail took: '.$ts.'ms; TOK: '.$fn);

}
catch (Exception $e) 
{
    err('PHPMailer: '.$mail->ErrorInfo);
}

usleep(rand(10000, 50000));  // thats between 10ms and 50ms 
echo json_encode(array(1));
die();    
