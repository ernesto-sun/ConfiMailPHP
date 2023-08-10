<?php

ob_start();

header('Content-type: text/javascript');
error_reporting(E_ALL | E_STRICT); 
define('MODE_STRICT', 1);
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

// --------------
function err($msg)
{
    @error_log(TIMESTAMP().': ERROR: '.$msg);
    @error_log('AGENT: '.AGENT_INFO_STR());
    $ts = MS() - $GLOBALS['sts'];
    @error_log('Script Runtime: '.$ts.'ms');
    @session_destroy();
    usleep(rand(100000,300000));  // thats between 100ms and 300ms 
    ob_end_clean();
    if($GLOBALS['debug']) echo 'ERROR: ',$msg;
    die();
}

// -----------------------------
function err_cap($wrong)
{
    $cor ='?';
    if(isset($_SESSION['securimage_code_value']))
    {
        if(isset($_SESSION['securimage_code_value']['default']))
        {
            $cor = $_SESSION['securimage_code_value']['default'];
        }
    }
    if($cor == '?') $cor = '?? ';
    
    $cor .= "SES: ".var_export($_SESSION, true);
    @error_log('Captcha failed: '.$wrong.' Correct: '.$cor.'  Agent: '.AGENT_INFO_STR());
    echo 'var _MAIL_err_cap=1, d;';
    echo 'for(d of document.querySelectorAll(".captcha-reload")) d.dispatchEvent(new Event("click"));';
    echo 'for(d of document.querySelectorAll(\'input[name="captcha-code"]\'))d.value = "";';

    usleep(rand(50000,100000));  // thats between 50ms and 100ms 
    ob_end_flush();
    die();
}

$ok_come_from_api = 1;
require('config_dont_touch.php');

$GLOBALS['fp_log'] = $GLOBALS['config']['dir_sec_l'].'log_'.date('ymd').'_mail_script.y7';

ini_set('log_errors', 1);
ini_set('error_log', $fp_log);

//-------------------------------------------------
// ---------------------------- from here on logging shall work
//-------------------------------------------------

if(!isset($_GET['r'])) err('r-p');
$r = (int)$_GET['r'];
if($r < 1 || $r > 2147483647) err('r-int');   // check positive integer range here, because number comes from user
if(''.$r != $_GET['r']) err('rs');

if(!isset($_GET['c'])) err('c-p');
$cap = $_GET['c'];
$no_cap = 0;
if(strlen($cap) != 1)  // only MATH_EASY is supported right now
{
    if(empty($cap) && 
        $GLOBALS['config']['allow_no_captcha'])
    {
        $no_cap = 1;
    }
    else
    {
        err('c-len');
    }
}

session_start();


if(!$no_cap)
{
    if(''.$cap != $_GET['c']) err('cap-s');

    require '_3p/securimage/securimage.php';
    $securimage = new Securimage();

    if (!empty($_GET['namespace'])) /* HACK: eto 20210510 */
    {
        $securimage->setNamespace($_GET['namespace']);
    }

    if($securimage->check($cap) == false) err_cap($cap);
}

$_SESSION['_MAIL']['r0'] = $r;
$_SESSION['_MAIL']['ts0'] = MS();

$uri_script = $_SERVER['SCRIPT_NAME'];
$fn_script = basename($uri_script);

if($fn_script != 'mail_script.php') err('pf');

$uri_path = substr($uri_script, 0, strlen($uri_script) - strlen($fn_script));

$url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$uri_path;   // url looks now like this: 'http://localhost/GitHub/ConfiMailPHP/', useful e.g. for image-src 

?>

var _MAIL_err_cap=0, _MAIL_tok;

// ---------------------------------
function _MAIL_rint()  // 2000000 <= random int >= 2000000000   
{
  return Math.round(Math.random() * 1998000000) + 2000000;
}

// ---------------------------------------------------
function _MAIL_POST(url, data)
{
  return new Promise(function(ok, no)
  {
    var req = new XMLHttpRequest();
    req.responseType = 'json';
    req.open('POST', url, true);  

    req.onload = function() 
    {
        if (this.status >= 200 && this.status < 400) 
        {
            ok(this.response);
        } 
        else 
        {  
            no('ERROR: POST failed: ' + url)
        }
    };
          
    req.onerror = function() 
    {
        no('AJAX Network failed: ' + url)
    }
    
    if(typeof data == 'object')
    {
        req.setRequestHeader('Content-type', 'application/json');
        data = JSON.stringify(data);
    }

    req.send(data);    
  });
}

