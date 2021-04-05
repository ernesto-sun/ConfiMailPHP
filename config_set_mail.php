<?php if(!isset($ok_come_from_config_script)) die();
$set_config = array(

  'api_key_allow' =>  0,          // NOTE: This is NOT a very safe method; allows direct exec, ONLY works if debug is on, only for testing
  'api_key'       =>  '8732i98898989ahdj',   // Min-Chars: 10
 
  'smtp_use' =>       0,          // If 0, the PHP internal mail()-function is used, otherwise your SMTP-server of choice    

  'smtp_host' =>      'mail.yourserver.com',    
  'smtp_port' =>      465,    
  'smtp_user' =>      '',         // The password is in conig_set_sec.php

  'email_to' =>       'contact@yourserver.com',      // Note: Only with AUTH other email-targets can be sent to
  'name_to' =>        'Contact',    

  'email_from' =>     '',    
  'name_from' =>      'Contact Form',    

  'subject_prefix' =>      '[CF]  ',    

  'email_reply' =>    '',         // Keep empty to use email_from
  'name_reply' =>     '',         // Keep empty to use name_from

);

