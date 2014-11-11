<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Description of sessiondata
 *
 * @author Sébastien Villemain
 */
class Core_SessionData extends Core_DataStorage implements Core_AccessToken {

    /**
     * Nouvelle information de block.
     *
     * @param array $data
     */
    public function __construct(array &$data) {
        parent::__construct();

        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }

        $this->newStorage($data);
        $this->updateDataValue("name", Exec_Entities::stripSlashes($this->getDataValue("name")));
        $this->updateDataValue("rank", (int) $this->getDataValue("rank"));
        $this->updateDataValue("signature", Exec_Entities::stripSlashes($this->getDataValue("signature")));
        $this->updateDataValue("website", Exec_Entities::stripSlashes($this->getDataValue("website")));
    }

    /**
     * Retourne toutes les information sur le client.
     *
     * @return array
     */
    public function &getData() {
        return $this->getStorage();
    }

    /**
     * Identifiant du client.
     *
     * @return string
     */
    public function &getId() {
        return $this->getDataValue("user_id");
    }

    /**
     * Nom du client.
     *
     * @return string
     */
    public function &getName() {
        return $this->getDataValue("name");
    }

    /**
     * Adresse email du client.
     *
     * @return string
     */
    public function &getMail() {
        return $this->getDataValue("mail");
    }

    /**
     * Type de compte lié au client.
     *
     * @return int
     */
    public function &getRank() {
        return $this->getDataValue("rank", 0);
    }

    /**
     * Détermine si l'utilisateur a des droits.
     *
     * @return boolean
     */
    public function hasRank() {
        return $this->getRank() > Core_Access::RANK_PUBLIC;
    }

    /**
     * Détermine si l'utilisateur est membre.
     *
     * @return boolean
     */
    public function hasRegisteredRank() {
        return $this->getRank() >= Core_Access::RANK_REGITRED;
    }

    /**
     * Détermine si l'utilisateur est administrateur.
     *
     * @return boolean
     */
    public function hasAdminRank() {
        return $this->getRank() >= Core_Access::RANK_ADMIN;
    }

    /**
     * Détermine si l'utilisateur est administrateur.
     *
     * @return boolean
     */
    public function hasAdminWithRightsRank() {
        return $this->hasAdminRank() && count($this->getRights()) > 0;
    }

    /**
     * Détermine si l'utilisateur est super administrateur.
     *
     * @return boolean
     */
    public function hasSuperAdminRank() {
        return $this->hasAdminRank() && Core_Access::autorize(Core_AccessType::getTypeFromAdmin());
    }

    /**
     * Date d'inscription du client.
     *
     * @return timestamp
     */
    public function &getDate() {
        return $this->getDataValue("date");
    }

    /**
     * URL de l'avatar de l'utilisateur.
     *
     * @return string
     */
    public function &getAvatar() {
        $avatar = $this->getDataValue("avatar");

        if (empty($avatar)) {
            $avatar = "includes/avatars/nopic.png";
        }
        return $avatar;
    }

    /**
     * Site internet du client.
     *
     * @return string
     */
    public function &getWebsite() {
        return $this->getDataValue("website");
    }

    /**
     * Signature du client.
     *
     * @return string
     */
    public function &getSignature() {
        return $this->getDataValue("signature");
    }

    /**
     * Template du client.
     *
     * @return string
     */
    public function &getTemplate() {
        return $this->getDataValue("template");
    }

    /**
     * Affecte le template du client.
     *
     * @param string $template
     * @param boolean $force
     */
    public function setTemplate($template, $force = false) {
        if (!$this->hasValue("template") || $force) {
            $this->setDataValue("template", $template);
        }
    }

    /**
     * Langue du client.
     *
     * @return string
     */
    public function &getLangue() {
        return $this->getDataValue("langue");
    }

    /**
     * Affecte la langue du client.
     *
     * @param string $langue
     * @param boolean $force
     */
    public function setLangue($langue, $force = false) {
        if (!$this->hasValue("langue") || $force) {
            $this->setDataValue("langue", $langue);
        }
    }

    /**
     * Retourne la liste des accès que dispose le client.
     *
     * @return Core_AccessType[] array(0 => Core_AccessType)
     */
    public function &getRights() {
        if (!$this->hasRights()) {
            $this->checkRights();
        }
        return $this->getDataValue("rights");
    }

    /**
     * Routine de vérification des droits du client.
     */
    private function checkRights() {
        $accessTypes = array();

        if ($this->hasRank()) {
            $coreSql = Core_Sql::getInstance();

            $coreSql->select(
            Core_Table::USERS_RIGHTS_TABLE, array(
                "zone",
                "page",
                "identifiant"), array(
                "user_id = '" . $this->getId() . "'")
            );

            if ($coreSql->affectedRows() > 0) {
                foreach ($coreSql->fetchArray() as $rights) {
                    $accessTypes[] = Core_AccessType::getTypeFromDatabase($rights);
                }
            }
        }

        $this->setDataValue("rights", $accessTypes);
    }

    /**
     * Détermine si les droits du client ont été vérifiés.
     *
     * @return boolean
     */
    private function hasRights() {
        return $this->hasValue("rights");
    }

    /**
     * Retourne le zone d'échange.
     *
     * @return string
     */
    public function &getZone() {
        $zone = "SESSION";
        return $zone;
    }

}
