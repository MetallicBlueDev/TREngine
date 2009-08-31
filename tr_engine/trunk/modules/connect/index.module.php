<?php

class Module_Connect_Index extends Module_Model {
	
	public function display() {
		if (Core_Session::isUser()) {
			$this->account();
		} else {
			$this->logon();
		}
	}
	
	public function account() {
		if (Core_Session::isUser()) {
			
		} else {
			$this->display();
		}
	}
	
	/**
	 * Formulaire de connexion
	 */
	public function logon() {
		if (!Core_Session::isUser()) {
			$login = Core_Request::getString("login", "", "POST");
			$password = Core_Request::getString("password", "", "POST");
			$auto = (Core_Request::getWord("auto", "", "POST") == "on") ? true : false;
			
			if (!empty($login) || !empty($password)) {			
				if (Core_Session::getInstance()->startConnection($login, $password, $auto)) {
					$url = (!empty($this->configs['defaultUrlAfterLogon'])) ? $this->configs['defaultUrlAfterLogon'] : "index.php";
					Core_Html::getInstance()->redirect($url);
				} else {
					$this->errorBox();
				}
			}
			
			if (!Core_Html::getInstance()->isJavaScriptActived() || (empty($login) && empty($password))) {
				Core_Loader::classLoader("Libs_Form");
				$form = new Libs_Form("logon");
				$form->setTitle(LOGIN_FORM_TITLE);
				$form->setDescription(LOGIN_FORM_DESCRIPTION);
				$form->addInputText("login", LOGIN . " ", "", "maxlength=\"180\" value=\"" . $login . "\"");
				$form->addInputPassword("password", PASSWORD . " ", "", "maxlength=\"180\"");
				$form->addInputCheckbox("auto", REMEMBER_ME, true);
				$form->addInputHidden("referer", urlencode(base64_encode(Core_Request::getString("QUERY_STRING", "", "SERVER"))));
				$form->addInputHidden("mod", "connect");
				$form->addInputHidden("view", "logon");
				$form->addInputHidden("layout", "module");
				$form->addInputSubmit("submit", "", "value=\"" . CONNECT . "\"");
				$form->addHtmlInFieldset($moreLink);
				echo $form->render();
				Core_Html::getInstance()->addJavaScriptJquery("validLogin('#form-logon', '#form-logon-login-input', '#form-logon-password-input');");
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
		Core_Html::getInstance()->redirect("", 3);
	}
	
	/**
	 * Formulaire d'identifiant oublié
	 */
	public function forgetlogin() {
		if (!Core_Session::isUser()) {
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
					$this->errorBox();
				}
			}
			
			if ($ok) {
				Core_Exception::addInfoError(FORGET_LOGIN_IS_SUBMIT_TO . " " . $mail);
			} else {
				if (!Core_Html::getInstance()->isJavaScriptActived() || empty($mail)) {
					Core_Loader::classLoader("Libs_Form");
					$form = new Libs_Form("forgetlogin");
					$form->setTitle(FORGET_LOGIN_TITLE);
					$form->setDescription(FORGET_LOGIN_DESCRIPTION);
					$form->addInputText("mail", MAIL . " ");
					$form->addInputHidden("mod", "connect");
					$form->addInputHidden("view", "forgetlogin");
					$form->addInputHidden("layout", "module");
					$form->addInputSubmit("submit", "", "value=\"" . FORGET_LOGIN_SUBMIT . "\"");
					echo $form->render();
					Core_Html::getInstance()->addJavaScriptJquery("validMail('#form-forgetlogin', '#form-forgetlogin-mail-input');");
				}
			}
		} else {
			$this->display();
		}
	}
	
	public function forgetpass() {
		if (!Core_Session::isUser()) {
			$ok = false;
			$mail = "";
			$login = Core_Request::getString("login", "", "POST");
			
			if (!empty($login)) {
				if (Core_Session::validLogin($login)) {
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
				if (!Core_Html::getInstance()->isJavaScriptActived() || empty($login)) {
					Core_Loader::classLoader("Libs_Form");
					$form = new Libs_Form("forgetpass");
					$form->setTitle(FORGET_PASSWORD_TITLE);
					$form->setDescription(FORGET_PASSWORD_DESCRIPTION);
					$form->addInputText("login", LOGIN . " ");
					$form->addInputHidden("mod", "connect");
					$form->addInputHidden("view", "forgetpass");
					$form->addInputHidden("layout", "module");
					$form->addInputSubmit("submit", "", "value=\"" . FORGET_PASSWORD_SUBMIT . "\"");
					echo $form->render();
					Core_Html::getInstance()->addJavaScriptJquery("validMail('#form-forgetpass', '#form-forgetpass-login-input');");
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