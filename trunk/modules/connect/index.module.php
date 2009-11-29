<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Module d'interface et de traitement entre le site et le client
 * 
 * @author Sebastien Villemain
 *
 */
class Module_Connect_Index extends Module_Model {
	
	public function display() {
		if (Core_Session::getInstance()->isUser()) {
			$this->account();
		} else {
			$this->logon();
		}
	}
	
	public function account() {
		if (Core_Session::getInstance()->isUser()) {
			Core_Loader::classLoader("Libs_Form");
			Core_Loader::classLoader("Libs_Tabs");
			// Ajout des formulaires dans les onglets
			$accountTabs = new Libs_Tabs("accounttabs");
			$accountTabs->addTab(ACCOUNT_PROFILE, $this->tabProfile());
			$accountTabs->addTab(ACCOUNT_PRIVATE, $this->tabAccount());
			$accountTabs->addTab(ACCOUNT_AVATAR, $this->tabAvatar());
			if (Core_Session::$userRang > 1) {
				$accountTabs->addTab(ACCOUNT_ADMIN, $this->tabAdmin());
			}
			echo $accountTabs->render();
		} else {
			$this->display();
		}
	}
	
	private function &tabProfile() {
		$form = new Libs_Form("profile");
		$form->setTitle(ACCOUNT_PROFILE_TITLE);
		$form->setDescription(ACCOUNT_PROFILE_DESCRIPTION);
		$form->addSpace();
		$form->addTextarea("signature", ACCOUNT_PROFILE_SIGNATURE, "", "style=\"display: block;\" rows=\"5\" cols=\"50\"", Core_Session::$userSignature);
		$form->addInputHidden("mod", "connect");
		$form->addInputHidden("view", "account");
		$form->addInputHidden("layout", "module");
		$form->addInputSubmit("submit", "", "value=\"" . VALID . "\"");
		return $form->render();
	}
	
	private function &tabAccount() {		
		$form = new Libs_Form("accountprivate");
		$form->setTitle(ACCOUNT_PRIVATE_TITLE);
		$form->setDescription(ACCOUNT_PRIVATE_DESCRIPTION);
		$form->addSpace();
		$form->addInputText("name", LOGIN, "", "value=\"" . Core_Session::$userName . "\"");
		$form->addInputPassword("pass", PASSWORD);
		$form->addInputPassword("pass2", ACCOUNT_PRIVATE_PASSWORD_CONFIRME);
		$form->addInputText("mail", MAIL, "", "value=\"" . Core_Session::$userMail . "\"");
		
		// Liste des langages disponibles
		$form->addSpace();
		$form->addSelectOpenTag("langue", ACCOUNT_PRIVATE_LANGUE);
		$langues = Core_Translate::listLanguages();
		$currentLanguage = Core_Translate::getCurrentLanguage();
		$form->addSelectItemTag($currentLanguage, "", true);
		foreach($langues as $langue) {
			if ($langue == $currentLanguage) continue;
			$form->addSelectItemTag($langue);
		}
		$form->addSelectCloseTag();
		
		// Liste des templates disponibles
		if (Core_Loader::isCallable("Libs_MakeStyle")) {
			$form->addSelectOpenTag("template", ACCOUNT_PRIVATE_TEMPLATE);
			$templates = Libs_MakeStyle::listTemplates();
			$currentTemplate = Libs_MakeStyle::getCurrentTemplate();
			$form->addSelectItemTag($currentTemplate, "", true);
			foreach($templates as $template) {
				if ($template == $currentTemplate) continue;
				$form->addSelectItemTag($template);
			}
			$form->addSelectCloseTag();
		}
		$form->addSpace();
		$form->addInputHidden("mod", "connect");
		$form->addInputHidden("view", "sendAccount");
		$form->addInputHidden("layout", "module");
		$form->addInputSubmit("submit", "", "value=\"" . VALID . "\"");
		Core_Html::getInstance()->addJavascript("validAccount('#form-accountprivate', '#form-accountprivate-name-input', '#form-accountprivate-pass-input', '#form-accountprivate-pass2-input', '#form-accountprivate-mail-input');");
		return $form->render();
	}
	
