<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Module d'interface et de traitement entre le site et le client
 *
 * @author Sébastien Villemain
 */
class Module_Connect_Index extends Module_Model {

    public function display() {
        if (Core_Session::hasConnection()) {
            $this->account();
        } else {
            $this->logon();
        }
    }

    public function account() {
        if (Core_Session::hasConnection()) {
            // Ajout des formulaires dans les onglets
            $accountTabs = new Libs_Tabs("accounttabs");
            $accountTabs->addTab(ACCOUNT_PROFILE, $this->tabProfile());
            $accountTabs->addTab(ACCOUNT_PRIVATE, $this->tabAccount());
            $accountTabs->addTab(ACCOUNT_AVATAR, $this->tabAvatar());
            if (Core_Session::getInstance()->getUserInfos()->hasAdminRank()) {
                $accountTabs->addTab(ACCOUNT_ADMIN, $this->tabAdmin());
            }
            echo $accountTabs->render();
        } else {
            $this->display();
        }
    }

    private function tabProfile() {
        $form = new Libs_Form("account-profile");
        $form->setTitle(ACCOUNT_PROFILE_TITLE);
        $form->setDescription(ACCOUNT_PROFILE_DESCRIPTION);
        $form->addSpace();
        $form->addInputText("website", ACCOUNT_PROFILE_WEBSITE);
        $form->addTextarea("signature", ACCOUNT_PROFILE_SIGNATURE, Core_Session::getInstance()->getUserInfos()->getSignature(), "style=\"display: block;\" rows=\"5\" cols=\"50\"");
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "sendProfile");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        return $form->render();
    }

    public function sendProfile() {
        $values = array();
        $website = Core_Request::getString("website", "", "POST");
        $signature = Core_Request::getString("signature", "", "POST");

        if (!empty($website)) {
            $values['website'] = Exec_Url::cleanUrl($website);
        }
        if (!empty($signature)) {
            $values['signature'] = $signature;
        }

        if (!empty($values)) {
            $coreSql = Core_Sql::getInstance();
            $coreSession = Core_Session::getInstance();

            $coreSql->update(
            Core_Table::USERS_TABLE, $values, array(
                "user_id = '" . $coreSession->getUserInfos()->getId() . "'")
            );

            if ($coreSql->affectedRows() > 0) {
                $coreSession->refreshSession();
                Core_Logger::addInformationMessage(DATA_SAVED);
            }
        }
        if (Core_Main::getInstance()->isDefaultLayout()) {
            Core_Html::getInstance()->redirect("index.php?mod=connect&view=account&selectedTab=accounttabsidTab0", 1);
        }
    }

    private function tabAccount() {
        $userInfos = Core_Session::getInstance()->getUserInfos();

        $form = new Libs_Form("account-accountprivate");
        $form->setTitle(ACCOUNT_PRIVATE_TITLE);
        $form->setDescription(ACCOUNT_PRIVATE_DESCRIPTION);
        $form->addSpace();
        $form->addInputText("name", LOGIN, $userInfos->getName());
        $form->addInputPassword("pass", PASSWORD);
        $form->addInputPassword("pass2", ACCOUNT_PRIVATE_PASSWORD_CONFIRME);
        $form->addInputText("mail", MAIL, $userInfos->getMail());

        // Liste des langages disponibles
        $form->addSpace();
        $form->addSelectOpenTag("langue", ACCOUNT_PRIVATE_LANGUE);
        $langues = Core_Translate::getLangList();
        $currentLanguage = Core_Translate::getInstance()->getCurrentLanguage();
        $form->addSelectItemTag($currentLanguage, "", true);
        foreach ($langues as $langue) {
            if ($langue == $currentLanguage)
                continue;
            $form->addSelectItemTag($langue);
        }
        $form->addSelectCloseTag();

        // Liste des templates disponibles
        if (CoreLoader::isCallable("Libs_MakeStyle")) {
            $form->addSelectOpenTag("template", ACCOUNT_PRIVATE_TEMPLATE);
            $templates = Libs_MakeStyle::getTemplateList();
            $currentTemplate = Libs_MakeStyle::getCurrentTemplate();
            $form->addSelectItemTag($currentTemplate, "", true);
            foreach ($templates as $template) {
                if ($template == $currentTemplate)
                    continue;
                $form->addSelectItemTag($template);
            }
            $form->addSelectCloseTag();
        }
        $form->addSpace();
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "sendAccount");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        Core_Html::getInstance()->addJavascript("validAccount('#form-account-accountprivate', '#form-account-accountprivate-name-input', '#form-account-accountprivate-pass-input', '#form-account-accountprivate-pass2-input', '#form-account-accountprivate-mail-input');");
        return $form->render();
    }

    public function sendAccount() {
        $userInfos = Core_Session::getInstance()->getUserInfos();

        $name = Core_Request::getWord("name", "", "POST");
        $pass = Core_Request::getString("pass", "", "POST");
        $pass2 = Core_Request::getString("pass2", "", "POST");
        $mail = Core_Request::getString("mail", "", "POST");
        $langue = Core_Request::getString("langue", "", "POST");
        $template = Core_Request::getString("template", "", "POST");

        if ($userInfos->getName() != $name || $userInfos->getMail() != $mail || $userInfos->getLangue() != $langue || $userInfos->getTemplate() != $template) {
            if (Core_Session::validLogin($name)) {
                $validName = true;

                if ($userInfos->getName() != $name) {
                    $name = Exec_Entities::secureText($name);

                    Core_Sql::getInstance()->select(
                    Core_Table::USERS_TABLE, array(
                        "user_id"), array(
                        "name = '" . $name . "'")
                    );

                    if (Core_Sql::getInstance()->affectedRows() > 0) {
                        $validName = false;
                        Core_Logger::addWarningMessage(ACCOUNT_PRIVATE_LOGIN_IS_ALLOWED);
                    }
                }
                if ($validName) {
                    if (Exec_Mailer::validMail($mail)) {
                        $values = array();
                        if (!empty($pass) || !empty($pass2)) {
                            if ($pass == $pass2) {
                                if (Core_Session::validPassword($pass)) {
                                    $values['pass'] = Core_Session::cryptPass($pass);
                                } else {
                                    $this->errorBox();
                                }
                            } else {
                                Core_Logger::addWarningMessage(ACCOUNT_PRIVATE_PASSWORD_INVALID_CONFIRME);
                            }
                        }
                        $values['name'] = $name;
                        $values['mail'] = $mail;
                        $values['langue'] = $langue;
                        $values['template'] = $template;
                        Core_Sql::getInstance()->update(
                        Core_Table::USERS_TABLE, $values, array(
                            "user_id = '" . $userInfos->getId() . "'")
                        );

                        if (Core_Sql::getInstance()->affectedRows() > 0) {
                            Core_Session::getInstance()->refreshSession();
                            Core_Logger::addInformationMessage(DATA_SAVED);
                        }
                    } else {
                        Core_Logger::addWarningMessage(INVALID_MAIL);
                    }
                }
            } else {
                $this->errorBox();
            }
        }
        if (Core_Main::getInstance()->isDefaultLayout()) {
            Core_Html::getInstance()->redirect("index.php?mod=connect&view=account&selectedTab=accounttabsidTab1", 1);
        }
    }

    private function tabAvatar() {
        $form = new Libs_Form("account-avatar");
        $form->setTitle(ACCOUNT_AVATAR_TITLE);
        $form->setDescription(ACCOUNT_AVATAR_DESCRIPTION);
        $form->addSpace();
        $form->addInputHidden("mod", "connect");
        $form->addInputHidden("view", "account");
        $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit", VALID);
        return $form->render();
    }

    private function tabAdmin() {
        $userInfos = Core_Session::getInstance()->getUserInfos();

        $form = new Libs_Form("account-admin");
        $form->setTitle(ACCOUNT_ADMIN_TITLE);
        $form->setDescription(ACCOUNT_ADMIN_DESCRIPTION);

        // Type de compte admin
        $form->addSpace();
        $form->addHtmlInFieldset("<b>");

        if ($userInfos->hasSuperAdminRank()) {
            $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_MAX);
        } else if ($userInfos->hasAdminWithRightsRank()) {
            $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_HIG);
        } else {
            $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_MED);
        }

        $form->addHtmlInFieldset("</b>");

        // Liste des droits
        $form->addSpace();
        $form->addHtmlInFieldset("<u>" . ACCOUNT_ADMIN_RIGHT . ":</u>");

        if ($userInfos->hasSuperAdminRank()) {
            $form->addHtmlInFieldset(ADMIN_RIGHT_ALL);
        } else {
            foreach ($userInfos->getRights() as $userAccessType) {
                if (!$userAccessType->valid()) {
                    continue;
                }

                $text = "";

                if ($userAccessType->isModuleZone()) {
                    $text = ADMIN_RIGHT_MODULE;
                } else if ($userAccessType->isBlockZone()) {
                    $text = ADMIN_RIGHT_BLOCK;
                }

                $form->addHtmlInFieldset($text . " <b>" . $userAccessType->getPage() . "</b> (#" . $userAccessType->getId() . ")");
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
        if (!Core_Session::hasConnection()) {
            $login = Core_Request::getString("login", "", "POST");
            $password = Core_Request::getString("password", "", "POST");

            if (!empty($login) || !empty($password)) {
                if (Core_Session::startConnection($login, $password)) {
                    // Redirection de la page
                    $url = "";
                    $referer = base64_decode(urldecode(Core_Request::getString("referer", "", "POST")));
                    if (!empty($referer)) {
                        $url = $referer;
                    } else {
                        $url = $this->getModuleData()->getConfigValue("defaultUrlAfterLogon", "mod=home");
                    }
                    Core_Html::getInstance()->redirect("index.php?" . $url);
                } else {
                    $this->errorBox();
                }
            }

            if (Core_Main::getInstance()->isDefaultLayout() || (empty($login) && empty($password))) {
                $form = new Libs_Form("login-logon");
                $form->setTitle(LOGIN_FORM_TITLE);
                $form->setDescription(LOGIN_FORM_DESCRIPTION);
                $form->addInputText("login", LOGIN, "", "maxlength=\"180\" value=\"" . $login . "\"");
                $form->addInputPassword("password", PASSWORD, "maxlength=\"180\"");
                $form->addInputHidden("referer", urlencode(base64_encode(Core_Request::getString("QUERY_STRING", "", "SERVER"))));
                $form->addInputHidden("mod", "connect");
                $form->addInputHidden("view", "logon");
                $form->addInputHidden("layout", "module");
                $form->addInputSubmit("submit", CONNECT);
                $form->addHtmlInFieldset($moreLink);
                echo $form->render();
                Core_Html::getInstance()->addJavascript("validLogon('#form-login-logon', '#form-login-logon-login-input', '#form-login-logon-password-input');");
            }
        } else {
            $this->display();
        }
    }

    /**
     * Envoie des messages d'erreurs
     */
    private function errorBox() {
        foreach (Core_Session::getErrorMessage() as $errorMessage) {
            Core_Logger::addWarningMessage($errorMessage);
        }
    }

    /**
     * Déconnexion du client
     */
    public function logout() {
        Core_Session::stopConnection();
        Core_Html::getInstance()->redirect();
    }

    /**
     * Formulaire d'identifiant oublié
     */
    public function forgetlogin() {
        if (!Core_Session::hasConnection()) {
            $login = "";
            $ok = false;
            $mail = Core_Request::getString("mail", "", "POST");

            if (!empty($mail)) {
                if (Exec_Mailer::validMail($mail)) {
                    Core_Sql::getInstance()->select(
                    Core_Table::USERS_TABLE, array(
                        "name"), array(
                        "mail = '" . $mail . "'")
                    );

                    if (Core_Sql::getInstance()->affectedRows() == 1) {
                        list($login) = Core_Sql::getInstance()->fetchArray();
                        $ok = Exec_Mailer::sendMail(); // TODO envoyer un mail
                    }
                    if (!$ok)
                        Core_Logger::addWarningMessage(FORGET_LOGIN_INVALID_MAIL_ACCOUNT);
                } else {
                    Core_Logger::addWarningMessage(INVALID_MAIL);
                }
            }

            if ($ok) {
                Core_Logger::addInformationMessage(FORGET_LOGIN_IS_SUBMIT_TO . " " . $mail);
            } else {
                if (Core_Main::getInstance()->isDefaultLayout() || empty($mail)) {
                    $form = new Libs_Form("login-forgetlogin");
                    $form->setTitle(FORGET_LOGIN_TITLE);
                    $form->setDescription(FORGET_LOGIN_DESCRIPTION);
                    $form->addInputText("mail", MAIL);
                    $form->addInputHidden("mod", "connect");
                    $form->addInputHidden("view", "forgetlogin");
                    $form->addInputHidden("layout", "module");
                    $form->addInputSubmit("submit", FORGET_LOGIN_SUBMIT);
                    echo $form->render();
                    Core_Html::getInstance()->addJavascript("validForgetLogin('#form-login-forgetlogin', '#form-login-forgetlogin-mail-input');");
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
        if (!Core_Session::hasConnection()) {
            $ok = false;
            $mail = "";
            $login = Core_Request::getString("login", "", "POST");

            if (!empty($login)) {
                if (Core_Session::validLogin($login)) {
                    Core_Sql::getInstance()->select(
                    Core_Table::USERS_TABLE, array(
                        "name, mail"), array(
                        "name = '" . $login . "'")
                    );

                    if (Core_Sql::getInstance()->affectedRows() == 1) {
                        list($name, $mail) = Core_Sql::getInstance()->fetchArray();
                        if ($name == $login) {
                            // TODO Ajouter un générateur d'id
                            $ok = Exec_Mailer::sendMail(); // TODO envoyer un mail
                        }
                    }
                    if (!$ok)
                        Core_Logger::addWarningMessage(FORGET_PASSWORD_INVALID_LOGIN_ACCOUNT);
                } else {
                    $this->errorBox();
                }
            }

            if ($ok) {
                Core_Logger::addInformationMessage(FORGET_PASSWORD_IS_SUBMIT_TO . " " . $mail);
            } else {
                if (Core_Main::getInstance()->isDefaultLayout() || empty($login)) {
                    $form = new Libs_Form("login-forgetpass");
                    $form->setTitle(FORGET_PASSWORD_TITLE);
                    $form->setDescription(FORGET_PASSWORD_DESCRIPTION);
                    $form->addInputText("login", LOGIN);
                    $form->addInputHidden("mod", "connect");
                    $form->addInputHidden("view", "forgetpass");
                    $form->addInputHidden("layout", "module");
                    $form->addInputSubmit("submit", FORGET_PASSWORD_SUBMIT);
                    echo $form->render();
                    Core_Html::getInstance()->addJavascript("validForgetPass('#form-login-forgetpass', '#form-login-forgetpass-login-input');");
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