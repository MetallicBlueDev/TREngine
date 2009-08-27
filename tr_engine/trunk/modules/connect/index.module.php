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
	
	public function logon() {
		if (!Core_Session::isUser()) {
			$login = Core_Request::getString("login", "", "POST");
			$password = Core_Request::getString("password", "", "POST");
			$auto = (Core_Request::getWord("auto", "", "POST") == "on") ? true : false;
			
			if (Core_Session::getInstance()->startConnection($login, $password, $auto)) {
				$url = (!empty($this->configs['defaultUrlAfterLogon'])) ? $this->configs['defaultUrlAfterLogon'] : "index.php";
				Core_Html::getInstance()->redirect($url);
			} else {
				$this->errorBox();
			}
			
			if (!Core_Html::getInstance()->isJavaScriptActived() || (empty($login) && empty($password))) {
				Core_Loader::classLoader("Libs_Form");
				$form = new Libs_Form("logon");
				$form->addInputText("login", LOGIN . " ", "", "maxlength=\"180\" value=\"" . $login . "\"");
				$form->addInputPassword("password", PASSWORD . " ", "", "maxlength=\"180\"");
				$form->addInputCheckbox("auto", REMEMBER_ME, true);
				$form->addInputHidden("referer", urlencode(base64_encode(Core_Request::getString("QUERY_STRING", "", "SERVER"))));
				$form->addInputHidden("mod", "connect");
				$form->addInputHidden("view", "logon");
				$form->addInputHidden("layout", "module");
				$form->addInputSubmit("submit", "", "value=\"" . LOGIN_SUBMIT . "\"");
				$form->addHtmlInFieldset($moreLink);
				echo $form->render();
				Core_Html::getInstance()->addJavaScriptJquery(Core_Session::getJavascriptLogon("form-logon", "form-logon-login-input", "form-logon-password-input"));
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
	
	public function forgetlogin() {
		
	}
	
	public function forgetpass() {
		
	}
	
	public function registration() {
		
	}
}

?>