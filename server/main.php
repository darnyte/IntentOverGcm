<?php

 // GPL License.
 // Copyright(C) 2013-2013 RabiSoft
 // http://www.rabisoft.com

 class Main {

 public $m_password;
 public $m_key;
  
 private $m_db;

 function __construct() {

  $filepath = './data/data.db';

  $exists = file_exists($filepath);

  $this->m_db = sqlite_open($filepath);

  if( ! $exists ) {
   $this->CreateTable($this->m_db); 
  }

 }
  
 function __destruct() {

  sqlite_close($this->m_db);
  
 }
  
 function DoAction() { 
 
  $denger_action = $_REQUEST['action'];

  switch($denger_action) {
  case 'register':
   $this->DoRegister();
   break;
  case 'send':
   $this->DoSend();
   break;
  case 'list':
   $this->DoList();
   break;
  default:
   $this->ResultNg();
  }
  
 }
 
 function CreateTable() {
 
  $sql = <<<EOD
CREATE TABLE ids (id text, name text, primary key(id));
CREATE INDEX index_name ON ids(name);

EOD;

  sqlite_exec($this->m_db, $sql);
 
 }
 
 function DoRegister() {

  $denger_id = $_REQUEST['id'];
  if( ! $denger_id ) {
   $this->ResultNg();
   return;
  }

  $denger_name = $_REQUEST['name'];
  if( ! $denger_name ) {
   $this->ResultNg();
   return;
  }
  
  $denger_password = $_REQUEST['password'];
  if( $denger_password === $this->m_password ) {
  
   $escaped_id = sqlite_escape_string($denger_id);
   $escaped_name = sqlite_escape_string($denger_name);

   $sql = <<<EOD
INSERT OR REPLACE INTO ids (id, name) VALUES (
 '${escaped_id}' ,
 '${escaped_name}'
 );
 
EOD;

   sqlite_exec($this->m_db, $sql);
   
  }
 
  $this->ResultOK();
  
 }
 
 function DoSend() {

  $denger_message = $_REQUEST['message'];
  if( ! $denger_message ) {
   $this->ResultNg();
   return;
  }

  $denger_name = $_REQUEST['name'];
  $denger_id = $_REQUEST['id'];
  if( (! $denger_name) && (! $denger_id) ) {
   $this->ResultNg();
   return;
  }

  $denger_password = $_REQUEST['password'];
  if( $denger_password === $this->m_password ) {

   if( $denger_name ) {
    $denger_ids = $this->DengerGetIdsByName($denger_name);
   }
  
   if( $denger_id ) {
    $denger_ids = $this->DengerGetIdsWithoutId($denger_id);
   }
  
   if( $denger_ids ) {
    $result = $this->Post($denger_ids, $denger_message);
    $this->Update($denger_ids, $result);
   }
   
  }
  
  $this->ResultOk();
  
 }
 
 function Post($denger_ids, $denger_message) {

  $uri = 'https://android.googleapis.com/gcm/send';

  $header = array(
   'Content-Type: application/json',
   'Authorization: key=' . $this->m_key ,
  );

  $denger_json_ids = json_encode($denger_ids);
  
  $post = <<<EOD
{
 "registration_ids" : ${denger_json_ids},
 "data" : {
  "message" : "${denger_message}"
 }
}

EOD;

  $ch = curl_init($uri);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  $result = curl_exec($ch);
  curl_close($ch);
  
  return $result;
 
 }
 
 function DengerGetIdsWithoutId($denger_id) {

  $escaped_id = sqlite_escape_string($denger_id);

  $sql = <<<EOD
SELECT * FROM ids WHERE id <> '${escaped_id}';

EOD;

  $result = sqlite_query($this->m_db, $sql);
  $count = sqlite_num_rows($result);
  
  for($i = 0; $i < $count; ++ $i) {

    $denger_columns = sqlite_fetch_array($result);

    $denger_idSend = $denger_columns['id'];
    $denger_ids[] = $denger_idSend;

  }

  return $denger_ids; 
 
 }
 
 function DengerGetIdsByName($denger_name) {

  $escaped_name = sqlite_escape_string($denger_name);

  $sql = <<<EOD
SELECT * FROM ids WHERE name = '${escaped_name}';

EOD;

  $result = sqlite_query($this->m_db, $sql);
  $count = sqlite_num_rows($result);
  
  for($i = 0; $i < $count; ++ $i) {

   $denger_columns = sqlite_fetch_array($result);
   $denger_idSend = $denger_columns['id'];
   $denger_ids[] = $denger_idSend;

  }

  return $denger_ids; 
 
 }

 function Update($denger_ids, $denger_json) {
 
  $denger_temp = json_decode($denger_json, true);
  if( $denger_temp === $denger_json ) {
   echo $denger_temp;
   return;
  }
  
  $denger_results = $denger_temp['results'];

  $count = count($denger_results);

  for($i = 0; $i < $count; ++$i) {
   
   $denger_result = $denger_results[$i];

   $denger_idOld = $denger_ids[$i];
   $escaped_idOld = sqlite_escape_string($denger_idOld);
   
   $denger_error = $denger_result['error'];
   
   // Not Tested.
   switch($denger_error) {
   case 'InvalidRegistration':
   case 'NotRegistered':
    $sql .= <<<EOD
DELETE FROM ids WHERE id = '${escaped_idOld}';

EOD;
    break;
   default:
    // do nothing.
   }
   
   $denger_idNew = $denger_result['registration_id'];
   
   // Not Tested.
   if($denger_idNew) {
    $escaped_idNew = sqlite_escape_string($denger_idNew);
    $sql .= <<<EOD
UPDATE ids SET id = '${escaped_idNew}' WHERE id = '${escaped_idOld}';

EOD;
   }
   
  }
  
  if($sql) {
   sqlite_exec($this->m_db, $sql);
  }
  
 }
 
 function DoList() {

  $sql = <<<EOD
SELECT * FROM ids;

EOD;

  $denger_names = array();

  $denger_password = $_REQUEST['password'];
  if( $denger_password === $this->m_password ) {

   $result = sqlite_query($this->m_db, $sql);
   $count = sqlite_num_rows($result);

   for($i = 0; $i < $count; ++ $i) {

    $denger_columns = sqlite_fetch_array($result);

    $denger_name = $denger_columns['name'];
    $denger_names[] = $denger_name;

   }

  }

  $denger_json_names = json_encode($denger_names);
 
  echo <<<EOD
{ 
 "result" : "OK",
 "names" : ${denger_json_names}
}
  
EOD;

 }
 
 function ResultOk() {

  $this->Result("OK");
 
 }

 function ResultNg() {

  $this->Result("NG");
 
 }

 function Result($result) {

  echo <<<EOD
{ "result" : "${result}" }

EOD;
 
 }
 
 }
 
?>
 