	public function sendAccount() {
		$name = Core_Request::getWord("name");
		$pass = Core_Request::getString("pass");
		$pass2 = Core_Request::getString("pass2");
		$mail = Core_Request::getString("mail");
		$langue = Core_Request::getString("langue");
		$template = Core_Request::getString("template");
		
		if (Core_Session::$userName != $name || Core_Session::$userMail != $mail || Core_Session::$userLanguage != $langue || Core_Session::$userTemplate != $template) {
			if (Core_Session::getInstance()->validLogin($name)) {
				$validName = true;
				if (Core_Session::$userName != $name) {
					$name = Exec_Entities::secureText($name);
					Core_Sql::select(
						Core_Table::$USERS_TABLE,
						array("user_id"),
						array("name = '" . $name . "'")
					);
					if (Core_Sql::affectedRows() > 0) {
						$validName = false;
						Core_Exception::addNoteError(ACCOUNT_PRIVATE_LOGIN_IS_ALLOWED);
					}
				}
				if ($validName) {
					Core_Loader::classLoader("Exec_Mailer");
					if (Exec_Mailer::validMail($mail)) {
						$values = array();
						if (!empty($pass) || !empty($pass2)) {
							if ($pass == $pass2) {
								if (Core_Session::getInstance()->validPassword($pass)) {
									$values['pass'] = Core_Session::getInstance()->cryptPass($pass);
								} else {
									$this->errorBox();
								}
							} else {
								Core_Exception::addNoteError(ACCOUNT_PRIVATE_PASSWORD_INVALID_CONFIRME);
							}
						}
						$values['name'] = $name;
						$values['mail'] = $mail;
						$values['langue'] = $langue;
						$values['template'] = $template;
						Core_Sql::update(
							Core_Table::$USERS_TABLE,
							$values,
							array("user_id = '" . Core_Session::$userId . "'")
						);
						echo "essai..";
						if (Core_Sql::affectedRows() > 0) {echo "OKKKK";
							Core_Session::getInstance()->refreshConnection();
							Core_Exception::addInfoError(DATA_SAVED);
						}
					} else {
						Core_Exception::addNoteError(INVALID_MAIL);
					}
				}
			} else {
				$this->errorBox();
			}
		}
		if (!Core_Html::getInstance()->isJavascriptEnabled()) {
			//Core_Html::getInstance()->redirect("index.php?mod=connect&view=account&selectedTab=accounttabsidTab1");
		}
	}
	
	private function &tabAvatar() {
		$form = new Libs_Form("avatar");
		$form->setTitle(ACCOUNT_AVATAR_TITLE);
		$form->setDescription(ACCOUNT_AVATAR_DESCRIPTION);
		$form->addSpace();
		$form->addInputHidden("mod", "connect");
		$form->addInputHidden("view", "account");
		$form->addInputHidden("layout", "module");
		$form->addInputSubmit("submit", "", "value=\"" . VALID . "\"");
		return $form->render();
	}
	
