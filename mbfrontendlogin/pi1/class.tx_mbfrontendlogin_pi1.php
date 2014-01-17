<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Markus Brunner <mail@markusbrunner-design.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'MB :: Frontend User Login' for the 'mbfrontendlogin' extension.
 *
 * @author	Markus Brunner <mail@markusbrunner-design.de>
 * @package	TYPO3
 * @subpackage	tx_mbfrontendlogin
 */
class tx_mbfrontendlogin_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_mbfrontendlogin_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_mbfrontendlogin_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'mbfrontendlogin';	// The extension key.
        
        protected $smarty;
        protected $extPath;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
    $this->extPath = t3lib_extMgm::siteRelPath($this->extKey);
          
    // init flexform
    $this->pi_initPIflexForm();
    $switchView = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'selectmode', 'sDEF');
    
    // init smarty
    $this->initSmarty();
    
    // login user
    $this->processAuth();

    // switch view
    switch($switchView) {
      case 'forgot_pw':
        $content = $this->showForgotPw();
        break;
      default:
        $content = $this->showLoginLogout();
        break;
    }
	
		return $content;
	}
  
  /**
   * login view
   */
  protected function showLoginLogout() {
    if(is_array($GLOBALS["TSFE"]->fe_user->user)) {

      // assign variables
      $this->getSmarty()->assign('action',$this->pi_getPageLink($GLOBALS['TSFE']->id));
      $this->getSmarty()->assign('piVars',$this->piVars);
      $this->getSmarty()->assign('user',$GLOBALS["TSFE"]->fe_user->user);
      $this->getSmarty()->assign('login',$this->conf['login.']);

      // logout view
      $template = !empty($this->conf['smarty.']['templates.']['logout']) ? $this->conf['smarty.']['templates.']['logout'] : 'logout.tpl';

      //get smarty content
      $content = $this->getSmarty()->display($template,$this->extKey);
    } else {

      // assign variables
      $this->getSmarty()->assign('action',$this->pi_getPageLink($GLOBALS['TSFE']->id));
      $this->getSmarty()->assign('piVars',$this->piVars);
      $this->getSmarty()->assign('security',$this->conf['security.']);
      $this->getSmarty()->assign('login',$this->conf['login.']);
      if(!empty($this->conf['login.']['forgotPasswordPageUid'])) {
        $this->getSmarty()->assign('forgot_pw_link',$this->pi_getPageLink($this->conf['login.']['forgotPasswordPageUid']));
      }

      // login view
      $template = !empty($this->conf['smarty.']['templates.']['login']) ? $this->conf['smarty.']['templates.']['login'] : 'login.tpl';

      //get smarty content
      $content = $this->getSmarty()->display($template,$this->extKey);
    }
    return $content;
  }
  
  /**
   * forgot pw view
   */
  protected function showForgotPw() {

    // assign variables
    $this->getSmarty()->assign('action',$this->pi_getPageLink($GLOBALS['TSFE']->id));
    $this->getSmarty()->assign('piVars',$this->piVars);

    // forgot_pw view
    $template = !empty($this->conf['smarty.']['templates.']['forgot_pw']) ? $this->conf['smarty.']['templates.']['forgot_pw'] : 'forgot_pw.tpl';

    //get smarty content
    $content = $this->getSmarty()->display($template,$this->extKey);
    return $content;
  }
        
  /**
   * Feuser Login Process
   */
  protected function processAuth() {

    // login
    if(!empty($this->piVars['login'])) {

      // password
      $password = $this->piVars['password'];
      
      // delete additional salting
      $password = str_replace($this->conf['security.']['passwordSalt'],'',$password);
      
        // !saltedpasswords => MD5?
        if(
            (
                !t3lib_extMgm::isLoaded('saltedpasswords') 
                ||
                !tx_saltedpasswords_div::isUsageEnabled('FE')
            )
            &&
            (
              (
                $this->conf['security.']['feuserPasswordIsMD5'] == 'true'
                && $this->conf['security.']['enableFrontendMD5'] == 'false' 
              )
              ||
              (
                $this->conf['security.']['feuserPasswordIsMD5'] == 'true'
                && strlen($password) < 32
              )
            )
        ) {
          $password = md5($password);
        }

      // process login
      $resUser = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
        '*',
        'fe_users',
        $this->conf['login.']['feuserAuthField'].'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['user'],'fe_users')
      );
      $user = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($resUser);
      if($user !== FALSE) {
        
        // saltedpasswords
        #$password = $password;  // plain-text password
        $saltedPassword = $user['password'];  // salted user password hash
        $success = FALSE; // keeps status if plain-text password matches given salted user password hash
        if (t3lib_extMgm::isLoaded('saltedpasswords') && tx_saltedpasswords_div::isUsageEnabled('FE')) {
          $objSalt = tx_saltedpasswords_salts_factory::getSaltingInstance($saltedPassword);
          if (is_object($objSalt)) {
            $success = $objSalt->checkPassword($password, $saltedPassword);
          }
        }
        // no saltedpaswords
        elseif($password == $user['password']) {
          $success = TRUE;
        }

        if($success) {
          $GLOBALS["TSFE"]->fe_user->createUserSession($user);
          $GLOBALS["TSFE"]->fe_user->loginSessionStarted = TRUE;
          $GLOBALS["TSFE"]->fe_user->user = $GLOBALS["TSFE"]->fe_user->fetchUserSession();

          // process redirect
          $host = (!empty($GLOBALS['TSFE']->baseUrl)) ?$GLOBALS['TSFE']->baseUrl : t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').'/';
          if(!empty($this->conf['redirects.']['reloadActualPage'])) {
            header("Location: ".$host.$this->pi_getPageLink(intval($GLOBALS['TSFE']->id)));
          } elseif(!empty($this->conf['redirects.']['afterLogin'])) {
            header("Location: ".$host.$this->pi_getPageLink(intval($this->conf['redirects.']['afterLogin'])));
          }
        }
        return $success;
      }
      return false;
    } 

    // logout
    elseif(!empty($this->piVars['logout'])) {

      // logout user
      $GLOBALS["TSFE"]->fe_user->logoff();
      #unset($GLOBALS["TSFE"]->fe_user->user);

      // process redirect
      $host = (!empty($GLOBALS['TSFE']->baseUrl)) ?$GLOBALS['TSFE']->baseUrl : t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').'/';
      if(!empty($this->conf['redirects.']['reloadActualPage'])) {
        header("Location: ".$host.$this->pi_getPageLink(intval($GLOBALS['TSFE']->id)));
      } elseif(!empty($this->conf['redirects.']['afterLogout'])) {
        header("Location: ".$host.$this->pi_getPageLink(intval($this->conf['redirects.']['afterLogout'])));
      }

      return true;
    }
    
    // forgot pw
    elseif(!empty($this->piVars['forgot_pw'])) {
      
      // get user
      $user = mysql_real_escape_string($this->piVars['user']);
      $query = 'SELECT uid,username,email FROM fe_users WHERE '.$this->conf['login.']['feuserAuthField'].'="'.$user.'" '.$this->cObj->enableFields('fe_users');
      $res = $GLOBALS['TYPO3_DB']->sql_query($query);
      $currentFeuser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
      
      if(!empty($currentFeuser['uid'])) {
        $generatedPw = $generatedPwDB = sha1($this->conf['security.']['passwordSalt'].time());
        if (t3lib_extMgm::isLoaded('saltedpasswords') && tx_saltedpasswords_div::isUsageEnabled('FE')) {
          $objSalt = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL);
          if (is_object($objSalt)) {
            $generatedPwDB = $objSalt->getHashedPassword($generatedPw);
          }
        }
        elseif($this->conf['security.']['feuserPasswordIsMD5'] == 'true') {
          $generatedPwDB = md5($generatedPw);
        }
        $query = 'UPDATE fe_users SET password = "'.$generatedPwDB.'" WHERE uid='.intval($currentFeuser['uid']);
        if($GLOBALS['TYPO3_DB']->sql_query($query)) {
          $email = (!empty($currentFeuser['email'])) ? $currentFeuser['email'] : $currentFeuser['username'];
          
          // Mail for forgot_pw
            // assign variables
            $this->getSmarty()->assign('password',$generatedPw);

            // forgot_pw view
            $template = !empty($this->conf['smarty.']['templates.']['forgot_pw_mail']) ? $this->conf['smarty.']['templates.']['forgot_pw_mail'] : 'forgot_pw_mail.tpl';

            //get smarty content for mail
            $message = $this->getSmarty()->display($template,$this->extKey);
          
          if(!$this->sendNewMail($email, $message, $this->pi_getLL('forgot_pw'))) {
            $this->getSmarty()->assign('error',$this->pi_getLL('error_forgot_pw_mail'));
          } else {
            $this->getSmarty()->assign('success',$this->pi_getLL('success_forgot_pw_mail'));
          }
          // test
          #t3lib_div::debug(array('$email' => $email, '$message' => $message),'debug '.__FILE__.__LINE__);
        } else {
          $this->getSmarty()->assign('error',$this->pi_getLL('error_forgot_pw'));
          return false;
        }
      } else {
        $this->getSmarty()->assign('error',$this->pi_getLL('error_forgot_pw'));
        return false;
      }
    }

    // nothing prcessed
    return false;
  }
  
	/**
	 * Sends an E-Mail.
	 *
	 * @param string $to
	 * @param string $message
	 * @param string $subject
	 * @return bool (true if sent)
	 */
	function sendNewMail($to, $message, $subject) {
	
		$fromName = $this->conf['mail.']['from'] ? $this->conf['mail.']['from'] : 'set TS; plugin.tx_mbfrontendlogin_pi1.mail.from';
		$fromMail = $this->conf['mail.']['fromMail'] ? $this->conf['mail.']['fromMail'] : 'set TS; plugin.tx_mbfrontendlogin_pi1.mail.fromMail';
		$replyMail = $this->conf['mail.']['replyMail'] ? $this->conf['mail.']['replyMail'] : 'set TS; plugin.tx_mbfrontendlogin_pi1.mail.replyMail';
		$mailType = $this->conf['mail.']['mailType'] ? $this->conf['mail.']['mailType'] : 'text/html';
		$mailCharset = $this->conf['mail.']['mailCharset'] ? $this->conf['mail.']['mailCharset'] : 'utf-8';
		$mail_header  = "MIME-Version: 1.0\n";
		ini_set('sendmail_from', $fromMail); 
		$mail_header  = "From: $fromName <$fromMail>\n";
		$mail_header .= "Reply-To: $replyMail\n";
		$mail_header .= "Content-Type: $mailType; charset=$mailCharset\n";

		// Send the message
		if ($mail = mail($to, $subject, $message , $mail_header)) {
			return true;
		}
		else {
			return false;
		}	
	}

  /**====================================================================================================================================================
  * Function to init the plugin (smarty, language)
  */
  function initSmarty(){
    // Create a new instance of Smarty
    $this->smarty = tx_smarty::smarty();
    $templateDir = !empty($this->conf['smarty.']['template_dir']) ? $this->conf['smarty.']['template_dir'] : t3lib_extMgm::extPath($this->extKey).'res/smarty/templates/';
    $this->getSmarty()->template_dir = $templateDir;
    $compileDir = !empty($this->conf['smarty.']['compile_dir']) ? $this->conf['smarty.']['compile_dir'] : t3lib_extMgm::extPath($this->extKey).'res/smarty/templates_c/';
    $this->getSmarty()->compile_dir = $compileDir;
    $this->getSmarty()->compile_id = $this->extKey;
    $this->getSmarty()->assign('baseURL',$GLOBALS['TSFE']->baseUrl);
    $this->getSmarty()->assign('siteScript',$GLOBALS['TSFE']->siteScript);
    $this->getSmarty()->assign('lang',$this->getLanguageArray());
    $this->getSmarty()->assign('pageid',$GLOBALS['TSFE']->id);
    $this->getSmarty()->assign('extPath',$this->extPath);
    $this->getSmarty()->assign('prefixId',$this->prefixId);
  }
  /**
   * @return Smarty Obj
   */
  public function getSmarty(){
          return $this->smarty;
  }
  /**
   * Load Language
   * 
   * @return Language Array
   */
  public function getLanguageArray() {
    $lang = array();
    foreach ($this->LOCAL_LANG['default'] as $key => $value){
      // TYPO3 4.6.* => value in [0]['target']
      if(is_array($this->LOCAL_LANG[$this->LLkey][$key])) {
        if($this->LOCAL_LANG[$this->LLkey][$key][0]['target']) {
          $lang[$key] = $this->LOCAL_LANG[$this->LLkey][$key][0]['target'];
        } else {
          $lang[$key] = $value[0]['target'];
        }
      } 
      // older TYPO3 Versions
      else {
        if($this->LOCAL_LANG[$this->LLkey][$key]) {
          $lang[$key] = $this->LOCAL_LANG[$this->LLkey][$key];
        } else {
          $lang[$key] = $value;
        }
      }
    }				
    return $lang;		
  }
  
  
  
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mbfrontendlogin/pi1/class.tx_mbfrontendlogin_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mbfrontendlogin/pi1/class.tx_mbfrontendlogin_pi1.php']);
}

?>