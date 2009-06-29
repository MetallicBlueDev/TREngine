<?php
/*
 * project : [EKRAN] Architecte
 * file    : auth.class.php
 * owner   : Baroiller P.E.
 * date    : oct. 08
 * abstract: Gestion des droits d'accès administrateurs
 * 
 */

if(defined('package_admin_auth')) return true;

define('AUTH_SESSION_NAME','manager_auth');
class manager_auth {  
  
  # desctruction de la session admin
  function kill() {
    if(isset($_SESSION[AUTH_SESSION_NAME])) unset($_SESSION[AUTH_SESSION_NAME]);       
  }

  # vérifie que l'admin "présenté" est bien le bon
  function done() {
    if(isset($_SESSION[AUTH_SESSION_NAME])) {
      $_sess = $_SESSION[AUTH_SESSION_NAME];
      if(isset($_sess['AdminId'])) {
        if($_sess['AdminId']>0) {
          if(isset($_SERVER['PHP_AUTH_USER'])&& isset($_SERVER['PHP_AUTH_PW'])) {
            if($_sess['key'] = md5($_SERVER['PHP_AUTH_USER'].$_SERVER['PHP_AUTH_PW'])) {
              return true;
            }
          }
        }
      }
    }
    return false;
  }
  
  # vérifie l'état de la demande d'authentification
  function waiting() {
    if(isset($_SESSION[AUTH_SESSION_NAME])) {
      $_sess = $_SESSION[AUTH_SESSION_NAME];
      if(isset($_sess['logout'])) {
        return $_sess['logout'];
      }
    }
    return false;
  }
  # récupère l'id admin
  function getId() {
    return $_SESSION[AUTH_SESSION_NAME]['AdminId'];
  }
  
  # récupère l'identifiant de l'admin
  function getLogin() {
    return $_SESSION[AUTH_SESSION_NAME]['AdminLogin'];
  }
  
  # récupère le niveau d'accès de l'admin
  function getLevel() {
    return $_SESSION[AUTH_SESSION_NAME]['AdminLevel'];
  }
  
  # récupère l'adresse e-mail de l'admin
  function getEmail() {
    return $_SESSION[AUTH_SESSION_NAME]['AdminEmail'];
  }

  # récupère la signature de l'admin
  function getSignature() {
    return $_SESSION[AUTH_SESSION_NAME]['AdminSignature'];
  }

  # récupère le nom de l'admin
  function getLastName() {
    return $_SESSION[AUTH_SESSION_NAME]['AdminName'];
  }
  # récupère le prénom de l'admin
  function getFirstName() {
    return $_SESSION[AUTH_SESSION_NAME]['AdminFirstName'];
  }
  
  # activation de l'accès pour l'admin présenté
  function activate(& $oAdmin) {    
    # génération de la clé pour la session en cours    
    $_key = md5($_SERVER['PHP_AUTH_USER'].$_SERVER['PHP_AUTH_PW']);
    $_SESSION[AUTH_SESSION_NAME] = array(
      'AdminId' => $oAdmin->field('f_id'),
      'AdminLogin' => $oAdmin->field('f_login'),
      'AdminName' => $oAdmin->field('f_name'),
      'AdminLevel' => $oAdmin->field('f_auth_level'),
      'AdminSignature' => $oAdmin->field('f_sign'),
      'AdminEmail' => $oAdmin->field('f_email'),      
      'key' => $_key
    );
  }
  
  # déconnection du compte admin
  function disconnect() {
    manager_auth::kill();
    $url=WWW_ROOT."/index.php";
    $_SESSION[AUTH_SESSION_NAME] = array('logout' => true); 
    session_write_close();
    die(header("location: ".$url));
  }
  
  # procédure d'authentification ( contrôle et demande login )
  function process() {      
	  // If not empty, display values for variables
    
	  if( isset($_SERVER['PHP_AUTH_USER'])&& (!manager_auth::waiting())) {      	   
      $banned_idents = array('demo','test','guest','user','root','admin','contrib','daoditu','contributiel');
      if( (in_array($_SERVER['PHP_AUTH_USER'],$banned_idents)) || (in_array($_SERVER['PHP_AUTH_PW'],$banned_idents))) {
        header('HTTP/1.0 401 Unauthorized');      
        die('<H1>Accès non authorisé</H1>');
      }  	    
      $oAdmin = & new admin_user();
      if($oAdmin->exists($_SERVER['PHP_AUTH_USER'])) {        
        $oAdmin->loadFromName($_SERVER['PHP_AUTH_USER']);
        if($oAdmin->field('f_pass') == $_SERVER['PHP_AUTH_PW']) {
          manager_auth::activate(&$oAdmin);
          $url=null;
          if(isset($_SERVER['HTTP_REFERER']))  $url = $_SERVER['HTTP_REFERER'];
          if(!$url) if(isset($_SERVER['SCRIPT_NAME'])) $url=$_SERVER['SCRIPT_NAME'];          
          if(!$url) if(isset($_SERVER['REQUEST_URI'])) $url=$_SERVER['REQUEST_URI'];
          if(strstr($url,"authlogin")) {
            unset($url);            
            $url=WWW_ROOT."/";
          }          
          if(!empty($url)) die(header("Location: ".$url));
          return true;
        }
      }    
      // Admin loggin error, notify "system root"
      //manager_auth::bad_auth();    
	  }	
	  manager_auth::kill();
    header('WWW-Authenticate: Basic realm="Accès administrateur"');
    header('HTTP/1.0 401 Unauthorized');
    echo('<H1>Accès non authorisé</H1>');
    die();
	  
  }
  
  # envoi d'une notif en cas de mauvais login/pass
  function bad_auth() {
    $body ="erreur d'identification administrateur\n";
    $body.="URL du site : ".WWW_ROOT."\n";
    $body.="Navigateur  : ".$_SERVER['HTTP_USER_AGENT']."\n";
    $body.="Origine     : ".$_SERVER['REMOTE_ADDR']." (".@$_SERVER['REMOTE_HOST'].")\n";
    $body.="URL demandée: ".$_SERVER['REQUEST_URI']."\n";
    $body.="Login       : ".$_SERVER['PHP_AUTH_USER']."\n";
    $body.="Mot de passe: ".$_SERVER['PHP_AUTH_PW']."\n";  
    $_params = array(
      'isHTML' => 0,
      'sender_name' => 'connect@daoditu.com',
      'sender_email' => 'connect@daoditu.com',
      'recipients' => array('root@daoditu.com'),
      'subject' => "ERREUR LOGIN (".WWW_ROOT.")",
      'body' => $body
    );
    /*mail_notification  ($_params);      */

  }
}

# patch for OVH
if(isset($_SERVER['REMOTE_USER'])) {  
  if((!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_USER']))
      && preg_match('/Basic+(.*)$/i', $_SERVER['REMOTE_USER'], $matches)) {
    list($name, $password) = explode(':', base64_decode($matches[1]));
    $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
    $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
  }
}

define('package_admin_auth',1);
return true;
?>