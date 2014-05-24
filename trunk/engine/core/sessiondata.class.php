<?php

/**
 * Description of sessiondata
 *
 * @author Sébastien Villemain
 */
class Core_SessionData implements Core_AccessToken {

    /**
     * Information sur le client.
     *
     * @var array
     */
    private $data = array();

    /**
     * Nouvelle information de block.
     *
     * @param array $data
     */
    public function __construct(array &$data) {
        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }

        $data['name'] = Exec_Entities::stripSlashes($data['name']);
        $data['rank'] = (int) $data['rank'];
        $data['signature'] = Exec_Entities::stripSlashes($data['signature']);
        $data['website'] = Exec_Entities::stripSlashes($data['website']);

        $this->data = $data;
    }

    /**
     * Retourne toutes les information sur le client.
     *
     * @return array
     */
    public function &getData() {
        return $this->data;
    }

    /**
     * Identifiant du client.
     *
     * @return string
     */
    public function &getId() {
        return $this->data['user_id'];
    }

    /**
     * Nom du client.
     *
     * @return string
     */
    public function &getName() {
        return $this->data['name'];
    }

    /**
     * Adresse email du client.
     *
     * @return string
     */
    public function &getMail() {
        return $this->data['mail'];
    }

    /**
     * Type de compte lié au client.
     *
     * @return int
     */
    public function &getRank() {
        return $this->data['rank'];
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
        return $this->data['date'];
    }

    /**
     * URL de l'avatar de l'utilisateur.
     *
     * @return string
     */
    public function &getAvatar() {
        if (empty($this->data['avatar'])) {
            $avatar = "includes/avatars/nopic.png";
        } else {
            $avatar = $this->data['avatar'];
        }
        return $avatar;
    }

    /**
     * Site internet du client.
     *
     * @return string
     */
    public function &getWebsite() {
        return $this->data['website'];
    }

    /**
     * Signature du client.
     *
     * @return string
     */
    public function &getSignature() {
        return $this->data['signature'];
    }

    /**
     * Template du client.
     *
     * @return string
     */
    public function &getTemplate() {
        return $this->data['template'];
    }

    /**
     * Affecte le template du client.
     *
     * @param string $template
     * @param boolean $force
     */
    public function setTemplate($template, $force = false) {
        if (!isset($this->data['template']) || $force) {
            $this->data['template'] = $template;
        }
    }

    /**
     * Langue du client.
     *
     * @return string
     */
    public function &getLangue() {
        return $this->data['langue'];
    }

    /**
     * Affecte la langue du client.
     *
     * @param string $langue
     * @param boolean $force
     */
    public function setLangue($langue, $force = false) {
        if (!isset($this->data['langue']) || $force) {
            $this->data['langue'] = $langue;
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
        return $this->data['rights'];
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

        $this->data['rights'] = $accessTypes;
    }

    /**
     * Détermine si les droits du client ont été vérifiés.
     *
     * @return boolean
     */
    private function hasRights() {
        return isset($this->data['rights']);
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
