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
		} else {
			$this->display();
		}
	}
	
	private function errorBox() {
		//echo $this->errorMessage[0];
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