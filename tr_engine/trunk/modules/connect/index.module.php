<?php

class Module_Connect_Index extends Module_Model {
	
	/**
	 * Message d'erreur
	 * 
	 * @var array
	 */
	private $errorMessage = array();
	
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
			$pass = Core_Request::getString("pass", "", "POST");
			$auto = (Core_Request::getInt("pass", "", "POST") == 1) ? true : false;
			
			if ($this->validLogin($login) && $this->validPassword($pass)) {
				if (Core_Session::startConnection($login, $pass, $auto)) {
					$url = (!empty($this->configs['defaultUrlAfterLogon'])) ? $this->configs['defaultUrlAfterLogon'] : "index.php";
					Core_Html::getInstance()->redirect($url);
				} else {
					$this->errorMessage[] = ERROR_LOGIN_OR_PASSWORD_INVALID;
					$this->errorBox();
				}
			} else {
				$this->errorBox();
			}
		} else {
			$this->display();
		}
	}
	
	/**
	 * Vérification du login
	 * 
	 * @param $login
	 * @return boolean true login valide
	 */
	private function validLogin($login) {
		if (!empty($login)) {
			if (strlen($login) >= 3) {
				if (!preg_match("/^[A-Za-z0-9_-]/ie", $login)) {
					return true;
				} else {
					$this->errorMessage[] = ERROR_LOGIN_CARACTERE;
				}
			} else {
				$this->errorMessage[] = ERROR_LOGIN_NUMBER_CARACTERE;
			}
		} else {
			$this->errorMessage[] = ERROR_LOGIN_EMPTY;
		}
		return false;
	}
	
	/**
	 * Vérification du password
	 * 
	 * @param $password
	 * @return boolean true password valide
	 */
	private function validPassword($password) {
		if (!empty($password)) {
			if (strlen($login) >= 5) {
				return true;
			} else {
				$this->errorMessage[] = ERROR_PASSWORD_NUMBER_CARACTERE;
			}
		} else {
			$this->errorMessage[] = ERROR_PASSWORD_EMPTY;
		}
		return false;
	}
	
	private function errorBox() {
		
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