	private function &tabAdmin() {
		$form = new Libs_Form("admin");
		$form->setTitle(ACCOUNT_ADMIN_TITLE);
		$form->setDescription(ACCOUNT_ADMIN_DESCRIPTION);
		
		// Type de compte admin
		$form->addSpace();
		$rights = Core_Acces::getAdminRight();
		$form->addHtmlInFieldset("<b>");
		if (Core_Session::$userRang == 3 && $rights[0] == "all") $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_MAX);
		else if (Core_Session::$userRang == 3) $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_HIG);
		else $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_MED);
		$form->addHtmlInFieldset("</b>");
		
		// Liste des droits
		$form->addSpace();
		$form->addHtmlInFieldset("<u>" . ACCOUNT_ADMIN_RIGHT . ":</u>");
		// Requête pour la liste des blocks
		Core_Sql::select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "title")
		);
		if (Core_Sql::affectedRows() > 0) {
			Core_Sql::addBuffer("blocks");
			$blocks = Core_Sql::getBuffer("blocks");
		}
		$zone = $identifiant = "";
		foreach($rights as $key => $right) {
			if ($right == "all") {
				$form->addHtmlInFieldset(ADMIN_RIGHT_ALL);
			} else if (Core_Acces::accessType($right, $zone, $identifiant)) {
				if ($zone == "MODULE") {
					$form->addHtmlInFieldset(ADMIN_RIGHT_MODULE . " <b>" . $right . "</b> (#" . $identifiant . ")");
				} else if ($zone == "BLOCK") {
					foreach($blocks as $block) {
						if ($block->block_id == $identifiant) {
							$right = "<b>" . $block->title . "</b> (#" . $identifiant . ")";
							break;
						}
					}
					$form->addHtmlInFieldset(ADMIN_RIGHT_BLOCK . " " . $right);
				} else if ($zone == "PAGE") {
					$form->addHtmlInFieldset(ADMIN_RIGHT_PAGE . " <b>" . $identifiant . "</b>");
				}
			}
		}
		$form->addInputHidden("mod", "connect");
		$form->addInputHidden("view", "account");
		$form->addInputHidden("layout", "module");
		return $form->render();
	}
	
	/**
	 * Formulaire de connexion
	 */
	public function logon() {
		if (!Core_Session::getInstance()->isUser()) {
			$login = Core_Request::getString("login", "", "POST");
			$password = Core_Request::getString("password", "", "POST");
			$auto = (Core_Request::getWord("auto", "", "POST") == "on") ? true : false;
			
			if (!empty($login) || !empty($password)) {
				if (Core_Session::getInstance()->startConnection($login, $password, $auto)) {
					$url = (!empty($this->configs['defaultUrlAfterLogon'])) ? $this->configs['defaultUrlAfterLogon'] : "home";
					Core_Html::getInstance()->redirect("index.php?mod=" . $url);
				} else {
					$this->errorBox();
				}
			}
			
			if (!Core_Html::getInstance()->isJavascriptEnabled() || (empty($login) && empty($password))) {
				Core_Loader::classLoader("Libs_Form");
				$form = new Libs_Form("logon");
				$form->setTitle(LOGIN_FORM_TITLE);
				$form->setDescription(LOGIN_FORM_DESCRIPTION);
				$form->addInputText("login", LOGIN, "", "maxlength=\"180\" value=\"" . $login . "\"");
				$form->addInputPassword("password", PASSWORD, "", "maxlength=\"180\"");
				$form->addInputCheckbox("auto", REMEMBER_ME, true);
				$form->addInputHidden("referer", urlencode(base64_encode(Core_Request::getString("QUERY_STRING", "", "SERVER"))));
				$form->addInputHidden("mod", "connect");
				$form->addInputHidden("view", "logon");
				$form->addInputHidden("layout", "module");
				$form->addInputSubmit("submit", "", "value=\"" . CONNECT . "\"");
				$form->addHtmlInFieldset($moreLink);
				echo $form->render();
				Core_Html::getInstance()->addJavascript("validLogon('#form-logon', '#form-logon-login-input', '#form-logon-password-input');");
			}
		} else {
			$this->display();
		}
	}
	
	/**
	 * Envoie des messages d'erreurs
	 */
	private function errorBox() {
		$errorMessages = Core_Session::getInstance()->getErrorMessage();
		foreach($errorMessages as $errorMessage) {
			Core_Exception::addNoteError($errorMessage);
		}
	}
	
	/**
	 * Déconnexion du client
	 */
	public function logout() {
		Core_Session::getInstance()->stopConnection();
		Core_Html::getInstance()->redirect();
	}
	
	/**
	 * Formulaire d'identifiant oublié
	 */
	public function forgetlogin() {
		if (!Core_Session::getInstance()->isUser()) {
			$login = "";
			$ok = false;
			$mail = Core_Request::getString("mail", "", "POST");
			
			if (!empty($mail)) {
				Core_Loader::classLoader("Exec_Mailer");
				if (Exec_Mailer::validMail($mail)) {
					Core_Sql::select(
						Core_Table::$USERS_TABLE,
						array("name"),
						array("mail = '" . $mail . "'")
					);
					
					if (Core_Sql::affectedRows() == 1) {
						list($login) = Core_Sql::fetchArray();
						$ok = Exec_Mailer::sendMail();
					}
					if (!$ok) Core_Exception::addNoteError(FORGET_LOGIN_INVALID_MAIL_ACCOUNT);
				} else {
					Core_Exception::addNoteError(INVALID_MAIL);
				}
			}
			
			if ($ok) {
				Core_Exception::addInfoError(FORGET_LOGIN_IS_SUBMIT_TO . " " . $mail);
			} else {
				if (!Core_Html::getInstance()->isJavascriptEnabled() || empty($mail)) {
					Core_Loader::classLoader("Libs_Form");
					$form = new Libs_Form("forgetlogin");
					$form->setTitle(FORGET_LOGIN_TITLE);
					$form->setDescription(FORGET_LOGIN_DESCRIPTION);
					$form->addInputText("mail", MAIL);
					$form->addInputHidden("mod", "connect");
					$form->addInputHidden("view", "forgetlogin");
					$form->addInputHidden("layout", "module");
					$form->addInputSubmit("submit", "", "value=\"" . FORGET_LOGIN_SUBMIT . "\"");
					echo $form->render();
					Core_Html::getInstance()->addJavascript("validForgetLogin('#form-forgetlogin', '#form-forgetlogin-mail-input');");
				}
			}
		} else {
			$this->display();
		}
	}

	/**
	 * Formulaire de mot de passe oublié
	 */
	public function forgetpass() {
		if (!Core_Session::getInstance()->isUser()) {
			$ok = false;
			$mail = "";
			$login = Core_Request::getString("login", "", "POST");
			
			if (!empty($login)) {
				if (Core_Session::getInstance()->validLogin($login)) {
					Core_Sql::select(
						Core_Table::$USERS_TABLE,
						array("name, mail"),
						array("name = '" . $login . "'")
					);
					
					if (Core_Sql::affectedRows() == 1) {
						list($name, $mail) = Core_Sql::fetchArray();
						if ($name == $login) {
							// Ajouter générateur d'id
							$ok = Exec_Mailer::sendMail();
						}
					}
					if (!$ok) Core_Exception::addNoteError(FORGET_PASSWORD_INVALID_LOGIN_ACCOUNT);
				} else {
					$this->errorBox();
				}
			}
			
			if ($ok) {
				Core_Exception::addInfoError(FORGET_PASSWORD_IS_SUBMIT_TO . " " . $mail);
			} else {
				if (!Core_Html::getInstance()->isJavascriptEnabled() || empty($login)) {
					Core_Loader::classLoader("Libs_Form");
					$form = new Libs_Form("forgetpass");
					$form->setTitle(FORGET_PASSWORD_TITLE);
					$form->setDescription(FORGET_PASSWORD_DESCRIPTION);
					$form->addInputText("login", LOGIN);
					$form->addInputHidden("mod", "connect");
					$form->addInputHidden("view", "forgetpass");
					$form->addInputHidden("layout", "module");
					$form->addInputSubmit("submit", "", "value=\"" . FORGET_PASSWORD_SUBMIT . "\"");
					echo $form->render();
					Core_Html::getInstance()->addJavascript("validForgetPass('#form-forgetpass', '#form-forgetpass-login-input');");
				}
			}
		} else {
			$this->display();
		}
	}
	
	public function registration() {
		
	}
}

?>