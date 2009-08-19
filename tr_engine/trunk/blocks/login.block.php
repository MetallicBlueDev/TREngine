<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../engine/core/secure.class.php");
	new Core_Secure();
}

/**
 * Block login, accès rapide a une connexion, a une déconnexion et a son compte
 * 
 * @author Sebastien Villemain
 *
 */
class Block_Login extends Block_Model {
	
	/**
	 * Affiche le texte de bienvenue
	 * 
	 * @var boolean
	 */
	private $displayText = false;
	
	/**
	 * Affiche l'avatar
	 * 
	 * @var boolean
	 */
	private $displayAvatar = false;
	
	/**
	 * Affiche les icons rapides
	 * 
	 * @var boolean
	 */
	private $displayIcons = false;
	
	public function configure() {
		list($activeText, $activeAvatar, $activeIcons) = explode('|', $this->content);
		$this->displayText = ($activeText == 1) ? true : false;
		$this->displayAvatar = ($activeAvatar == 1) ? true : false;
		$this->displayIcons = ($activeIcons == 1) ? true : false;
	}
	
	public function render() {
		$content = "";
		if (Core_Session::isUser()) {
			if ($this->displayText) {
				$content .= "<b>" . WELCOME . " <b>" . Core_Session::$userName . "</b> !<br />";
			}
			if ($this->displayAvatar) {
				Core_Loader::classLoader("Exec_Image");
				$content .= "<a href=\"" . Core_Html::getLink("mod=connect&view=account") . "\">" . Exec_Image::resize(Core_Session::$userAvatar, 80) . "</a><br />";
			}
			if ($this->displayIcons) {
				$content .= "<a href=\"" . Core_Html::getLink("mod=connect&view=logout") . "\" title=\"" . LOGOUT . "\">" . LOGOUT . "</a><br />";
				$content .= "<a href=\"" . Core_Html::getLink("mod=connect&view=account") . "\" title=\"" . MY_ACCOUNT . "\">" . MY_ACCOUNT. "</a><br />";
				$content .= "<a href=\"" . Core_Html::getLink("mod=receiptbox") . "\" title=\"" . MY_RECEIPTBOX . "\">" . MY_RECEIPTBOX . " (?)</a><br />";
			}
		} else {
			$moreLink = "<ul><li><a href=\"" . Core_Html::getLink("mod=connect&view=forgetlogin") . "\">" . FORGET_LOGIN . "</a></li>"
			. "<li><a href=\"" . Core_Html::getLink("mod=connect&view=forgetpass") . "\">" . FORGET_PASS . "</a></li>";
			if (Core_Main::isRegistrationAllowed()) {
				$moreLink .= "<li><a href=\"" . Core_Html::getLink("mod=connect&view=registration") . "\"><b>" . BECOME_MEMBER . "</b></a></li>";
			}
			$moreLink .= "</ul>";
			
			if (!Core_Loader::isCallable("Libs_Module") || Libs_Module::$view != "logon") {
				Core_Loader::classLoader("Libs_Form");
				$form = new Libs_Form("logon", Core_Html::getLink("mod=connect&view=logon", true));
				$form->addInputText("login", LOGIN, "", "maxlength=\"180\"");
				$form->addInputText("pass", PASSWORD, "", "maxlength=\"180\"");
				$form->addInputHidden("referer", "value=\"" . urlencode(base64_encode(Core_Request::getString("QUERY_STRING", "", "SERVER"))) . "\"");
				$form->addInputSubmit("submit", "", "value=\"" . LOGIN_SUBMIT . "\"");
				$form->addHtmlInFieldset($moreLink);
				$content .= $form->render();
			} else {
				$content .= LOGIN_PLEASE . $moreLink;
			}
		}
		return $content;
	}
	
	public function display() {
		$this->configure();
		
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("blockTitle", $this->title);
		$libsMakeStyle->assign("blockContent", $this->render());
		$libsMakeStyle->display($this->templateName);
	}
}


?>