// ------------------------------------
async function _MAIL_mprep(r0, r1, r2, t, m)
{
    const bh = (buf) => { return [...new Uint8Array (buf)].map (b => b.toString (16).padStart (2, '0')).join(''); },
          hb = (hex) => { return new Uint8Array(hex.match(/.{1,2}/g).map(b => parseInt(b, 16))); },
          bb6 = (buf) =>  
    {
      let r = '', i, by = new Uint8Array(buf), l = by.byteLength;
      for (i = 0; i < l; i++) r += String.fromCharCode(by[i]);
      return btoa(r);
    },
          b6b = (b64) =>
    {
      let bs = atob(b64), l = bs.length, by = new Uint8Array(l), i;
      for (i = 0; i < l; i++) by[i] = bs.charCodeAt(i);
      return by.buffer;
    };

    var enc = new TextEncoder('utf-8'),
        dec = new TextDecoder('utf-8'),
        k0 = await crypto.subtle.importKey('raw', enc.encode('' + r0 + 'OK' + r1 + '_I_am_a_person_with_dignity_' + r2), 
                                        'PBKDF2', false, ['deriveBits', 'deriveKey']),
        st = hb(t.s),
        iv = hb(t.i),
        k1 = await crypto.subtle.deriveKey({name: 'PBKDF2', salt: st, iterations: 999, hash: 'SHA-256' }, k0, 
                                        {name: 'AES-CBC', length: 256}, true, ['decrypt']),

        dy = await crypto.subtle.decrypt({name: 'AES-CBC', iv: iv}, k1, b6b(t.c)),
        k2 = dec.decode(dy),
        k3 = await crypto.subtle.importKey('raw', enc.encode(k2), 'PBKDF2', false, ['deriveBits', 'deriveKey']);

    st = crypto.getRandomValues(new Uint8Array(256)),
    iv = crypto.getRandomValues(new Uint8Array(16)); 
    k1 = await crypto.subtle.deriveKey({name: 'PBKDF2', salt: st, iterations: 999, hash: 'SHA-256'}, k3,
                                    {name: 'AES-CBC', length: 256}, true, ['encrypt']);
    _MAIL_tok = k2;       
    const c = await crypto.subtle.encrypt({name: 'AES-CBC', iv: iv}, k1, enc.encode(m));  

    return {c: bb6(c), s: bh(st), i: bh(iv)}
}

// ------------------------------------
async function _MAIL_send(msg, r0, lang) 
{
    try 
    {
        if(typeof lang != "string" || lang.length != 2) lang = "en";  // Language fallback to English

        const url = <?php echo "'", $url, "'";?>,
            rx = _MAIL_rint(), 
            tx = _MAIL_rint(), 
            u1 = url + '/mail_checkin.php',
            d = await _MAIL_POST(u1, {m: 'I confirm to use this code in intended ways only', rx: rx, tx: tx});
        if(d && d[0] == 1 && d.length == 2)
        {
            const m = await _MAIL_mprep(r0, rx, tx, d[1], msg);
            if(m && m.c && m.i && m.s)
            {
                const e = await _MAIL_POST(u1, {m: 'I feel sick if I disrespect the privacy of others', s: m});
                if(e && e[0] == 1 && e.length == 2)
                {
                    var tp = e[1];
                    if(typeof tp == 'string' && tp.length == 64)
                    {
                        const r3 = _MAIL_rint(),
                            r4 = _MAIL_rint(),
                            enc = new TextEncoder('utf-8'),
                            k = await crypto.subtle.importKey("raw", enc.encode(_MAIL_tok), {name: "HMAC", hash: {name: "SHA-256"}}, false, ["sign", "verify"]),
                            buf = await crypto.subtle.sign("HMAC", k, enc.encode('' + r3 + tp + r4));
                        _MAIL_tok = 0;
                        let r = '', i, by = new Uint8Array(buf), l = by.byteLength;
                        for (i = 0; i < l; i++) r += String.fromCharCode(by[i]);                    
                        const f = await _MAIL_POST(url + '/mail_send.php', {m: 'I will experience harm if I try to crack this script', t: tp, th: btoa(r), rx: r3, tx: r4});
                        if(f && f[0] == 1 && f.length == 1)
                        {
                            // all seems gone well
                            return 1;                    
                        }
                    }
                }
            }
        }
    }
    catch(ex) 
    {
        console.error('MAIL sending failed by exception: ', ex);
    }
    return 0;
}

<?php
usleep(rand(10000, 50000));  // thats between 10ms and 50ms 
ob_end_flush();
die();
