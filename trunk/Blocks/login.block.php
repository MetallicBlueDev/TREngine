<?php

namespace TREngine\Blocks;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block login, acc�s rapide a une connexion, a une d�connexion et a son compte.
 *
 * @author Sébastien Villemain
 */
class Block_Login extends BlockModel {

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
            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
            $libMakeStyle->assign("blockContent", $this->render());
            $libMakeStyle->display($this->getBlockData()->getTemplateName());
        }
    }

    private function configure() {
        list($activeText, $activeAvatar, $activeIcons) = explode('|', $this->getBlockData()->getContent());
        $this->displayText = ($activeText == 1) ? true : false;
        $this->displayAvatar = ($activeAvatar == 1) ? true : false;
        $this->displayIcons = ($activeIcons == 1) ? true : false;

        if (CoreMain::getInstance()->isBlockLayout()) { // Si nous sommes dans un affichage type block
            $this->localView = CoreRequest::getString("localView", "", "GET");
        }
    }

    private function &render() {
        $content = "";
        if (CoreSession::hasConnection()) {
            $userInfos = CoreSession::getInstance()->getUserInfos();

            if ($this->displayText) {
                $content .= WELCOME . " <b>" . $userInfos->getName() . "</b> !<br />";
            }
            if ($this->displayAvatar && !empty($userInfos->getAvatar())) {
                $content .= CoreHtml::getLink("mod=connect&view=account", ExecImage::resize($userInfos->getAvatar(), 80)) . "<br />";
            }
            if ($this->displayIcons) {
                $content .= CoreHtml::getLink("mod=connect&view=logout", LOGOUT) . "<br />"
                . CoreHtml::getLink("mod=connect&view=account", MY_ACCOUNT) . "<br />"
                . CoreHtml::getLink("mod=receiptbox", MY_RECEIPTBOX . " (?)") . "<br />";
            }
        } else {
            $moreLink = "<ul>";
            if (CoreMain::getInstance()->registrationAllowed()) {
                $moreLink .= "<li><b>" . CoreHtml::getLinkWithAjax("mod=connect&view=registration", "blockId=" . $this->getBlockData()->getId() . "&localView=registration", "#login-logonblock", GET_ACCOUNT) . "</b></li>";
            }
            $moreLink .= "<li>" . CoreHtml::getLinkWithAjax("mod=connect&view=logon", "blockId=" . $this->getBlockData()->getId() . "&localView=logon", "#login-logonblock", GET_LOGON) . "</li>"
            . "<li>" . CoreHtml::getLinkWithAjax("mod=connect&view=forgetlogin", "blockId=" . $this->getBlockData()->getId() . "&localView=forgetlogin", "#login-logonblock", GET_FORGET_LOGIN) . "</li>"
            . "<li>" . CoreHtml::getLinkWithAjax("mod=connect&view=forgetpass", "blockId=" . $this->getBlockData()->getId() . "&localView=forgetpass", "#login-logonblock", GET_FORGET_PASS) . "</li></ul>";

            $content .= "<div id=\"login-logonblock\">";

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
        $form = new LibForm("login-logonblock");
        $form->addInputText("login", LOGIN, "", "maxlength=\"180\"");
        $form->addInputPassword("password", PASSWORD, "maxlength=\"180\"");
        $form->addInputHidden("referer", urlencode(base64_encode(CoreRequest::getString("QUERY_STRING", "", "SERVER"))));
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "logon");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", GET_LOGON);
        $form->addHtmlInFieldset($moreLink);
        CoreHtml::getInstance()->addJavascript("validLogon('#form-login-logonblock', '#form-login-logonblock-login-input', '#form-login-logonblock-password-input');");
        return $form->render("login-logonblock");
    }

    /**
     * Formulaire de login oubli�.
     *
     * @param $moreLink string
     * @return string
     */
    private function &forgetlogin($moreLink) {
        $form = new LibForm("login-forgetloginblock");
        $form->addInputText("mail", MAIL . " ");
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "forgetlogin");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        $form->addHtmlInFieldset($moreLink);
        CoreHtml::getInstance()->addJavascript("validForgetLogin('#form-login-forgetloginblock', '#form-login-forgetloginblock-mail-input');");
        return $form->render("login-forgetloginblock");
    }

    /**
     * Formulaire de mot de passe oubli�.
     *
     * @param $moreLink string
     * @return string
     */
    private function &forgetpass($moreLink) {
        $form = new LibForm("login-forgetpassblock");
        $form->addInputText("login", LOGIN . " ");
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "forgetpass");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        $form->addHtmlInFieldset($moreLink);
        CoreHtml::getInstance()->addJavascript("validForgetPass('#form-login-forgetpassblock', '#form-login-forgetpassblock-login-input');");
        return $form->render("login-forgetpassblock");
    }

    private function &registration($moreLink) { // TODO registration block a coder
        $form = new LibForm("login-registrationblock");
        $form->addInputText("login", LOGIN . " ");
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "registration");
        //$form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        $form->addHtmlInFieldset($moreLink);
        //CoreHtml::getInstance()->addJavascript("validForgetPass('#form-login-registrationblock', '#form-login-registrationblock-login-input');");
        return $form->render("login-registrationblock");
    }

    public function install() {

    }

    public function uninstall() {
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_FORMS);
        $coreCache->removeCache("login-logonblock.php");
        $coreCache->removeCache("login-forgetloginblock.php");
        $coreCache->removeCache("login-forgetpassblock.php");
        $coreCache->removeCache("login-registrationblock.php");
    }

}

?>