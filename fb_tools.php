#!/usr/bin/php
<?php $ver = "fb_tools 0.16 (c) 20.07.2016 by Michael Engelke <http://www.mengelke.de>"; #(charset=iso-8859-1 / tabs=8 / lines=lf)

if(!isset($cfg)) {					// $cfg schon gesetzt?
 $cfg = array(
	'host'	=> 'fritz.box',		// Fritz!Box-Addresse
	'pass'	=> 'password',		// Fritz!Box Kennwort
	'user'	=> false,		// Fritz!Box Username (Optional)
	'port'	=> 80,			// Fritz!Box HTTP-Port (Normalerweise immer 80)
	'fiwa'	=> 100,			// Fritz!Box Firmware (Nur Intern)
	'upnp'	=> 49000,		// Fritz!Box UPnP-Port (Normalerweise immer 49000)
	'pcre'	=> 16*1024*1024,	// pcre.backtrack_limit
	'sbuf'	=> 4096,		// TCP/IP Socket-Buffergr��e
	'tout'	=> 3,			// TCP/IP Socket-Timeout
	'upda'	=> 60*60*24*100,	// Auto-Update Periode (Kein Update: 0)
	'wrap'	=> 'auto',		// Manueller Wortumbruch (Kein Umbruch: 0)
	'char'	=> 'auto',		// Zeichenkodierung der Console (auto/ansi/oem/utf8)
	'dbfn'	=> 'debug#.txt',	// Template f�r Debug-Dateien
	'time'	=> 'Europe/Berlin',	// Zeitzone festlegen
	'drag'	=> 'konfig fcs,-d',	// Drag'n'Drop-Modus
	'help'	=> false,		// Hilfe ausgeben
	'dbug'	=> false,		// Debuginfos ausgeben
	'oput'	=> false,		// Ausgaben speichern
#	'error' => array(),		// Fehlerlogs
#	'preset'=> array(),		// Leere Benutzerkonfiguration
#	'boxinfo'=>array(),		// Leere Boxinfo Daten
	'usrcfg'=> 'fb_config.php',	// Filename der Benutzerkonfiguration
 );
}
if(!function_exists('array_combine')) {			// http://php.net/array_combine
 function array_combine($key,$value) {
  $array = false;
  if(is_array($key) and is_array($value) and count($key) == count($value)) {
   $array = array();
   while(list($kk,$kv) = each($key) and list($vk,$vv) = each($value))
    $array[$kv] = $vv;
  }
  return $array;
 }
}
if(!function_exists('utf8_encode')) {			// http://php.net/utf8_encode (Fallback auf 7bit)
 function utf8_encode($str) {
  return preg_replace('/[\x80-\xff]+/','?',$str);
 }
 $cfg['utf8'] = false;
}
if(!function_exists('utf8_decode')) {			// http://php.net/utf8_decode (Fallback auf 7bit)
 function utf8_decode($str) {
  return preg_replace('/[\x80-\xff]+/','?',$str);
 }
 $cfg['utf8'] = false;
}
if(!function_exists('file_put_contents')) {		// http://php.net/file_put_contents
 function file_put_contents($file,$data,$opt=0) {	// $opt ist nicht vollst�ndig implemmentiert
  if($fp = fopen($file,($opt/(1<<3)%2) ? 'a' : 'w')) {	// FILE_APPEND -> 8
   if(is_array($data))
    $data = implode('',$data);
   if($opt/(1<<1)%2) {	// LOCK_EX -> 2
    if(flock($fp,2)) {	// flock LOCK_EX
     fputs($fp,$data);
     flock($fp,3);	// flock LOCK_UN
    }
    else {
     fclose($fp);
     return null;
    }
   }
   else
    fputs($fp,$data);
   fclose($fp);
   $fp = strlen($data);
  }
  return $fp;
 }
}
function crc_32($str,$file=false) {			// Berechnet die CRC32 Checksume von einen String (Optional von einer Datei)
 return str_pad(sprintf('%X',crc32(($file) ? file_get_contents($str) : $str)),8,0,STR_PAD_LEFT);
}
function ifset(&$x,$y=false) {				// Variabeln pr�fen
 return (isset($x) and ($x or $x != '')) ? (($y and is_string($x) and $y{0} == '/' and preg_match($y,$x,$z)) ? $z : (($y) ? ((is_array($x)) ? array_search($y,$x) : $x == $y) : !$y)) : false;
}
function out($str,$mode=0) {				// Textconvertierung vor der ausgabe (mode: 0 -> echo / 1 -> noautolf / 2 -> debug)
 global $cfg;
 if($str) {
  if(is_array($str))
   $str = print_r($str,true);
  if(!($mode/(1<<1)%2) and preg_match('/\S$/D',$str))		// AutoLF
   $str .= "\n";
  if($mode/(1<<2)%2)						// Unn�tige Whitespaces im Debug-Modus l�schen
   $str = preg_replace('/(?<=\n\n|\r\n\r\n)\s+$/','',$str);
  if($cfg['oput'] and !($mode/(1<<2)%2))			// Ausgabe speichern
   file_put_contents($cfg['oput'],$str,8);
  if((int)$cfg['wrap'] and !ifset($cfg['char'],'/7bit|(13{2}|utf)7/'))	// Wortumbruch
   $str = wordwrap($str,$cfg['wrap']-1,"\n",true);
  if(ifset($cfg['char'],'/^(dos|oem|c(odepage|p)?(437|850))$/'))
   $str = str_replace(array('�','�','�','�','�','�','�','�',"\n"),array(chr(132),chr(148),chr(129),chr(225),chr(142),chr(153),chr(154),chr(21),"\r\n"),$str);
  elseif($cfg['char'] == 'utf8')
   $str = utf8_encode($str);
  elseif($cfg['char'] == 'html')
   $str = str_replace(array('&','<','>','"',"'",'�','�','�','�','�','�','�'),
    array('&amp;','&lt;','&gt;','&quot;',"&#39;",'&auml;','&ouml;','&uuml;','&szlig;','&Auml;','&Ouml;','&Uuml;'),$str);
  elseif(ifset($cfg['char'],'/^\d+$/') and dechex($cfg['char']) == 539
   and preg_match_all('/([a-z\d])(.)|(.)(..)/i','1I2Z3E4A5S6G7T8B9g0Oa4A4b8B8c<C(e3E3g6G6h#H#i!I!l1L1o0O0q9Q9s5S5t7T7x+X+z2Z2�4:�4:�0:�0:�u:�U:�55�55',$var)
   and preg_match_all('/(.)(.+)/',implode("\n",$var[0]),$var))
    $str = strtr($str,array_combine($var[1],$var[2]));
  elseif(!ifset($cfg['char'],'/^(ansi|(codepage|cp)1252|iso.?8859.?1)$/i')) /* if($cfg['char'] == '7bit') */
   $str = str_replace(array('�','�','�','�','�','�','�','�'),array('ae','oe','ue','ss','Ae','Oe','Ue','SS'),$str);
  if((int)$cfg['wrap'] and ifset($cfg['char'],'/7bit|(13{2}|utf)7/'))	// Wortumbruch
   $str = wordwrap($str,$cfg['wrap'],"\n",true);
 }
 return ($mode/(1<<0)%2) ? $str : print $str;
}
function dbug($str,$file=false,$mode=4) {		// Debug-Daten ausgeben/speichern (mode: 3 -> NoTime)
 global $cfg;
 $time = ($cfg['dbug']/(1<<2)%2 and !($mode/(1<<3)%2)) ? number_format(array_sum(explode(' ',microtime()))-$cfg['time'],2,',','.').' ' : '';
 if($cfg['dbug']/(1<<1)%2 and $cfg['dbfn'] and $file)	// Debug: Array in separate Datei sichern
  if(strpos($file,'#') and is_array($str))
   foreach($str as $key => $var)			// Debug: Array in mehrere separaten Dateien sichern
    file_put_contents(str_replace('#',"-".str_replace('#',$key,$file),$cfg['dbfn']),$time.((is_array($var)) ? print_r($var,true) : $var),8);
  else
   file_put_contents(str_replace('#',"-$file",$cfg['dbfn']),$time.((is_array($str)) ? print_r($str,true) : $str),8);	// Alles in EINE Datei Sichern
 else {
  if(is_string($str)) {
   if(preg_match('/^\$(\w+)$/',$str,$var) and isset($GLOBALS[$var[1]]))	// GLOBALS Variable ausgeben
    $str = "$str => ".(is_array($GLOBALS[$var[1]]) ? print_r($GLOBALS[$var[1]],true) : $GLOBALS[$var[1]]);
   elseif(!($mode/(1<<1)%2) and preg_match('/\S$/D',$str))// AutoLF
    $str .= "\n";
  }
  elseif(is_array($str))
   $str = print_r($str,true);
  if($cfg['dbug']/(1<<1)%2 and $cfg['dbfn'])		// Debug: Ausgabe/Speichern
   file_put_contents(str_replace('#','',$cfg['dbfn']),$time.$str,8);
  else
   out($time.$str,$mode | 4);
 }
}
function errmsg($msg,$name='main') {			// Fehlermeldung(en) Sichern
 global $cfg;
 if($msg)			// Fehlermeldung speichern
  $cfg['error'][$name][] = trim($msg);
 else				// Fehlermeldung abrufen
  while(isset($cfg['error'][$name]) and is_array($cfg['error'][$name]))	// Fehlermeldung vorhanden?
   if($val = end($cfg['error'][$name]) and preg_match('/^\w+$/',$val))	// M�glicher Rekusive Fehlermeldung?
    $name = $val;		// N�chste Fehlermeldung suchen
   else
    return $val;		// Fehlermeldung ausgeben
 return false;			// Funktion fehlgeschlagen
}
function request($method,$page='/',$body=false,$head=false,$host=false,$port=false) {	// HTTP-Request durchf�hren
 global $cfg;
 if(is_array($method))					// Restliche Parameter aus Array holen
  extract($method);
 foreach(array('host','port') as $var)			// Host & Port setzen
  if(!$$var)
   $$var = $cfg[$var];
 if(!$head)						// Head Initialisieren
  $head = $cfg['head'];
 if($mode = preg_match('/^(\w+)(?:-(.+))?/',$method,$var)) {
  $method = strtoupper($var[1]);
  $mode = (isset($var[2])) ? $var[2] : (($var[1] == strtolower($var[1])) ? 'array' : false);	// Result-Modus festlegen
 }
 if($cfg['dbug']/(1<<5)%2)
  dbug("$host:$port ");
 if($fp = @fsockopen($host,$port,$errnr,$errstr,$cfg['tout'])) {	// Verbindung aufbauen
  stream_set_timeout($fp,$cfg['tout']);			// Timeout setzen
//  stream_set_blocking($fp,0);
  if($method == 'POST') {				// POST-Request vorbereiten
   if(is_array($body)) {				// Multipart-Post vorbereiten
    $row = "---".md5(rand().time());
    foreach($body as $key => $var) {
     $val = array('','');
     if(is_array($var))					// Unter-Header im Header
      foreach($var as $k => $v)
       if($k == '')					// Content
        $var = $v;
       elseif($k == 'filename')				// Weitere Angaben im Header
        $val[0] .= "; $k=\"$v\"";
       else						// Sub-Header
        $val[1] = "$k: $v\r\n";
     $body[$key] = "$row\r\nContent-Disposition: form-data; name=\"$key\"$val[0]\r\n$val[1]\r\n$var\r\n";
    }
    $body = implode('',$body)."$row--\r\n";
    $var = "multipart/form-data; boundary=$row";
   }
   else
    $var = 'application/x-www-form-urlencoded';		// Standard Post
   if(!isset($head['content-type']))
    $head['content-type'] = $var;
   if(!isset($head['content-length']))
    $head['content-length'] = strlen($body);
   $body = "\r\n$body";
  }
  elseif($method == 'GET' and $body) {			// GET-Request vorbereiten
   $page .= "?$body";
   $body = "\r\n";
  }
  else
   $body = "\r\n";
  if(!isset($head['host']))				// Host zum Header hinzuf�gen
   $head['host'] = $host;
  if(!isset($head['connection']))			// Connection zum Header hinzuf�gen
   $head['connection'] = "Closed";
  foreach($head as $key => $var)			// Header vorbeireiten
   $head[$key] = ucwords($key).": $var";
  $head = "HTTP/1.1\r\n".implode("\r\n",$head)."\r\n";
  if($cfg['dbug']/(1<<5)%2)				// Debug Request
   dbug("$method $page".(($cfg['dbug']/(1<<7)%2) ? " $head$body\n\n" : ''),'RequestPut');
  fputs($fp,"$method $page $head$body");		// Request Absenden
  if($mode == 'putonly')				// Nur Upload durchf�hren
   return fclose($fp);
  $rp = "";						// Antwort vorbereiten
  if(preg_match('/(?:save|down(?:load)?):(.*)/',$mode,$file)) {	// Download -> Datei
   while(!feof($fp)) {
    $rp .= fread($fp,$cfg['sbuf']);
    if($pos = strpos($rp,"\r\n\r\n")) {			// Header/Content trennen
     $header = substr($rp,0,$pos);
     $rp = substr($rp,$pos+4);
     $file[1] = preg_replace('/(?<=\/)$|^$/',($file[1]
	and preg_match('/^Content-Disposition:\s*(?:attachment;\s*)?filename=(["\']?)(.*?)\1\s*$/mi',$header,$var))
	? $var[2] : 'file.bin',$file[1]);
     if($cfg['dbug']/(1<<0)%2)
      dbug("Downloade '$file[1]'".((preg_match('/Content-Length:\s*(\d+)/',$header,$var)) ? " ".number_format($var[1],0,'.',',')." Bytes" : ""));
     if($sp = fopen($file[1],'w')) {
      fputs($sp,$rp);
      while(!feof($fp)) {
       fputs($sp,fread($fp,$cfg['sbuf']));
       if($cfg['dbug']/(1<<0)%2)
        dbug(".",0,10);
      }
      fclose($sp);
      if($cfg['dbug']/(1<<0)%2)
       dbug("\n",0,8);
      $rp = $header;
     }
     else
      return errmsg("$file[1] kann nicht zum Schreiben ge�ffnet werden",__FUNCTION__);
    }
   }
  }
  else
   while(!feof($fp)) {
    $rp .= fread($fp,$cfg['sbuf']);
    if($cfg['dbug']/(1<<6)%2)
     dbug(".",0,10);
  }
  fclose($fp);
  if($cfg['dbug']/(1<<6)%2)
   dbug("\n",0,8);
  $fp = $rp;
  if($cfg['dbug']/(1<<6)%2)				// Debug Response
   dbug((($cfg['dbug']/(1<<7)%2) ? $rp : preg_replace('/\n.*$/s','',$rp))."\n\n",'RequestGet');
  if($mode != 'raw' and preg_match('/^(http[^\r\n]+)(.*?)\r\n\r\n(.*)$/is',$rp,$array)) {	// Header vom Body trennen
   if($mode == 'array') {
    $fp = array($array[1]);
    if(count($array) > 0 and preg_match_all('/^([^\s:]+):\s*(.*?)\s*$/m',$array[2],$array[0]))
     foreach($array[0][2] as $key => $var)
      $fp[ucwords($array[0][1][$key])] = $var;
    $fp[1] = $array[3];
   }
   else
    $fp = $array[3];
  }
 }
 else
  errmsg("$host:$port - Fehler $errnr: $errstr",__FUNCTION__);
 return $fp;
}
function response($xml,$pass,$page=false) {		// Login-Response berechnen
 if(preg_match('!<Challenge>(\w+)</Challenge>!',$xml,$var)) {
  $hash = "response=$var[1]-".md5(preg_replace('!.!',"\$0\x00","$var[1]-$pass"));
  if($page and $GLOBALS['cfg']['fiwa'] == 100)
   $GLOBALS['cfg']['fiwa'] = (substr($page,-4) == '.lua') ? '530' : '474';
  return $hash;
 }
 else
  return errmsg('Keine Challenge erhalten',__FUNCTION__);
}
function login($pass=false,$user=false) {		// In der Fritz!Box einloggen
 global $cfg;
 foreach(array('user','pass') as $var)			// User & Pass setzen
  if(!$$var)
   $$var = $GLOBALS['cfg'][$var];
 $bug = (($user) ? " $user@" : " ")."$cfg[host] - Methode";
 $sid = $rp = $err = false;
 if($cfg['fiwa'] == 100 or $cfg['fiwa'] > 479) {
  if($cfg['dbug']/(1<<0)%2)
   dbug("Ermittle Boxinfos");
  if($data = request('GET-array','/jason_boxinfo.xml') and preg_match_all('!<j:(\w+)>([^<>]+)</j:\1>!m',$data[1],$array)) {	// BoxInfos holen
   if($cfg['dbug']/(1<<4)%2)
    dbug($array);
   $cfg['boxinfo'] = array_combine($array[1],$array[2]);
   $cfg['boxinfo']['Time'] = strtotime($data['Date']);
   if(preg_match('/^\d+\.0*(\d+?)\.(\d+)$/',$cfg['boxinfo']['Version'],$var))	// Firmware-Version sichern
    $cfg['fiwa'] = $var[1].$var[2];
  }
  elseif(!$data)
   $err = ", keine Antwort";
 }
 if(!$err and $cfg['fiwa'] == 100 or $cfg['fiwa'] > 529) {	// Login lua ab 05.29
  if($cfg['dbug']/(1<<0)%2)
   dbug("Login$bug SID.lua (5.30)");
  $page = "/login_sid.lua";
  if($rp = request('GET',$page) and ($auth = response($rp,$pass,$page)) and $rp = request('POST',$page,(($user) ? "$auth&username=$user" : $auth))) {
   if(preg_match('/<SID>(\w+)<\/SID>/',$rp,$var)) {
    if($cfg['fiwa'] == 100)
     $cfg['fiwa'] = 530;
    if(hexdec($var[1]) != 0)
     $sid = $var[1];
    else
     $err = ", SID.lua ist ung�ltig";
   }
  }
  elseif(!$rp)
   $err = ", keine Antwort";
 }
 if(!$sid and !$err and ($cfg['fiwa'] == 100 or $cfg['fiwa'] > 473)) {	// Login cgi ab 04.74 (Zwischen 4.74 bis 5.29)
  if($cfg['dbug']/(1<<0)%2)
   dbug("Login$bug SID.xml (4.74)");
  $page = "/cgi-bin/webcm";
  $data = "getpage=../html/login_sid.xml";
  if(!$rp or !$auth = response(($rp),$pass,$page))
   if($auth = request('GET',"$page?$data"))
    $auth = response($auth,$pass,$page);
   else
    $err = ", keine Antwort";
  if($auth and preg_match('/<SID>(\w+)<\/SID>/',request('POST',$page,"$data&login:command/$auth"),$var)) {
   if($cfg['fiwa'] == 100)
    $cfg['fiwa'] = 474;
   if(hexdec($var[1]) != 0)
    $sid = $var[1];
   else
    $err = ", SID.xml ist ung�ltig";
  }
 }
 if(!$sid and !$err and ($cfg['fiwa'] == 100 or $cfg['fiwa'] < 490)) {	// Login classic bis 4.89 (z.B. FRITZ!Repeater N/G)
  if($cfg['dbug']/(1<<0)%2)
   dbug("Login$bug PlainText");
  if($var = request('POST',$page,"login:command/password=$pass") and !preg_match('/Anmeldung/',$var))
   $sid = true;
  elseif(!$var)
   $err = ", keine Antwort";
 }
 return ($cfg['sid'] = $sid) ? $sid : errmsg("Anmeldung fehlgeschlagen$err",__FUNCTION__);
}
function logout($sid) {					// Aus der Fritz!Box ausloggen
 if($GLOBALS['cfg']['dbug']/(1<<0)%2)
  dbug("Logout ".$GLOBALS['cfg']['host']);
 if(is_string($sid) and $sid)				// Ausloggen
  request('GET',(($GLOBALS['cfg']['fiwa'] < 529) ? "/cgi-bin/webcm" : "/login_sid.lua"),"security:command/logout=1&logout=1&sid=$sid");
}
function supportcode($str = false) {			// Supportcode aufschl�sseln
 return ($str or $str = request('GET','/cgi-bin/system_status')) ? ((preg_match('!
	([^<>]+?)-
	(\w+)-
	([01]\d|2[0-3])([0-2]\d|3[01])(0\d|1[01])-
	([0-2]\d|3[01])([0-5]\d|6[0-3])([0-2]\d|3[01])-
	([0-7]{6})-
	([0-7]{6})-
	(1[49]|21|78|8[35])(67|79)(\d\d)-(\d{2,3})(\d\d)(\d\d)-
	(\d+)-
	(\w+)(?:-(\w+))?
	!x',$str,$array))
  ?	"\nModell: $array[1]\n"
	."Firmware: $array[14].$array[15].$array[16]\n"
	."Version: $array[17]\n"
	.((ifset($array[19])) ? "Sprache: ".strtr($array[19],array('de' => 'Deutsch', 'en' => 'Englisch'))."\n" : '')
	."Branding: $array[18]\n"
	."Annex: $array[2]\n\n"
	."Laufzeit:".preg_replace(array('/(?<= )0+ \w+(, )?|(?<= )0+(?=\d)/','/, $/','/( 1 \w+)\w(?=,|\s)/'),array('','','$1')," $array[6] Jahre, $array[5] Monate, $array[4] Tage, $array[3] Stunden\n")
	."Neustarts: " .($array[7] * 32 + $array[8])."\n\n"
	."debug.cfg: ".(($array[11]%64 == 14) ? "Nicht v" : "V")."orhanden"./*(($array[11]%64 == 19) ? "" : "!!!").*/"\n"
	."fw_attrib: " .(($array[11] < 64) ? "Modifiziert" : "Unver�ndert")."\n\n"
	."OEM: ".(($array[12] == 67) ? "Custom" : "Original")."\n"
	."RunClock: $array[13]\n"
  :	"Unbekannt: $str") : errmsg('request',__FUNCTION__);
}
function upnprequest($page,$ns,$rq,$exp=false) {	// UPnP Request durchf�hren
 return ($rp = request(array(
	'method' => 'POST',
	'page' => $page,
	'body' => utf8_encode("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
	."<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">\n"
	."<s:Body><u:$rq xmlns:u=$ns /></s:Body>\n</s:Envelope>"),
	'head' => array_merge($GLOBALS['cfg']['head'],array('content-type' => 'text/xml; charset="utf-8"', 'soapaction' =>  "\"$ns#$rq\"")),
	'port' => $GLOBALS['cfg']['upnp'])))
  ? (($exp) ? ((preg_match("/<$exp>(.*?)<\/$exp>/",$rp,$var)) ? $var[1] : errmsg('Kein Erwartetes Ergebnis erhalten',__FUNCTION__)) : $rp)
  : errmsg('request',__FUNCTION__);
}
function getupnppath($urn) {				// Helper f�r UPnP-Requests
 return ($rp = request(array('method' => 'GET', 'page' => '/igddesc.xml', 'port' => $GLOBALS['cfg']['upnp']))
	and preg_match("!<(service)>.*?<(serviceType)>(urn:[^<>]*".$urn."[^<>]*)</\\2>.*?<(controlURL)>(/[^<>]+)</\\4>.*?</\\1>!s",$rp,$var))
	? array($var[3],$var[5]) : errmsg('request',__FUNCTION__);
}
function getexternalip() {				// Externe IPv4-Adresse �ber UPnP ermitteln
 return ($val = getupnppath('WANIPConnection') and $var = upnprequest($val[1],$val[0],'GetExternalIPAddress','NewExternalIPAddress'))
  ? $var : errmsg('request',__FUNCTION__);
}
function forcetermination() {				// Internetverbindungen �ber UPnP neu aufbauen
 return ($val = getupnppath('WANIPConnection') and $var = upnprequest($val[1],$val[0],'ForceTermination','NewExternalIPAddress'))
  ? $var : errmsg('request',__FUNCTION__);
}
function saverpdata($file,$data,$name) {		// HTTP-Downloads in Datei speichern
 $file = preg_replace('/[<>\[\]:\/\\\\"*?|]/','_',($file) ? $file : ((@preg_match('/filename="(.*)"/',$data['Content-Disposition'],$var)) ? $var[1] : $name));
 $cfg['file'] = $file;
 return file_put_contents($file,$data[1]);
}
function supportdata($file=false,$sid=false) {		// Supportdaten anfordern
 if(!$sid)
  $sid = $GLOBALS['cfg']['sid'];
 $array = array();
 if($sid and $sid !== true)
  $array['sid'] = $sid;
 $array['SupportData'] = '';
 $data = ($file) ? (bool)request("POST-save:$file",'/cgi-bin/firmwarecfg',$array) : request("POST-array",'/cgi-bin/firmwarecfg',$array);
 return ($data) ? $data : errmsg('request',__FUNCTION__);
}
function supportdataextrakt($data,$mode=false) {	// Supportdaten extrahieren
 $info = array();
 if($mode == 'sec') {
  $preg = '/^#{5} +BEGIN +SECTION +(\S+) *([^\r\n]+\s*)?(.*?)#{5} +END +SECTION +\1\s+/sm';
  if(preg_match_all($preg,$data,$array)) {
   if($GLOBALS['cfg']['dbug']/(1<<4)%2)
    dbug($array,'SupportDataExtrakt-#');
   foreach($array[1] as $key => $var)
    if(trim($array[3][$key]))
     $info = array_merge($info,(($val = trim(preg_replace($preg,'',$array[3][$key]))) ? array($var => $array[2][$key].$val) : array()),supportdataextrakt($array[3][$key],'sec'));
  }
 }
 elseif(substr($data,0,5) == '#####' and $array = supportdataextrakt($data,'sec')) {
  $mstr = $mlen = array(0,0);
  $val = $list = array();
  foreach($array as $key => $var) {			// Maximale L�ngen ermitteln
   file_put_contents("$key.txt",$var);
   $len = number_format(strlen($var),0,",",".");
   $list[] = array($key,$len);
   $c = (count($list)-1 < count($array)/2) ? 0 : 1;
   $mstr[$c] = max($mstr[$c],strlen($key));
   $mlen[$c] = max($mlen[$c],strlen($len));
  }
  for($a=0;$a<count($list);$a++)			// Liste zusammenstellen
   if(@$var=$list[(($a-$a%2)/2)+floor(count($list)%2+count($list)/2)*($a%2)])
    $val[$a-$a%2] = ((isset($val[$a-$a%2])) ? $val[$a-$a%2] : '').str_pad($var[0],$mstr[$a%2]," ")." ".str_pad($var[1],$mlen[$a%2]," ",STR_PAD_LEFT)." Bytes   ";
  $info = implode("\n",$val);
 }
 return $info;
}
function dial($dial,$port=false,$sid=false) {		// Wahlhilfe
 if(!$sid)
  $sid = $GLOBALS['cfg']['sid'];
 $dial = preg_replace('/[^\d*#]/','',$dial);
 $rdial = urlencode($dial);
 $port = ($port) ? preg_replace('/\D+/','',$port) : false;
 if($GLOBALS['cfg']['fiwa'] >= 530) {
  if($port) {
   if($GLOBALS['cfg']['dbug']/(1<<0)%2)
    dbug("Dial: �ndere Anruf-Telefon auf $port");
   request('POST',"/fon_num/dial_fonbook.lua","clicktodial=on&port=$port&btn_apply=&sid=$sid");
  }
  if($GLOBALS['cfg']['dbug']/(1<<0)%2)
   dbug("Dial: ".(($rdial) ? "W�hle $dial" : "Auflegen"));
  request('GET',"/fon_num/fonbook_list.lua",(($rdial == '') ? "hangup=&orig_port=$port" : "dial=$rdial")."&sid=$sid");
 }
 else {
  request('POST',"/cgi-bin/webcm","telcfg:settings/UseClickToDial=1"
	.(($rdial == '') ? "&telcfg:command/Hangup=" : "&telcfg:command/Dial=$rdial")
	.(($port) ? "&telcfg:settings/DialPort=$port" : "")."&sid=$sid");
  if($GLOBALS['cfg']['dbug']/(1<<0)%2)
   dbug("Dial: ".(($rdial) ? "W�hle $dial".(($port) ? " f�r Telefon $port" : "") : "Auflegen"));
 }
 return true;
}
function cfgexport($mode,$pass=false,$sid=false) {	// Konfiguration Exportieren (NUR Exportieren)
 $body = array('ImportExportPassword' => $pass, 'ConfigExport' => false);
 $path = '/cgi-bin/firmwarecfg';
 if(!$sid) {
  $sid = $GLOBALS['cfg']['sid'];
  $body = array_merge(array('sid' => $sid),$body);
 }
 return ($mode)	? (($mode === 'array')	? request('POST-array',$path,$body)
					: request('POST-save:'.(($mode === true) ? './' : $file),$path,$body))
		: request('POST',$path,$body);
}
function cfgcalcsum($data) {				// Checksumme f�r die Konfiguration berechnen
 if(preg_match_all('/^(\w+)=(\S+)\s*$|^(\*{4}) (?:CRYPTED)?(CFG|BIN)FILE:(\S+)\s*(.*?)\3 END OF FILE \3\s*$/sm',$data,$array)) {
  if($GLOBALS['cfg']['dbug']/(1<<4)%2)
   dbug($array,'CfgCalcSum-#');
  foreach($array[4] as $key => $var)
   if($array[1][$key])
    $array[0][$key] = $array[1][$key].$array[2][$key]."\0";
   elseif($var == 'CFG')
    $array[0][$key] = $array[5][$key]."\0".stripcslashes(str_replace("\r",'',substr($array[6][$key],0,-1)));
   elseif($var == 'BIN')
    $array[0][$key] = $array[5][$key]."\0".pack('H*',preg_replace('/[^\da-f]+/i','',$array[6][$key]));
 }
 return ($array and preg_match('/(?<=^\*{4} END OF EXPORT )[A-Z\d]{8}(?= \*{4}\s*$)/m',$data,$key,PREG_OFFSET_CAPTURE))
	? array($key[0][0],$var = crc_32(join('',$array[0])),substr_replace($data,$var,$key[0][1],8)) : errmsg('Keine Konfig-Datei',__FUNCTION__);
}
function cfgimport($file,$pass=false,$mode=false,$sid=false) {	// Konfiguration importieren (Wird vermutlich bald �berarbeitet)
 if($file and (is_file($file) and ($data = file_get_contents($file)) or is_dir($file) and ($data = cfgmake($file)))
	or !$file and $data = $mode and substr($mode,0,4) == '****') {
  if($mode and $var = cfgcalcsum($data))
   $data = $var[2];
  if($GLOBALS['cfg']['dbug']/(1<<0)%2)
   dbug("Upload Konfig-File an ".$GLOBALS['cfg']['host']);
  $body = array('ImportExportPassword' => $pass,
	'ConfigImportFile' => array('filename' => $file, 'Content-Type' => 'application/octet-stream', '' => $data),
	'apply' => false);
  if(!$sid and $GLOBALS['cfg']['sid'] !== true)
   $body = array_merge(array('sid' => $GLOBALS['cfg']['sid']),$body);
  return request('POST','/cgi-bin/firmwarecfg',$body);
 }
 else
  return errmsg('Import-Datei/Ordner nicht gefunden',__FUNCTION__);
}
function cfginfo($data,$mode,$text=false) {		// Konfiguration in Einzeldateien sichern
 if(preg_match_all('/^(?:
	\*{4}\s(.*?)\sCONFIGURATION\sEXPORT|(\w+=\S+))\s*$		# 1 Fritzbox-Modell, 2 Variablen
	|^\*{4}\s(?:CRYPTED)?(CFG|BIN)FILE:(\S+)\s*?\r?\n(.*?)\r?\n	# 3 Typ, 4 File, 5 Data
	^\*{4}\sEND\sOF\sFILE\s\*{4}\s*?$/msx',$data,$array) and $array[1][0] and $crc = cfgcalcsum($data)) {
  $list = $val = $vars = array();
  $mstr = $mlen = array(0,0);
  if(@$GLOBALS['cfg']['dbug']/(1<<4)%2)			// Debugdaten Speichern
   dbug($array,'CfgInfo-#');
  foreach($array[3] as $key => $var)			// Config-Dateien aufteilen
   if($var) {
    if($array[3][$key] == 'CFG') {
     $bin = str_replace(array("\r","\\\\"),array("","\\"),$array[5][$key]);
     if(!isset($vars['Date']) and preg_match('/^\s\*\s([\s:\w]+)$/m',$bin,$var))
      $vars['Date'] = strtotime($var[1]);
    }
    else
     $bin = pack('H*',preg_replace('/[^\da-f]+/i',"",$array[5][$key]));
    $list[] = array($array[3][$key],$array[4][$key],number_format(strlen($bin),0,",","."));
    if($mode)
     file_put_contents($array[4][$key],$bin);
    unset($array[2][$key]);
   }
   elseif($var = ifset($array[2][$key],'/^(\w+)=(.*)$/'))
    $vars[$var[1]] = $var[2];
   else
    unset($array[2][$key]);
  $file = "pattern.txt";				// Konfig-Schablone sichern
  $data = preg_replace('/^(\*{4}\s(?:CRYPTED)?(?:CFG|BIN)FILE:\S+\s*?\r?\n).*?\r?\n(^\*{4}\sEND\sOF\sFILE\s\*{4}\s*?)$/msx','$1$2',$data);
  $list[] = array("TXT",$file,number_format(strlen($data),0,",","."));
  if($mode)
   file_put_contents($file,$data);
  if($text) {						// Zugangsdaten sichern
   $file = "zugangsdaten.txt";
   $list[] = array("TXT",$file,number_format(strlen($text),0,",","."));
   if($mode)
    file_put_contents($file,$text);
  }
  foreach($list as $key => $var) {			// Maximale L�ngen ermitteln
   $c = ($key < count($list)/2) ? 0 : 1;
   $mstr[$c] = max($mstr[$c],strlen($var[1]));
   $mlen[$c] = max($mlen[$c],strlen($var[2]));
  }
  for($a=0;$a<count($list);$a++)			// Liste zusammenstellen
   if(@$var=$list[(($a-$a%2)/2)+floor(count($list)%2+count($list)/2)*($a%2)])
    $val[$a-$a%2] = ((isset($val[$a-$a%2])) ? $val[$a-$a%2] : '').$var[0].": ".str_pad($var[1],$mstr[$a%2]," ")." ".str_pad($var[2],$mlen[$a%2]," ",STR_PAD_LEFT)." Bytes   ";
  $list = "\nModell:   {$array[1][0]}\n";
  if(ifset($vars['Date']))
   $list .= "Datum:    ".date('d.m.Y H:i:s',$vars['Date'])."\n";
  if(ifset($vars['FirmwareVersion']))
   $list .= "Firmware: $vars[FirmwareVersion]\n";
  return $list."Checksum: $crc[0] (".(($crc[0] == $crc[1]) ? "OK" : "Inkorrekt! - Korrekt: $crc[1]").")\n\n"
	.implode("\n",$val)."\n".((!$mode and $text) ? $text : '');
 }
 else
  return errmsg('Keine Konfig-Datei',__FUNCTION__);
}
function cfgmake($dir,$mode=false,$file=false) {	// Konfiguration wieder zusammensetzen
 if(file_exists("$dir/pattern.txt") and $data = file_get_contents("$dir/pattern.txt") and preg_match('/^\*{4}\s+FRITZ!/m',$data,$array)) {
  $GLOBALS['val'] = $dir;
  $data = preg_replace_callback('/(^\*{4}\s(?:CRYPTED)?(CFG|BIN)FILE:(\S+)\s*?(\r?\n))(^\*{4}\sEND\sOF\sFILE\s\*{4}\s*?$)/m','prcb_cfgmake',$data);
  if(preg_match('/^\*{4}\s(.*?)\sCONFIGURATION\sEXPORT.*?FirmwareVersion=(\S+)/s',$data,$array) and $crc = cfgcalcsum($data)) {
   $val = "Modell:   $array[1]\nFirmware: $array[2]\nChecksum: $crc[0] ";
   $val .= (($crc[0] == $crc[1]) ? "(OK)" : "Inkorrekt! - Korrekt: $crc[1]")."\n";
   $data = ($mode) ? $crc[2] : $data;
   file_put_contents($file,$data);
   return ($file) ? $val : $data;
  }
 }
 return errmsg("Kein Konfig-Ordner - $dir/pattern.txt nicht gefunden",__FUNCTION__);
}
function prcb_cfgmake($a,$b='') {			// Helper f�r Preg_Replace CfgMake
  if(file_exists("$GLOBALS[val]/$a[3]"))
   $b = file_get_contents("$GLOBALS[val]/$a[3]");
  return $a[1].(($a[2] == 'BIN') ? wordwrap(strtoupper(implode('',unpack('H*',$b))),80,$a[4],CUT) : str_replace("\\","\\\\",$b)).$a[4].$a[5];
}
function konfigdecrypt($data,$pass,$sid=false) {	// Konfig-Datei mit Fritz!Box entschl�sseln
 global $cfg;
 if(preg_match_all('/^\s*([\w-]+)\s*=\s*("?)(\${4}\w+)\2;?\s*$/m',$data,$array) and preg_match('/(?:\s*Password\d*=\${4}\w+)+/',$data,$var)) {
  if($cfg['dbug']/(1<<4)%2)
   dbug($array,'KonfigDeCrypt');
  if(preg_match('/^boxusers\s\{\s*^\s{8}users(\s\{.*?^\s{8}\}$)/smx',$k = str_replace("\t","        ",gzinflate(base64_decode("
	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"))),$v))
   $export = array(str_replace("#0",$var[0],$k),$v[1]);	// Stark gek�rze und leere Fritz!Box 7490 Konfig
  $list = array_unique($array[3]);			// Doppelte Eintr�ge entfernen
  shuffle($list);					// Alle Eintr�ge durchw�rfeln (F�r Problemf�lle)
  $plain = $buffer = array();
  if(preg_match_all('/\${4}\w+/',$var[0],$array))	// Salt-Kennw�rter eintragen
   foreach($array[0] as $var)
    $plain[$var] = $pass;
  if($cfg['dbug']/(1<<0)%2)
   dbug(count($list)." verschiedene verschl�sselte Eintr�ge gefunden!");
  if(!$sid)						// Sid sicherstellen
   $sid = $cfg['sid'];
  $dupe = array(array(1,4,'/^(.*?)$/'),array(15,5,'/(?<=^|,\s)(.*?)(?=,\s|$)/'));
  $pregimport = array(
   'de' => array('Internetzugangsdaten' => array(1,0,'/^Benutzer:\s(.*?)(?:,\sAnbieter:\s.*?)?$/'),'Dynamic DNS' => array(2,1,'/(?<=Domainname:\s|Benutzername:\s)(.*?)(?=,\s|$)/'),
    'PushService' => array(1,3,'/^E-Mail-Empf�nger:\s(.*?)$/'),'MyFRITZ!' => $dupe[0],'FRITZ!Box-Benutzer' => $dupe[1]),
   'en' => array('Internet Account Information' => array(1,0,'/^User:\s(.*?)(?:,\sProvider:\s.*?)?$/'),'Dynamic DNS' => array(2,1,'/(?<=Domain\sname:\s|user\sname:\s)(.*?)(?=,\s|$)/'),
    'Push service' => array(1,3,'/^e-mail\srecipient:\s(.*?)$/'),'MyFRITZ!' => $dupe[0],'FRITZ!Box Users' => $dupe[1]),
   'es' => array('Datos de acceso a Internet' => array(1,0,'/^Usuario:\s(.*?)(?:,\sProvider:\s.*?)?$/'),'DNS din�mico' => array(2,1,'/(?<=Nombre\sdel\sdominio:\s|nombre\sdel\susuario:\s)(.*?)(?=,\s|$)/'),
    'Push Service' => array(1,3,'/^Destinatario\sde\scorreo:\s(.*?)$/'),'MyFRITZ!' => $dupe[0],'Usuarios de FRITZ!Box' => $dupe[1]),
   'fr' => array('Donn�es d\'acc�s � Internet' => array(1,0,'/^Utilisateur[\xa0\s]?:[\xa0\s](.*?)(?:,\sProvider:\s.*?)?$/'),'DNS dynamique' => array(2,1,'/(?<=Nom\sde\sdomaine[\xa0\s]:[\xa0\s]|nom\sd\'utilisateur[\xa0\s]:[\xa0\s])(.*?)(?=,\s|$)/'),
    'Service push' => array(1,3,'/^Destinataire\sdu\scourrier\s�lectronique[\xa0\s]?:[\xa0\s](.*?)$/'),'MyFRITZ!' => $dupe[0],'Utilisateur de FRITZ!Box' => $dupe[1]),
   'it' => array('Dati di accesso a Internet' => array(1,0,'/^Utente:\s(.*?)(?:,\sProvider:\s.*?)?$/'),'Dynamic DNS' => array(2,1,'/(?<=Nome\sdi\sdominio:\s|nome\sutente:\s)(.*?)(?=,\s|$)/'),
    'Servizio Push' => array(1,3,'/^Destinatario\se-mail:\s(.*?)$/'),'MyFRITZ!' => $dupe[0],'Utenti FRITZ!Box' => $dupe[1]),
   'pl' => array('Dane dost?powe do internetu' => array(1,0,'/^U\?ytkownik:\s(.*?)(?:,\sProvider:\s.*?)?$/'),'Dynamic DNS' => array(2,1,'/(?<=Nazwa\sdomeny:\s|nazwa\su\?ytkownika:\s)(.*?)(?=,\s|$)/'),
    'Push Service' => array(1,3,'/^Odbiorca\se-maila:\s(.*?)$/'),'MyFRITZ!' => $dupe[0],'U?ytkownicy FRITZ!Box' =>  $dupe[1]),
  );
  $lang = (ifset($cfg['boxinfo']['Lang']) and ifset($pregimport[$cfg['boxinfo']['Lang']])) ? $cfg['boxinfo']['Lang'] : 'de';
  while(count($list)) {					// Alle Verschl�sselte Eintr�ge durchlaufen
   $import = $export[0];
   $buffer = array_values($buffer);
   while(count($list) and count($buffer) < 20)		// Die ersten 20 Eintr�ge sichern
    $buffer[] = array_shift($list);
   $a = 1;						// Import-Buffer f�llen
   $v = array();
   foreach($buffer as $var)
    if($a < 6)
     $import = str_replace('#'.$a++,$var,$import);
    else
     $v[] = str_replace('#7',$var,str_replace('#6',4+$a++,$export[1]));
   $import = preg_replace('/(^boxusers\s\{\s*^\s{8}users)\s\{.*?^\s{8}\}$/smx',"$1".str_replace('$','\$',implode('',$v)),$import);
   if($var = cfgcalcsum($import))			// Checksum berechnen
    $import = $var[2];
   if($var = request('POST','/cgi-bin/firmwarecfg',array('sid' => $sid, 'ImportExportPassword' => $pass,
	'ConfigTakeOverImportFile' => array('filename' => 'fritzbox.export', 'Content-Type' => 'application/octet-stream', '' => $import), 'apply' => false))
	and !preg_match('/cfg_nok/',$var)) {
    if($getdata = utf8_decode(($cfg['fiwa'] < 650) ? request('GET',"/system/cfgtakeover_edit.lua?sid=$sid&cfg_ok=1") : request('POST',"/data.lua","xhr=1&sid=$sid&lang=de&no_sidrenew=&page=cfgtakeover_edit"))) {
     if(preg_match_all('/^\s*\["add\d+_text"\]\s*=\s*"([^"]+)",\s*$.*?^\s*\["gui_text"\]\s*=\s*"([^"]+)",\s*$/sm',$getdata,$match))
      $match[2] = array_flip($match[2]);
     elseif(preg_match_all('/<label for="uiCheckcfgtakeover\d+">(.*?)\s*<\/label>\s*<span class="addtext">(.*?)\s*<br>\s*<\/span>/',$getdata,$match))
      $match = array(1 => $match[2], 2 => array_flip($match[1]));
     if($cfg['dbug']/(1<<4)%2)
      dbug($match,'KonfigDeCrypt-Match');
     if($match) {					// Decodierte Kennw�rter gefunden
      foreach($pregimport[$lang] as $key => $var)
       if(isset($match[2][$key]) and preg_match_all($var[2],$match[1][$match[2][$key]],$array))
        foreach($array[1] as $k => $v)
         if(isset($buffer[$var[1] + $k]) and $buffer[$var[1] + $k] != $v) {	// Kennwort sichern
          $plain[$buffer[$var[1] + $k]] = str_replace('"','\\\\"',html_entity_decode($v,ENT_QUOTES,'ISO-8859-1'));
          unset($buffer[$var[1] + $k]);
         }
     }
     else
      return errmsg('Keine Entschl�sselte Daten gefunden',__FUNCTION__);
    }
    else
     return errmsg('Keine Daten erhalten',__FUNCTION__);
   }
   else
    return errmsg('Entschl�sselungsversuch wurde nicht akzeptiert',__FUNCTION__);
   if($cfg['dbug']/(1<<0)%2)
    dbug((count($list)) ? floor(count($plain)/(count($list)+count($plain))*100)."% entschl�sselt..." : "100% - Ersetze ".count($plain)." entschl�sselte Eintr�ge...");
  }
  if($cfg['dbug']/(1<<4)%2)				// Plaintext sichern
   dbug($plain,'KonfigDeCrypt-Plain');
  return str_replace(array_keys($plain),array_values($plain),$data);
 }
 return errmsg('Keine Konfig-Datei',__FUNCTION__);
}
function konfig2array($data) {				// FRITZ!Box-Konfig -> Array
 $config = array();
 if($data{0} == '*' and preg_match_all('/^(?:\*{4}\s(.*?)\sCONFIGURATION\sEXPORT|(\w+)=(\S+))\s*$
	|^\*{4}\s(?:CRYPTED)?(CFG|BIN)FILE:(\S+)\s*?\r?\n(.*?)\r?\n^\*{4}\sEND\sOF\sFILE\s\*{4}\s*?$/msx',$data,$array)) {
  if(@$GLOBALS['cfg']['dbug']/(1<<4)%2)			// Debugdaten Speichern
   dbug($array,'Konfig2Array-#');
  foreach($array[4] as $key => $var)
   if(ifset($array[1][$key]))				// Routername
    $config['Name'] = $array[1][$key];
   elseif(ifset($array[2][$key]))			// Variablen
    $config[$array[2][$key]] = $array[3][$key];
   elseif(ifset($array[4][$key],'BIN'))			// BinData
    $config[$array[5][$key]] = pack('H*',preg_replace('/[^\da-f]+/i','',$array[6][$key]));
   elseif(ifset($array[4][$key],'CFG') and preg_match_all('/^(\w+)\s(\{\s*$.*?^\})\s*$/smx',
	str_replace(array("\r","\\\\"),array("","\\"),$array[6][$key]),$match))	// CfgData
    foreach($match[1] as $k => $v)
     $config/*[$array[5][$key]]*/[$v] = konfig2array($match[2][$k]);
  if(@$GLOBALS['cfg']['dbug']/(1<<4)%2)			// Debugdaten Speichern
   dbug($config,'Konfig2Array');
 }
 elseif($data{0} == '{' and preg_match_all('/\{\s*?$.*?^\}/msx',$data,$array)) {
  if(@$GLOBALS['cfg']['dbug']/(1<<4)%2)			// Debugdaten Speichern
   dbug($array,'Konfig2Array-Multi-#');
  if(count($array[0]) > 1)				// Ein oder Multi-Array
   foreach($array[0] as $var)				// Weitere Matches auf selber Ebene
    $config[] = konfig2array($var);
  elseif(preg_match_all('/^\s{8}(?:(\w+)\s(?:=\s(?:([^\s"]+)|(".*?(?<!\\\\)"(?:,\s*)?));|(\{\s*$.*?^\s{8}\}))\s*$)$/msx',$data,$match)) {
   if(@$GLOBALS['cfg']['dbug']/(1<<4)%2)		// Debugdaten Speichern
    dbug($match,'Konfig2Array-Sub-#');
   foreach($match[1] as $key => $var)			// Array durch arbeiten
    if(ifset($match[2][$key]))				// Einfache Werte
     $config[$var] = ($match[2][$key] == 'yes') ? true  : (($match[2][$key] == 'no') ? false : (($match[2][$key] == (int)$match[2][$key]) ? (int)($match[2][$key]) : $match[2][$key]));
    elseif(ifset($match[3][$key]) and preg_match_all('/"(.*?)(?<!\\\\)"/',$match[3][$key],$val))	// String(s)
     $config[$var] = str_replace('\"','"',(count($val[1]) > 1) ? $val[1] : $val[1][0]);
    elseif(ifset($match[4][$key]))			// Verschachteltes Array
     $config[$var] = konfig2array(preg_replace('/^\s{8}/m','',$match[4][$key]));
  }
 }
 else
  return errmsg('Keine Konfig-Datei',__FUNCTION__);
 return $config;
}
function showaccessdata($data) {			// Die Kronjuwelen aus Konfig-Daten heraussuchen
 $text = '';
 $config = array();
 if($konfig = konfig2array($data)) {		// Konfig als Array umwandeln
  $access = array(
   'Mobile-Stick' => array(&$konfig['ar7cfg']['serialcfg'],'=number,provider,username,passwd'),
   'DSL' => array(&$konfig['ar7cfg']['targets'],'-name,>local>username,>local>passwd'),
   'IPv6' => array(&$konfig['ipv6']['sixxs'],'=ticserver,username,passwd,tunnelid'),
   'DynamicDNS' => array(&$konfig['ddns']['accounts'],'=domain,username,passwd'),
   'MyFRITZ!' => array(&$konfig['jasonii'],'=user_email,user_password,box_id,box_id_passphrase,dyn_dns_name'),
   'FRITZ!Box-Oberfl�che' => array(&$konfig['webui'],'=username,password'),
   'Fernwartung' => array(&$konfig['websrv']['users'],'=username,passwd'),
   'TR-069-Fernkonfiguration' => array(&$konfig['tr069cfg']['igd']['managementserver'],'=url,username,password,ConnectionRequestUsername,ConnectionRequestPassword'),
   'Telekom-Mediencenter' => array(&$konfig['t_media'],'=refreshtoken,accesstoken'),
   'Google-Play-Music' => array(&$konfig['gpm'],'=emailaddress,password,partition,servername'),
   'Onlinespeicher' => array(&$konfig['webdavclient'],'=host_url,username,password'),
   'WLAN' => array(&$konfig['wlancfg'],'/^((guest_)?(ssid(_scnd)?|pskvalue)|(sta_)?key_value\d|wps_pin|wds_key)$/i'),
   'Push-Dienst' => array(&$konfig['emailnotify'],'=From,To,SMTPServer,accountname,passwd','+To,arg0'),
   'FRITZ!Box-Benutzer' => array(&$konfig['boxusers']['users'],'-name,email,passwd,password'),
   'InternetTelefonie' => array(&$konfig['voipcfg'],'_name,username,authname,passwd,registrar,stunserver,stunserverport,gui_readonly'),
   'IP-Telefon' => array(&$konfig['voipcfg']['extensions'],'-extension_number,username,authname,passwd,clientid'),
   'Online-Telefonbuch' => array(&$konfig['voipcfg']['onlinetel'],'-pbname,url,serviceid,username,passwd,refreshtoken,accesstoken'),
   'Virtual-Privat-Network' => array(&$konfig['vpncfg']['connections'],'-name,>localid<fqdn,>remoteid<fqdn,>localid<user_fqdn,>remoteid<user_fqdn,key,>xauth>username,>xauth>passwd'),
  );
  foreach($access as $key => $var)		// Accessliste durcharbeiten
   if(ifset($var[0])) {
    if($var[1]{0} == '/') {			// Regul�re Ausdr�cke verwenden
     foreach($var[0] as $k => $v)
      if(preg_match($var[1],$k) and $var[0][$k])// Schl�ssel Suchen und Pr�fen
       $config[$key][$k] = $var[0][$k];
    }
    elseif($var[1]{0} == '=') {			// Normal abfragen
     $keys = explode(',',substr($var[1],1));
     foreach($keys as $k)
      if(ifset($var[0][$k]))			// Schl�ssel Testen
       $config[$key][$k] = $var[0][$k];
    }
    if(preg_match('/^([-+_])(.+)$/',$var[(isset($var[2])) ? 2 : 1],$keys)) {	// Eine Schl�ssel-Ebene �berspringen
     if($keys[1] == '-' and count(array_filter(array_keys($var[0]),'is_string')) > 0)
      $var[0] = array($var[0]);
     $keys[3] = explode(',',$keys[2]);
     foreach($var[0] as $k => $v)
      if((preg_match('/\d+\s*$/',$k,$val) or $keys[1] == '+') and is_array($v)) {	// Neue Ebene gefunden
       $name = ($val) ? false : $k;
       foreach($keys[3] as $val)
        if($val{0} == '>' and preg_match('/(\w+)([<>])(\w+)/',$val,$va1) and ifset($var[0][$k][$va1[1]][$va1[3]]))	// Mit Regul�re Ausdr�cke noch eine Ebene �berspringen
         if($name === false)
          $name = (string)$var[0][$k][$va1[1]][$va1[3]];
         else
          $config[$key][$name][(($va1[2] == '<') ? $va1[1] : $va1[3])] = $var[0][$k][$va1[1]][$va1[3]];	// Den Vorigen Schl�ssel verwenden?
        elseif(ifset($var[0][$k][$val]))		// Auf der neuen Ebene Pr�fen
         if($name === false)
          $name = (string)$var[0][$k][$val];
         else
          $config[$key][$name][$val] = $var[0][$k][$val];
      }
    }
   }
  if($GLOBALS['cfg']['dbug']/(1<<4)%2)			// Alle Fundst�cke ungefiltert sichern
   dbug($config,'ShowAccessData');
  if(ifset($config['InternetTelefonie']))								// Filter: StunServerPort 3478 filtern
   foreach($config['InternetTelefonie'] as $key => $var)
    if(ifset($var['stunserverport'],3478))
     unset($config['InternetTelefonie'][$key]['stunserverport']);
  if(ifset($config['IPv6']) and ifset($config['IPv6']['ticserver']) and count($config['IPv6']) == 1)	// Filter: IPv6 tivserver
   unset($config['IPv6']);
  if(ifset($config['TR-069-Fernkonfiguration']) and ifset($config['TR-069-Fernkonfiguration']['url']) and count($config['TR-069-Fernkonfiguration']) == 1)	// Filter: TR069 url
   unset($config['TR-069-Fernkonfiguration']);
  if(ifset($config['Mobile-Stick']) and ifset($config['Mobile-Stick']['username'],'ppp') and ifset($config['Mobile-Stick']['passwd'],'ppp'))	// Filter: Surf-Stick ppp
   unset($config['Mobile-Stick']);
  foreach($config as $key => $var) {			// Array in Text Umwandeln
   if($var and count($var))
    $text .= "\n$key\n";
   if($kl = max(array_map('strlen',array_keys($var)))) {
    foreach($var as $k => $v) {
     $text .= " ".str_pad($k,$kl)." -> ";
     if(is_array($v)) {
      $val = array();
      foreach($v as $kk => $vv)
       if($vv)
        $val[] = "$kk=$vv";
      $v = implode(", ",$val);
     }
     $text .= (($GLOBALS['cfg']['wrap']) ? wordwrap($v,$GLOBALS['cfg']['wrap']-($kl+6),str_pad("\n",$kl+6," "),true) : $v)."\n";
    }
   }
  }
 }
 return $text;
}
function getevent($filter='aus',$sep="\t",$sid=false) {	// Ereignisse abrufen
 global $cfg;
 $filters = array('aus','system','internet','telefon','wlan','usb');
 $filter = (($var = ifset($filters,$filter)) !== false) ? $var : 0;
 if($cfg['dbug']/(1<<0)%2)
  dbug("Hole Ereignisse (Filter: {$filters[$filter]})");
 if(!$sid)
  $sid = $cfg['sid'];
 if($data = (($cfg['fiwa'] < 500) ? request('POST-array','/cgi-bin/webcm',"getpage=../html/de/system/ppSyslog.html&logger:settings/filter=$filter&sid=$sid")
	: request('GET-array',"/system/syslog.lua?tab={$filters[$filter]}&event_filter=$filter&stylemode=print&sid=$sid")) and preg_match_all(($cfg['fiwa'] < 500)
	? '!<p class="log">(\S*)\s*(\S*)\s*(.*?)</p>!' : '!<tr><td[^>]*>(?:<div>)?(.*?)(?:</div>)?</td><td[^>]*>(.*?)</td><td[^>]*><a[^>]*>(.*?)</a></td></tr>!',$data[1],$array)) {
  if($cfg['dbug']/(1<<4)%2)
   dbug($array,'GetEvent');
  foreach($array[1] as $key => $var)
   $array[0][$key] = $array[1][$key].$sep.$array[2][$key].$sep.$array[3][$key];
  $array = implode("\r\n",array_reverse($array[0]))."\r\n";
  if(ifset($data['Content-type'],'/utf-?8/'))
   $array = utf8_decode($array);
  return html_entity_decode($array,ENT_QUOTES,'ISO-8859-1');
 }
 else
  return errmsg('Keine Ereignisse bekommen',__FUNCTION__);
}

# Eigentlicher Programmstart

if(ifset($argv) and $argc and (float)phpversion() > 4.3 and $ver = ifset($ver,'/^(\w+) ([\d.]+) \(c\) (\d\d)\.(\d\d)\.(\d{4}) by ([\w ]+?) <([.:\/\w]+)>$/')) { ## CLI-Modus ##
 $ver[2] = floatval($ver[2]);				// fb_tools Version
 $ver[] = intval($ver[5].$ver[4].$ver[3]);		// fb_tools Datum
 $uplink = array("mengelke.de","/Projekte;$ver[1].");	// Update-Link
 if(!$script = realpath($argv[0]))			// Pfad zum Scipt anlegen
  $script = realpath($argv[0].".bat");			// Workaround f�r den Windows-Sonderfall
 $self = basename($script);
 $ext = strtolower(preg_replace('/\W+/','',pathinfo($script,PATHINFO_EXTENSION))); // Extension f�r Unix/Win32 unterscheidung
 $cfg['head'] = array('Useragent' => "$self $ver[2] ".php_uname()." PHP ".phpversion()."/".php_sapi_name());	// Fake UserAgent
 define($ver[1],1);					// Feste Kennung f�r Plugins etc.
 if(!preg_match('/cli/',php_sapi_name()) and function_exists('header_remove')) {	// HTTP-Header l�schen wenn PHP-CGI eingesetzt wird
  header('Content-type:');
  header_remove('Content-type');
  header_remove('X-Powered-By');
 }
 foreach(array(".",$script) as $var) {			// Benutzerkonfig suchen
  $var = realpath($var);
  if(is_file($var))
   $var = dirname($var);
  if(is_dir($var))
   $var .= "/".basename($cfg['usrcfg']);
  if(file_exists($var)) {				// Benutzerkonfig gefunden und laden
   if($cfg['dbug']/(1<<0)%2)	// Debug-Meldung (dbug muss im Quelltext aktiviert werden)
    dbug("Lade Benutzer-Konfig: $var");
   include $var;
   break;
  }
 }
 if(@ini_get('pcre.backtrack_limit') < $cfg['pcre']) 	// Bug ab PHP 5 beheben (F�r Gro�e RegEx-Ergebnisse)
  @ini_set('pcre.backtrack_limit',$cfg['pcre']);
 if($cfg['time'])					// Zeitzone festlegen
  @ini_set('date.timezone',$cfg['time']);
 $cfg['time'] = (ifset($_SERVER['REQUEST_TIME_FLOAT'])) ? $_SERVER['REQUEST_TIME_FLOAT'] : array_sum(explode(' ',microtime()));	// Startzeit sichern
 $help = "\n\nWeitere Hilfe bekommen Sie mit der -h Option oder mehr Hilfe mit -h:all";
 $pmax = $argc;		// Anzahl der Parameter
 $pset = 1;		// Optionsz�hler

# Drag'n'Drop Modus
 if(ifset($cfg['drag']) and $pset+1 == $pmax and file_exists($argv[$pset])) {
  if($cfg['dbug']/(1<<0)%2)	// Debug-Meldung (dbug muss im Quelltext aktiviert werden)
   dbug("Nutze Drag-Parameter: $cfg[drag]");
  $drag = explode(',',$cfg['drag']);
  array_splice($argv,$pmax,0,explode(' ',$drag[1]));
  array_splice($argv,$pset,0,explode(' ',$drag[0]));
  $pmax = $argc = count($argv);
 }

# Fritz!Box Parameter ermitteln und auswerten
 if($pset+1 < $pmax and @preg_match('/^
	(?:([^:]+):)?	(?:([^@]+)@)?
	([\w.-]+\.[\w.-]+|\[[a-f\d:]+\]|'.strtr(preg_quote(implode("\t",array_keys($cfg['preset']))),"\t",'|').')
	(?::(\d{1,5}))?
		$/ix',$argv[$pset],$array)) {		// Fritz!Box Anmeldedaten holen
  $cfg['host'] = $array[3];
  if(isset($cfg['preset'][$array[3]]))			// Voreingestellte Fritz!Boxen Erkennen und Eintragen
   foreach($cfg['preset'][$array[3]] as $key => $var)
    $cfg[$key] = $var;
  if(@$array[1])
   $cfg['user'] = $array[1];
  if(@$array[2])
   $cfg['pass'] = $array[2];
  if(@$array[4])
   $cfg['port'] = $array[4];
  $pset++;
 }
 unset($cfg['preset']);					// Preset-Daten werden nicht mehr ben�tigt!

# Optionen setzen
 while($argv[$pmax-1]{0} == '-' and ($pmax == $argc and !ifset($argv[$pmax-2],'/^-/') and preg_match_all('/-(\w+)(?:[:_=]([\w.]+))?/',$argv[$pmax-1],$array)
	or preg_match('/^-(\w+)(?:[:_=](.+))?$/',$argv[$pmax-1],$array))) {
  if(is_string($array[0]))
   $array = array(array($array[0]),array($array[1]),array($array[2]));
  $pmax--;
  foreach($array[1] as $key => $var) {
   $vas = $array[2][$key];
   if($var == 'h')	// Help
    $cfg['help'] = ($vas) ? $vas : true;
   if($var == 'd')	// Debug
    $cfg['dbug'] = (ifset($vas,'/^\d+$/')) ? intval($vas) : true;
   if($var == 'w')	// Wrap
    $cfg['wrap'] = (ifset($vas,'/^\d+$/')) ? intval($vas) : 80;
   if($var == 'c')	// Char
    $cfg['char'] = strtolower($vas);
   if($var == 't')	// Timeout
    $cfg['tout'] = (ifset($vas,'/^\d+$/')) ? intval($vas) : 3;
   if($var == 'b')	// Buffer
    $cfg['sbuf'] = (ifset($vas,'/^\d+$/')) ? intval($vas) : 4096;
   if($var == 's') {		// SID
    if(preg_match('/^[\da-f]{16}$/i',$vas))
     $cfg['bsid'] = $cfg['sid'] = $vas;
    elseif(file_exists($vas) and preg_match('/(?<=^|\W)[\da-f]{16}$/i',file_get_contents($vas),$val))
     $cfg['bsid'] = $cfg['sid'] = $val[0];
    if($cfg['dbug'] and $cfg['bsid'])
     dbug("Recycle Login-SID: $cfg[host]");
   }
   if($var == 'fw' and ifset($vas,'/[1-9]{1,2}\d{2}/'))	// Fritz!Box Firmware-Version
    $cfg['fiwa'] = (int)$vas;
   foreach(array('o' => 'oput', 'un' => 'user', 'pw' => 'pass', 'fb' => 'host', 'pt' => 'port') as $k => $v)	// Optionen mit Zwangsparameter ohne Pr�fung
    if($var == $k and $vas)
     $cfg[$v] = $vas;
  }
 }

 # PHP-Fehler Protokollieren
 if($cfg['dbug']/(1<<8)%2) {
  set_error_handler(create_function('$no,$str,$file,$line','
  $a = preg_split("/\s+/","ERROR WARNING PARSE NOTICE CORE_ERROR CORE_WARNING COMPILE_ERROR COMPILE_WARNING
	USER_ERROR USER_WARNING USER_NOTICE STRICT RECOVERABLE_ERROR DEPRECATED USER_DEPRECATED UNKNOWN");
  foreach($a as $b => $c)
   if($no == pow(2,$b))
    break;
  $a = "$str on line $line";
  $b = &$GLOBALS["cfg"]["error"][$c][$file];
//  $b["backtrace"][] = debug_backtrace();
  if(!isset($a,$b) or array_search($a,$b) === false)
   $b[] = $a;
  return false;'));
 }
 else
  error_reporting(0);					// Fehler-Meldungen deaktivieren

# Consolen Breite automatisch ermitteln
 if($cfg['wrap'] == 'auto') {
  if(isset($_SERVER['HOME']) and isset($_SERVER['USER']) and isset($_SERVER['TERM']) and isset($_SERVER['SHELL']))	// Unix/Linux/Mac
   $cfg['wrap'] = (file_exists('/usr/bin/tput') and $a = (int)@exec('tput cols')
	or file_exists('/bin/stty') and $a = (int)preg_replace('/^\d+\D*/','',@exec('stty size'))) ? $a : 0;
  elseif(isset($_SERVER['SystemDrive']) and isset($_SERVER['SystemRoot']) and isset($_SERVER['APPDATA']) and (@exec('mode con',$var) or true)	// Windows
	and is_array($var) and preg_match_all('/(?:(zeilen|lines)|(spalten|columns)|(code\s?page)):\s*(\S+)/',strtolower(implode('',$var)),$val))
   foreach($val[4] as $key => $var) {
    if(ifset($val[2][$key]))	// Breite sichern
     $cfg['wrap'] = $var;
    if(ifset($val[3][$key]))	// Codepage merken
     $char = "cp$var";
   }
  if($cfg['wrap'] == 'auto')	// Auto fehlgeschlagen -> Wrap deaktiviert
   $cfg['wrap'] = 0;
 }

# Char ermitteln
 if(ifset($cfg['char'],'auto')) {
  if(preg_match('/(13)[73]((\1)37)/',date('dnHi'),$var))
   $cfg['char'] = $var[2];
  elseif(ifset($char))
   $cfg['char'] = $char;
  elseif($var = ifset($_SERVER['LANG'],'/(UTF-?8)|((?:iso-)?8859-1)/i') and ($var[1] and !isset($cfg['utf8'])) or ifset($var[2]))	// Linux/Ubuntu
   $cfg['char'] = ($var[1]) ? 'utf8' : 'iso_8859_1';
  elseif(isset($_SERVER['HOME']) and isset($_SERVER['USER']) and isset($_SERVER['TERM']) and isset($_SERVER['SHELL'])	// Unix/Linux/Mac
	and file_exists('/usr/bin/locale') and preg_match('/(utf-?8)|(ansi|iso-?8859-?1)/i',@exec('locale charmap'),$var))
   $cfg['char'] = (ifset($var[1]) and !isset($cfg['utf8'])) ? 'utf8' : ((ifset($var[2])) ? strtolower(str_replace('-','_',$var[2])) : 'utf7');
  elseif(isset($_SERVER['SystemDrive']) and isset($_SERVER['SystemRoot']) and isset($_SERVER['APPDATA']))	// Windows
   $cfg['char'] = 'oem';
  else
   $cfg['char'] = '7bit';
 }

# Auto-Update (Check)
 if($cfg['upda'] and $uplink and time()-filemtime($script) > $cfg['upda']) {
  if($fbnet = request('GET',"$uplink[1]md5",0,0,$uplink[0],80) and preg_match("/\((\w+)\s([\d.]+)\)/",$fbnet,$var) and floatval($var[2]) > $ver[2])
   out("Ein Update ist verf�gbar ($ver[1] $ver[2]) - Bitte nutzen Sie die UpGrade Funktion");
  else
   @touch($script);
 }

# Parameter auswerten
 if($pset < $pmax and preg_match('/^
	((?<bi>BoxInfo|bi)	|(?<pi>PlugIn|pi)	|(?<lio>Log(in|out)|l[io])	|(?<d>Dial|d)	|(?<gip>G(et)?IP)	|(?<fb>FooBar|fb)
	|(?<rc>ReConnect|rc)	|(?<sd>SupportDaten|sd)	|(?<ss>(System)?S(tatu)?s)	|(?<i>I(nfo)?)	|(?<k>K(onfig)?)	|(?<ug>UpGrade|ug)
	|(?<e>E(reignisse)?)	)$/ix',$argv[$pset++],$val)) {	## Modes mit und ohne Login ##
  if($cfg['dbug']/(1<<3)%2) {				// Debug Parameter
   dbug('$argv');
   dbug($val);
  }
  if(ifset($val['bi']) and $val['bi']) {		// Jason Boxinfo
   if(ifset($cfg['help']))
    out("$self <fritz.box:port> [BoxInfo|bi]".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self boxinfo
$self 169.254.1.1 bi" : ""));
   elseif($data = request('GET-array','/jason_boxinfo.xml') and preg_match_all('!<j:(\w+)>([^<>]+)</j:\1>!m',$data[1],$array)) {
    if($cfg['dbug']/(1<<4)%2)
     dbug($array,'BoxInfos');
    $jason = array(
	'Name'		=> array('Modell',false),
	'HW'		=> array('Hardware-Version',false),
	'Version'	=> array('Firmware-Version',false),
	'Revision'	=> array('Firmware-Revision',false),
	'Serial'	=> array('MAC-Adresse (LAN)','/\w\w(?=\w)/$0:/'),
	'OEM'		=> array('Branding',false),
	'Lang'		=> array('Sprache',false),
	'Annex'		=> array('Annex (Festnetz)',false),
	'Lab'		=> array('Labor',false),
	'Country'	=> array('Land-Vorwahl',false),
	'Flag'		=> array('Flags',false));
    foreach($array[1] as $key => $var)
     $array[0][$key] = str_pad(((isset($jason[$var])) ? $jason[$var][0] : $var)." ",20,'.')." ".((isset($jason[$var]) and $jason[$var][1])
	? preg_replace(preg_replace('/^((.).+?(?<!\\\\)\2).*\2(\w*)$/','$1$3',$jason[$var][1]),
	preg_replace('/^(.).+?(?<!\\\\)\1(.*)\1\w*$/','$2',$jason[$var][1]),$array[2][$key]) : $array[2][$key]);
    $array[0][] = 'Aktuelle Uhrzeit ... '.date('d.m.Y H:i:s',strtotime($data['Date']));
    out("\nBoxinfo:\n".implode("\n",$array[0]));
   }
   elseif($data)
    out("Keine BoxInfos erhalten");
   else
    out(errmsg(0,'request'));
  }
  elseif(ifset($val['gip'])) {				// Get Extern IP
   if(ifset($cfg['help']))
    out("$self <fritz.box:port> [GetIP|gip]".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self getip
$self 169.254.1.1 gip" : ""));
   elseif($var = getexternalip())
    out("IPv4: $var");
   elseif($var = errmsg(0,'getexternalip'))
    out($var);
   else
    out("Keine Externe IP-Adresse verf�gbar");
  }
  elseif(ifset($val['i'])) {				// Info (Intern)
   if(ifset($cfg['help']))
    out("$self [Info|i] <Funktion|Datei>\n
Funktionen:
PHP        PHPInfo() ausgeben
PenGramm   Kodierungstest mit Umlauten
<Datei>    Verschiedene Hashes von einer Datei berechnen".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self info
$self info php
$self info fb_config.php
$self i pg
$self i k
$self i -d" : ""));
   elseif($pset < $pmax and preg_match('/^(?:(PHP)|(G(?:LOBALS)?)|(pg|PanGramm)|(K(?:itty)?))$/i',$argv[$pset],$var)) {
    if(ifset($var[1])) {				// PHPInfo()
     ob_start();
     phpinfo();
     $data = ob_get_contents();
     ob_clean();
     out($data);
    }
    elseif(ifset($var[2]))				// GLOBALS
     out($GLOBALS);
    elseif(ifset($var[3]))				// Pangramm
     out('Welch fieser Katzentyp qu�lt da s��e V�gel blo� zum Jux?');
    elseif(ifset($var[4]))				// Kitty
     out(gzinflate(base64_decode('HYuxDcAgEAN7pnBnkN5Pk44RsgLS06anicTwgbg56U4GVse/MDNJZpGAasPFwRPkQFNrJyy7BBQUWbi3jgwMion7SuaoG1uJPQruZ873Aw')));
   }
   elseif($cfg['dbug']) {				// DEBUG: $argv & $cfg ausgeben mit Login-Test
    dbug('$argv');
    $sid = $cfg['sid'] = login();
    dbug('$cfg');
    if($sid)
     logout($sid);
   }
   else {						// FB_Tools-Version, PHP Kurzinfos und Hashes ausgeben
    $var = array("PHP ".phpversion()."/".php_sapi_name(),php_uname()."\n\n");
    out("$ver[0]\n".implode(($cfg['wrap'] and strlen($var[0].$var[1])+3 < $cfg['wrap']) ? " - " : "\n",$var));
    $file = ($pset < $pmax and file_exists($argv[$pset])) ? $argv[$pset++] : $script;
    $data = file_get_contents($file);
    $array = array('File' => $file,'Size' => number_format(filesize($file),0,0,'.')." Bytes",'CRC32' => crc_32($data),'MD5' => md5($data),'SHA1' => sha1($data));
    if(function_exists('hash') and $file != $script)
     $array = array_merge($array,array('SHA256' => hash('sha256',$data),'SHA512' => hash('sha512',$data)));
    $max = max(array_map('strlen',array_keys($array)));
    foreach($array as $key => $var)
     out(str_pad("$key:",$max+2,' ').(($cfg['wrap'] and $cfg['wrap'] < strlen($var)+$max+2) ? substr_replace($var,str_pad("\n",$max+3,' '),strlen($var)/2,0) : $var));
   }
  }
  elseif(ifset($val['rc'])) {				// ReConnect
   if(ifset($cfg['help']))
    out("$self <fritz.box:port> [ReConnect|rc]".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self reconnect
$self 169.254.1.1 rc" :""));
   else
    out(($var = forcetermination()) ? "Reconnect ausgef�hrt" : errmsg(0,'getexternalip'));
  }
  elseif(ifset($val['ss'])) {				// SystemStatus
   if(ifset($cfg['help']))
    out("$self <fritz.box:port> [SystemStatus|Status|ss] <supportcode>".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self systemstatus
$self 169.254.1.1 status
$self ss \"FRITZ!Box Fon WLAN 7390-B-010203-040506-000000-000000-147902-840522-22574-avm-de\"" : ""));
   else
    out(($var = supportcode(($pset < $pmax) ? $argv[$pset++] : false)) ? $var : errmsg(0,'supportcode'));
  }
  elseif(ifset($val['e'])) {				// Ereignisse
   if(ifset($cfg['help']) or $pset == $pmax)
    out("$self <user:pass@fritz.box:port> [Ereignisse|e] <Datei|*> <Filter> <Seperator>\n
Folgende Filter sind M�glich: alle, telefon, internet, usb, wlan, system
Hinweis: Der Dateiname wird mit strftime geparst (http://strftime.org)".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self password@fritz.box Ereignisse event.csv
$self password@fritz.box Ereignisse event-internet.csv internet ;
$self 192.168.178.1 e * -pw:secret
$self user:pass@169.254.1.1 e logs-%y%m%d.log" : ""));
   elseif($sid = (ifset($cfg['bsid'])) ? $cfg['bsid'] : login()) {	// Einloggen
    $file = $argv[$pset++];
    if($file == '*')					// * -> Ausgabe auf Console
     $file = false;
    elseif(strpos($file,'%') !== false)			// Dateiname mit strftime parsen?
     $file = strftime($file);
    $filter = ($pset < $pmax and preg_match('/^(alle|telefon|internet|usb|wlan|system)$/i',$argv[$pset++],$var)) ? strtolower($var[0]) : 'alle';
    $sep = ($pset < $pmax) ? $argv[$pset++] : " ";	// Seperator f�r CSV-Dateien
    $psep = preg_quote($sep,'/');
    $data = getevent($filter,$sep);			// Ereignisse holen
    if($file and file_exists($file) and $fp = fopen($file,'a+')) {// Ereignisse mit vorhandener Datei Syncronisieren
     if($cfg['dbug']/(1<<0)%2)
      dbug("Syncronisiere Ereignisse");
     fseek($fp,-256,SEEK_END);
     if(preg_match("/(\d\d)\.(\d\d)\.(?:20)?(\d\d)$psep([\d:]+)$psep(.*)\s*\$/",fread($fp,256),$last)) {	// Letzten Eintrag holen
      $date = strtotime("20$last[3]-$last[2]-$last[1] $last[4]");	// Datum vom Letzten Eintrag
      $array = explode("\r\n",$data);
      $data = array();
      foreach($array as $line)
       if(preg_match("/^(\d\d)\.(\d\d)\.(\d\d)$psep([\d:]+)$psep/",$line,$var) and strtotime("20$var[3]-$var[2]-$var[1] $var[4]") > $date)
        $data[] = $line;
      fwrite($fp,implode("\r\n",$data)."\r\n");
      out(($var = count($data)) ? (($var == 1) ? "Ein neues Ereignis wurde" : "$var neue Ereignisse wurden")." gespeichert" : "Keine neuen Ereignisse erhalten");
     }
     fclose($fp);
    }
    elseif($file)					// Neue Datei anlegen
     out((file_put_contents($file,$data)) ? (($var = substr_count($data,"\n")) ? (($var == 1) ? "Ein Ereignis wurde" : "$var Ereignisse wurden")." gespeichert"
     : "Keine Ereignisse erhalten") : "$file konnte nicht angelegt werden");
    elseif(preg_match_all("/^([\d.]+{$psep}[\d:]+)$psep(.*?)\s*$/m",$data,$array))	// Ereignisse ausgeben
     foreach($array[0] as $key => $var)
      out(wordwrap(trim($var),$cfg['wrap']-1,str_pad("\n",strlen($array[1][$key])+2," "),true));
    if(!ifset($cfg['bsid']))						// Ausloggen
     logout($sid);
   }
   else
    out(errmsg(0,'login'));
  }
  elseif(ifset($val['k'])) {				// Konfig
   if(ifset($cfg['help']) or $pset == $pmax)
    out("$self <user:pass@fritz.box:port> [Konfig|k] [Funktion] <Datei|Ordner> <Kennwort>\n
Funktionen:
ExPort          <Datei>  <Kennwort> - Konfig exportieren(1)
ExPort-DeCrypt  <Datei>  <Kennwort> - Konfig entschl�sseln und exportieren(1,3)
ExTrakt         <Ordner> <Kennwort> - Konfig entpackt anzeigen/exportieren(1)
ExTrakt-DeCrypt <Ordner> <Kennwort> - Konfig entpackt entschl./anz./exp.(1,3)
File            [Datei]  <Ordner> - Konfig-Infos aus Datei ausgeben(2)
File            [Ordner] [File]   - Konfig-Ordner in Datei zusammenpacken(2)
File-CalcSum    [Ordner] [File]   - Ver�nderter Konfig-Ordner Zusammensetzen(2)
File-JSON       [Datei] [Datei]   - Konfig-Daten in JSON konvertieren(2)
File-DeCrypt    [Datei] [Kennwort] <Datei> - Konfig-Daten entschl�sseln(1,3)
ImPort          [Datei|Ordner] <Kennwort>  - Konfig importieren(1)
ImPort-CalcSum  [Datei|Ordner] <Kennwort>  - Ver�nderte Konfig importieren(1)

(1) Anmeldung mit Logindaten erforderlich / (2) Ohne Fritz!Box nutzbar
(3) Fritz!Box mit OS 5 oder neuer erforderlich / [ ] Pflicht / < > Optional".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self password@fritz.box konfig export
$self fritz.box konfig extrakt
$self konfig file fritzbox.export
$self fritz.box konfig file-decrypt fb.export geheim fbdc.export -d
$self k fcs Export-Ordner fritzbox.export
$self username:password@fritz.box konfig import \"fb 7170.export\"
$self 169.254.1.1 k ipcs \"FRITZ.Box Fon WLAN 6360 85.04.86_01.01.00_0100.export\"" : ""));
   elseif(preg_match('/^(						# 1:Alle
	|i(p|mport)(cs|-calcsum)?					# 2:Import 3:CalcSum
	|e(p|xport)(?:(dc|-de(?:crypt|code))?)				# 4:Export 5:DeCrypt
	|(et|(?:extra[ck]t))?(?:(dc|-de(?:crypt|code))?)		# 6:Extrakt 7:DeCrypt
	|(f(?:ile)?)(?:(cs|-calcsum)?|(dc|-de(?:crypt|code))?|(-?json)?)# 8:File 9:CalcSum 10:DeCrypt 11:JSON
		)$/ix',$argv[$pset++],$mode)) {
    if($cfg['dbug']/(1<<3)%2)				// Debug Parameter
     dbug($mode);
    $mode = array_pad($mode,12,null);
    $file = ($pset < $pmax) ? $argv[$pset++] : false;
    $pass = ($pset < $pmax) ? $argv[$pset++] : false;
    if(($mode[2] or $mode[4] or $mode[6])) {		// Login Optionen
     if($sid = (ifset($cfg['bsid'])) ? $cfg['bsid'] : login()) {
      if($mode[5] or $mode[7]) {			// Kennwort-Entschl�sselung
       if($cfg['fiwa'] > 500 and ifset($cfg['boxinfo']['Name'],'/FRITZ!Box/i')) {
        if(!$pass)					// Im DeKode-Modus kein leeres Kennwort zulassen
         $pass = ($cfg['pass']) ? $cfg['pass'] : 'geheim';
       }
       else {
        out("Entschl�sselung wird nicht unterst�tzt");
        $mode[5] = $mode[7] = false;
       }
      }
      if($mode[4]) {					// Export
       if(is_dir($file)) {				// Im Ordner schreiben
        if($cfg['dbug']/(1<<0)%2)
         dbug("Wechsle zu Ordner $file");
        chdir($file);
        $file = false;
       }
       if($mode[5] and $pass and $data = cfgexport('array',$pass)) {	// Exportieren mit Entschl�sselten Benutzerdaten
        if($data[1] = konfigdecrypt($data[1],$pass,$sid)) {
         out(showaccessdata($data[1]));
         saverpdata($file,$data,'file.export');
        }
        else
         out(errmsg(0,'konfigdecrypt'));
       }
       else						// Export direkt File
        out(cfgexport(($file) ? $file : true,$pass) ? "Konfiguation wurde erfolgreich exportiert" : errmsg(0,'request'));
      }
      elseif($mode[6]) {				// Extrakt
       if($file and !file_exists($file)) {
        if($cfg['dbug']/(1<<0)%2)
         dbug("Erstelle Ordner $file");
        mkdir($file);					// Neues Verzeichniss erstellen
       }
       if(is_dir($file)) {				// Current-Dir setzen
        if($cfg['dbug']/(1<<0)%2)
         dbug("Wechsle zu Ordner $file");
        chdir($file);
       }
       if($data = cfgexport('array',$pass)) {		// Konfigdaten holen
        if($mode[7] and $pass and $data[2] = konfigdecrypt($data[1],$pass,$sid))	// Konfig Entschl�sseln
         out(cfginfo($data[2],$file,showaccessdata($data[2])));
        else
         out(cfginfo($data[1],$file));
       }
       else
        out(errmsg(0,'request'));
      }
      elseif($mode[2] and $file and file_exists($file))	// Import-Konfig
       out((cfgimport($file,$pass,$mode[3])) ? "Konfig wurde hochgeladen und wird nun bearbeitet" : errmsg(0,'cfgimport'));
      else
       out("$file kann nicht ge�ffnet werden!");
      if(!ifset($cfg['bsid']))
       logout($sid);
     }
     else
      out(errmsg(0,'login'));
    }
    elseif($mode[8] and !$mode[10] and !$mode[11] and is_file($file) and $data = file_get_contents($file)) {	// Converter-Modus File -> Dir
     if($pass) {		// Verzeichniss angegeben ?
      if(!file_exists($pass)) {
       if($cfg['dbug']/(1<<0)%2)
        dbug("Erstelle Ordner $pass");
       mkdir($pass);		// Neues Verzeichniss erstellen
      }
      if(is_dir($pass)) {
       if($cfg['dbug']/(1<<0)%2)
        dbug("Wechsle zu Ordner $pass");
       chdir($pass);		// Verzeichniss benutzen
      }
     }
     out(($data = cfginfo($data,$pass)) ? $data : "Keine Konfig Export-Datei angegeben");
    }
    elseif($mode[8] and !$mode[10] and !$mode[11] and is_dir($file) and $pass and (!file_exists($pass) or is_file($pass)))	// Converter-Modus Dir -> File
     out(($data = cfgmake($file,$mode[9],$pass)) ? $data : "Kein Konfig Export-Verzeichnis angegeben");
    elseif($mode[8] and $mode[10] and !$mode[11] and $pass and is_file($file) and $data = file_get_contents($file)) {		// Kennw�rter Entschl�sseln
     if($sid = (ifset($cfg['bsid'])) ? $cfg['bsid'] : login()) {
      if($cfg['fiwa'] > 500 and ifset($cfg['boxinfo']['Name'],'/FRITZ!Box/i')) {// Entschl�sselung durchf�hren
       if($data = konfigdecrypt($data,$pass,$sid)) {
        if($pset < $pmax) {
         $save = $argv[$pset++];
         if(is_dir($save)) {
          if($cfg['dbug']/(1<<0)%2)
           dbug("Wechsle zu Ordner $save");
          chdir($save);					// Verzeichniss benutzen
         }
         else {
          file_put_contents($save,$data);		// Entschl�sselte Konfig sichern
          $file = false;
         }
        }
        else
         $file = false;
        out(cfginfo($data,$file,showaccessdata($data)));// Daten als Text Pr�sentieren
       }
       else
        out("Entschl�sselung fehlgeschlagen, m�glicherweise ist das Kennwort falsch");
      }
      else
       out("Entschl�sselung wird nicht unterst�tzt");
      if(!ifset($cfg['bsid']))
       logout($sid);
     }
     else
      out(errmsg(0,'login'));
    }
    elseif($mode[8] and $mode[11] and is_file($file) and $pass)			// JSON Konverter
     if(function_exists('json_encode'))
      out(($data = file_get_contents($file) and $array = konfig2array($data))
	? ((file_put_contents($pass,json_encode($array))) ? "Konfig-Datei erflogreich in JSON konvertiert" : errmsg(0,'konfig2array'))
	: errmsg(0,'konfig2array'));
     else
      out('JSON wird von PHP '.phpversion()." nicht unterst�tzt");
    else
     out("Parameter-Ressourcen zu $mode[0] nicht gefunden oder nicht korrekt angegeben$help");
   }
   else
    out("Unbekannte Funktionsangabe f�r Konfig$help");
  }
  elseif(ifset($val['d'])) {				// Dial
   if(ifset($cfg['help']) or $pset == $pmax)
    out("$self <user:pass@fritz.box:port> [Dial|d] [Rufnummer] <Telefon>\n
Telefon:
1-4 -> FON 1-4 | 50 -> ISDN/DECT | 51-58 -> ISDN 1-8 | 60-65 -> DECT 1-6".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self password@fritz.box dial 0123456789 50
$self username:password@fritz.box dial \"#96*7*\"
$self 169.254.1.1 d - -pw:geheim" : ""));
   elseif($sid = (ifset($cfg['bsid'])) ? $cfg['bsid'] : login()) {
    out((dial($argv[$pset++],(($pset < $pmax) ? $argv[$pset++] : false))) ? "Rufnummer wurde gew�hlt" : errmsg(0,'dial'));
    if(!ifset($cfg['bsid']))
     logout($sid);
   }
   else
    out(errmsg(0,'login'));
  }
  elseif(ifset($val['sd'])) {				// Supportdaten
   if(ifset($cfg['help']))
    out("$self <user:pass@fritz.box:port> [SupportDaten|sd] <Datei|Ordner|.> <ExTrakt>".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self password@fritz.box supportdaten support.txt
$self password@fritz.box supportdaten sd-ordner extrakt -d
$self 169.254.1.1 sd -pw:geheim" : ""));
   elseif($sid = (ifset($cfg['bsid'])) ? $cfg['bsid'] : login()) {
    $file = ($pset < $pmax) ? $argv[$pset++] : false;
    $mode = ($pset < $pmax and ifset($argv[$pset++],'/^(ExTrakt|et)$/i')) ? true : false;
    if($mode and $file and !file_exists($file)) {
     if($cfg['dbug']/(1<<0)%2)
      dbug("Erstelle Ordner $file");
     mkdir($file);				// Neues Verzeichniss erstellen
    }
    if(is_dir($file)) {				// Current-Dir setzen
     if($cfg['dbug']/(1<<0)%2)
      dbug("Wechsle zu Ordner $file");
     chdir($file);
     $file = './';
    }
    if($mode and $cfg['fiwa'] >= 650) {		// Extrakt
     if($GLOBALS['cfg']['dbug']/(1<<0)%2)
      dbug("Hole Support-Daten zum extrahieren");
     if($data = supportdata() and $text = supportdataextrakt($data[1])) {
      out("\n$text");
     }
     elseif($data[1])
      file_put_contents(($file != './') ? $file : ((preg_match('/filename=(["\']?)(.*?)\1/i',$data['Content-Disposition'],$var))
       ? $var[2] : "Supportdaten.txt"),$data[1]);
    }
    elseif(supportdata(($file) ? $file : './'))
     out("Supportdaten wurden erfolgreich gespeichert");
    else
     out(errmsg(0,'supportdata'));
    if(!ifset($cfg['bsid']))
     logout($sid);
   }
   else
    out(errmsg(0,'login'));
  }
  elseif(ifset($val['lio'])) {				// Manuelles Login / Logout
   if(ifset($cfg['help']))
    out("$self <user:pass@fritz.box:port> [LogIn|LogOut|li|lo] <-s:sid>".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self password@fritz.box login > sid.txt
$self fritz.box login -pw:password -o:sid.txt
$self fritz.box logout -s:sid.txt
$self fritz.box logout -s:0123456789abcdef" : ""));
   elseif(preg_match('/l(?:og)?(?:(in?)|(o(?:ut)))/i',$val['lio'],$var)) {
    if(ifset($var[1]))
     out(login());
    elseif(ifset($var[2]))
     logout($cfg['sid']);
   }
  }
  elseif(ifset($val['pi'])) {				// Plugin
   if($pset == $pmax)
    out("$self <user:pass@fritz.box:port> [PlugIn|pi] [Script-Datei] <...>".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self password@fritz.box plugin fbtp_led.php off
$self fritz.box plugin fbtp_test.php" : "")."\n
WARNUNG: Es gibt KEINE Pr�fung auf Malware!");
   elseif($pset < $pmax and file_exists($argv[$pset]))
    include $argv[$pset++];
   else
    out("Kein Plugin-Script angegeben");
  }
  elseif(ifset($val['ug'])) {				// UpGrade (Intern)
   if(ifset($cfg['help']))
    out("$self [UpGrade|ug] <Check>".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self upgrade check
$self ug
$self ug c" : ""));
   elseif($uplink and $fbnet = request('GET-array',"$uplink[1]md5",0,0,$uplink[0],80)) {	// Update-Check
    $coo = (ifset($fbnet['X-Cookie'])) ? "\n".$fbnet['X-Cookie'] : "";
    if(preg_match("/((\d\d)\.(\d\d)\.(\d{4}))\s[\d:]+\s*\((\w+)\s([\d.]+)\)(?:.*?(\w+)\s\*\w+\.$ext(?=\s))?/s",$fbnet[1],$up)) {
     if(intval($up[4].$up[3].$up[2]) > $ver[8] or floatval($up[6]) > $ver[2]) {
      out("Ein Update ist verf�gbar: $up[5] $up[6] vom $up[1]");
      if($pset == $pmax) {
       out("Installiere Update ... ");
       $manuell = "!\nBitte installieren Sie es von http://$uplink[0]/.dg manuell!";
       if(ifset($up[7]) and $fbnet = @request('GET',"$uplink[1]$ext.gz",0,0,$uplink[0],80)) {
        $rename = preg_replace('/(?=(\.\w+)?$)/',"_$ver[2].bak",$script,1);
        if(function_exists('gzdecode') and $var = @gzdecode($fbnet) and md5($var) == $up[7] and @rename($script,$rename)) {// Update ab PHP5
         file_put_contents($script,$var);
         $var = true;
        }
        elseif(!function_exists('gzdecode') and $fp = fopen("$script.tmp",'w')) {		// Update ohne gzdecode
         fwrite($fp,$fbnet);
         fclose($fp);
         $var = '';
         if($gz=@gzopen("$script.tmp",'rb')) {							// gzdecode Workaround
          while(!gzeof($gz))
           $var .= gzread($gz,$cfg['sbuf']);
          gzclose($gz);
          unlink("$script.tmp");
         }
         if(md5($var) == $up[7] and @rename($script,$rename) and $fp = fopen($script,'w')) {	// Script �berschreiben
          fwrite($fp,$var);
          fclose($fp);
          $var = true;
         }
        }
        if($var === true) {
         @chmod($script,intval(fileperms($script),8));
         out("abgeschlossen!");
        }
        else
         out("fehlgeschlagen$manuell");
       }
       else
        out("Automatisches Update ist nicht verf�gbar$manuell");
      }
     }
     else {
      @touch($script);										// Aktuelles Datum setzen
      out("Kein neues Update verf�gbar!");
     }
    }
    else
     out("Update-Server sagt NEIN!");
    out($coo);
   }
    else
     out("Computer sagt NEIN!");
  }
  else
   out("M�glichweise ist ein unerwarterer und unbekannter, sowie mysteri�ser Fehler aufgetreten :-)");
 }
 elseif(ifset($cfg['dbug'])) {				// DEBUG: $argv & $cfg ausgeben
  dbug('$argv');
  dbug('$cfg');
 }
 else {							// Hilfe ausgeben
  out("$self <user:pass@fritz.box:port> [mode] <parameter> ... <option>".((ifset($cfg['help'])) ? "\n
Modes:
BoxInfo      - Modell, Firmware-Version und MAC-Adresse ausgeben
Dial         - Rufnummer w�hlen(2)
Ereignisse   - Systemmeldungen abrufen(2)
GetIP        - Aktuelle externe IPv4-Adresse ausgeben(1)
Info         - FB-Tools Version, PHP Version, MD5/SHA1 Checksum
Konfig       - Einstellungen Ex/Importieren(2,3)
Login/Logout - Manuelles Einloggen f�r Scriptdateien(2)
PlugIn       - Weitere Funktion per Plugin-Script einbinden
ReConnect    - Neueinwahl ins Internet(1)
SupportDaten - AVM-Supportdaten Speichern(2)
SystemStatus - Modell, Version, Laufzeiten, Neustarts und Status ausgeben(3)
UpGrade      - FB-Tools Updaten oder auf aktuelle Version pr�fen(3)

(1) Aktiviertes UPnP erforderlich / (2) Anmeldung mit Logindaten erforderlich
(3) Teilweise ohne Fritz!Box nutzbar / [ ] Pflicht / < > Optional".((preg_match('/[ab]/i',$cfg['help'])) ? "\n
Beispiele:
$self secret@fritz.box supportdaten
$self hans:geheim@fritz.box konfig export
$self secret@169.254.1.1 Ereignisse * -w:80-c:utf8-o:file.txt
$self dial \"**600\" -fb:fritz.box -un:max -pw:geheim
$self fritz.box plugin fbtp_led.php off -pw:secret
$self -h:alles": "")."\n
Hilfe bekommen Sie mit -h oder -h:a (Alles) -h:b (Beispiele) -h:o (Optionen)
Eine Anleitung finden Sie auf $ver[7]/.dg" : $help));
 }
 if(preg_match('/[ao]/i',$cfg['help'])) {		// Optionen ausgeben
  out("
Alle Optionen:
         -d             - Debuginfos
         -h:<a|b|o|s>   - Hilfe (Alles, Beispiele, Optionen, Standard)
Console: -c:[CodePage]  - Kodierung der Umlaute ($cfg[char])
         -w:[Breite]    - Wortumbruch ($cfg[wrap])
         -o:[Datei]     - Ansi-Ausgabe in Datei
Request: -b:[Bytes]     - Buffergr��e ($cfg[sbuf])
         -t:[Sekunden]  - TCP/IP Timeout ($cfg[tout])
Login:   -s:[SID|Datei] - Manuelle SID Angabe (F�r Scriptdateien)
         -fb:[Host]     - Alternative Fritz!Box Angabe ($cfg[host])
         -fw:[Version]  - Manuelle Angabe der Firmware-Version ($cfg[fiwa])
         -pt:[Port]     - Alternative Port Angabe ($cfg[port])
         -pw:[Pass]     - Alternative Kennwort Angabe
         -un:[User]     - Alternative Benutzername Angabe");
 }
 if($cfg['dbug'] and $cfg['error'])			// Fehler bei -d ausgeben
  dbug("Fehler:\n".print_r($cfg['error'],true));
}

?>
