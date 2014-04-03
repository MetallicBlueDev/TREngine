<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../engine/core/secure.class.php");
    new Core_Secure();
}

/**
 * Block login, acc�s rapide a une connexion, a une d�connexion et a son compte.
 *
 * @author Sébastien Villemain
 */
class Block_Login extends Libs_BlockModel {

    /**
     * Affiche le texte de bienvenue.
     *
     * @var boolean
     */
    private $displayText = false;

    /**
     * Affiche l'avatar.
     *
     * @var boolean
     */
    private $displayAvatar = false;

    /**
     * Affiche les icons rapides.
     *
     * @var boolean
     */
    private $displayIcons = false;

    /**
     * Type d'affichage demand�.
     *
     * @var string
     */
    private $localView = "";

    public function display() {
        $this->configure();

        if (!empty($this->localView)) {
            echo $this->render();
        } else {
            $libsMakeStyle = new Libs_MakeStyle();
            $libsMakeStyle->assign("blockTitle", $this->title);
            $libsMakeStyle->assign("blockContent", $this->render());
            $libsMakeStyle->display($this->templateName);
        }
    }

    private function configure() {
        list($activeText, $activeAvatar, $activeIcons) = explode('|', $this->content);
        $this->displayText = ($activeText == 1) ? true : false;
        $this->displayAvatar = ($activeAvatar == 1) ? true : false;
        $this->displayIcons = ($activeIcons == 1) ? true : false;

        if (Core_Main::isBlockScreen()) { // Si nous sommes dans un affichage type block
            $this->localView = Core_Request::getString("localView", "", "GET");
        }
    }

    private function &render() {
        $content = "";
        if (Core_Session::getInstance()->isUser()) {
            if ($this->displayText) {
                $content .= WELCOME . " <b>" . Core_Session::$userName . "</b> !<br />";
            }
            if ($this->displayAvatar && !empty(Core_Session::$userAvatar)) {
                Core_Loader::classLoader("Exec_Image");
                $content .= "<a href=\"" . Core_Html::getLink("mod=connect&view=account") . "\">" . Exec_Image::resize(Core_Session::$userAvatar, 80) . "</a><br />";
            }
            if ($this->displayIcons) {
                $content .= "<a href=\"" . Core_Html::getLink("mod=connect&view=logout") . "\" title=\"" . LOGOUT . "\">" . LOGOUT . "</a><br />"
                        . "<a href=\"" . Core_Html::getLink("mod=connect&view=account") . "\" title=\"" . MY_ACCOUNT . "\">" . MY_ACCOUNT . "</a><br />"
                        . "<a href=\"" . Core_Html::getLink("mod=receiptbox") . "\" title=\"" . MY_RECEIPTBOX . "\">" . MY_RECEIPTBOX . " (?)</a><br />";
            }
        } else {
            $moreLink = "<ul>";
            if (Core_Main::isRegistrationAllowed()) {
                $moreLink .= "<li><b>" . Core_Html::getLinkForBlock("mod=connect&view=registration", "blockId=" . $this->blockId . "&localView=registration", "#login-logonblock", GET_ACCOUNT) . "</b></li>";
            }
            $moreLink .= "<li>" . Core_Html::getLinkForBlock("mod=connect&view=logon", "blockId=" . $this->blockId . "&localView=logon", "#login-logonblock", GET_LOGON) . "</li>"
                    . "<li>" . Core_Html::getLinkForBlock("mod=connect&view=forgetlogin", "blockId=" . $this->blockId . "&localView=forgetlogin", "#login-logonblock", GET_FORGET_LOGIN) . "</li>"
                    . "<li>" . Core_Html::getLinkForBlock("mod=connect&view=forgetpass", "blockId=" . $this->blockId . "&localView=forgetpass", "#login-logonblock", GET_FORGET_PASS) . "</li></ul>";

            $content .= "<div id=\"login-logonblock\">";

            Core_Loader::classLoader("Libs_Form");
            switch ($this->localView) {
                case 'logon':
                    $content .= $this->logon($moreLink);
                    break;
                case 'forgetlogin':
                    $content .= $this->forgetlogin($moreLink);
                    break;
                case 'forgetpass':
                    $content .= $this->forgetpass($moreLink);
                    break;
                case 'registration':
                    $content .= $this->registration($moreLink);
                    break;
                default:
                    $content .= $this->logon($moreLink);
                    break;
            }
            $content .= "</div>";
        }
        return $content;
    }

    /**
     * Formulaire de connexion.
     *
     * @param $moreLink string
     * @return string
     */
    private function &logon($moreLink) {
        $form = new Libs_Form("login-logonblock");
        $form->addInputText("login", LOGIN, "", "maxlength=\"180\"");
        $form->addInputPassword("password", PASSWORD, "maxlength=\"180\"");
        $form->addInputHidden("referer", urlencode(base64_encode(Core_Request::getString("QUERY_STRING", "", "SERVER"))));
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "logon");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", GET_LOGON);
        $form->addHtmlInFieldset($moreLink);
        Core_Html::getInstance()->addJavascript("validLogon('#form-login-logonblock', '#form-login-logonblock-login-input', '#form-login-logonblock-password-input');");
        return $form->render("login-logonblock");
    }

    /**
     * Formulaire de login oubli�.
     *
     * @param $moreLink string
     * @return string
     */
    private function &forgetlogin($moreLink) {
        $form = new Libs_Form("login-forgetloginblock");
        $form->addInputText("mail", MAIL . " ");
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "forgetlogin");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        $form->addHtmlInFieldset($moreLink);
        Core_Html::getInstance()->addJavascript("validForgetLogin('#form-login-forgetloginblock', '#form-login-forgetloginblock-mail-input');");
        return $form->render("login-forgetloginblock");
    }

    /**
     * Formulaire de mot de passe oubli�.
     *
     * @param $moreLink string
     * @return string
     */
    private function &forgetpass($moreLink) {
        $form = new Libs_Form("login-forgetpassblock");
        $form->addInputText("login", LOGIN . " ");
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "forgetpass");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        $form->addHtmlInFieldset($moreLink);
        Core_Html::getInstance()->addJavascript("validForgetPass('#form-login-forgetpassblock', '#form-login-forgetpassblock-login-input');");
        return $form->render("login-forgetpassblock");
    }

    private function &registration($moreLink) { // TODO registration block a coder
        $form = new Libs_Form("login-registrationblock");
        $form->addInputText("login", LOGIN . " ");
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "registration");
        //$form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        $form->addHtmlInFieldset($moreLink);
        //Core_Html::getInstance()->addJavascript("validForgetPass('#form-login-registrationblock', '#form-login-registrationblock-login-input');");
        return $form->render("login-registrationblock");
    }

    public function install() {

    }

    public function uninstall() {
        Core_CacheBuffer::setSectionName("form");
        Core_CacheBuffer::removeCache("login-logonblock.php");
        Core_CacheBuffer::removeCache("login-forgetloginblock.php");
        Core_CacheBuffer::removeCache("login-forgetpassblock.php");
        Core_CacheBuffer::removeCache("login-registrationblock.php");
    }

}

?>