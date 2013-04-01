<?php

 // BSD License.
 // Copyright(C) 2013-2013 RabiSoft
 // http://www.rabisoft.com

 include('../main.php');

 Main $p = new Main();
 
 $p->m_password = '';
 
 // https://code.google.com/apis/console/
 // "API Access" => "Simple API Access" => "Key for server apps" => "API key".
 $p->m_key = '';

 $p->DoAction();
 
?>
 