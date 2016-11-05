<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Information de base sur une session d'un utilisateur.
 *
 * @author Sébastien Villemain
 */
class CoreSessionData extends CoreDataStorage implements CoreAccessToken {

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
        $this->updateDataValue("name", ExecEntities::stripSlashes($this->getStringValue("name")));
        $this->updateDataValue("rank", $this->getIntValue("rank"));
        $this->updateDataValue("signature", ExecEntities::stripSlashes($this->getStringValue("signature")));
        $this->updateDataValue("website", ExecEntities::stripSlashes($this->getStringValue("website")));
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
        return $this->getStringValue("user_id");
    }

    /**
     * Nom du client.
     *
     * @return string
     */
    public function &getName() {
        return $this->getStringValue("name");
    }

    /**
     * Adresse email du client.
     *
     * @return string
     */
    public function &getMail() {
        return $this->getStringValue("mail");
    }

    /**
     * Type de compte lié au client.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return int
     */
    public function &getRank() {
        return $this->getIntValue("rank", 0);
    }

    /**
     * Détermine si l'utilisateur a des droits.
     *
     * @return bool
     */
    public function hasRank() {
        return $this->getRank() > CoreAccess::RANK_PUBLIC;
    }

    /**
     * Détermine si l'utilisateur est membre.
     *
     * @return bool
     */
    public function hasRegisteredRank() {
        return $this->getRank() >= CoreAccess::RANK_REGISTRED;
    }

    /**
     * Détermine si l'utilisateur est administrateur.
     *
     * @return bool
     */
    public function hasAdminRank() {
        return $this->getRank() >= CoreAccess::RANK_ADMIN;
    }

    /**
     * Détermine si l'utilisateur est administrateur.
     *
     * @return bool
     */
    public function hasAdminWithRightsRank() {
        return $this->hasAdminRank() && count($this->getRights()) > 0;
    }

    /**
     * Détermine si l'utilisateur est super administrateur.
     *
     * @return bool
     */
    public function hasSuperAdminRank() {
        return $this->hasAdminRank() && CoreAccess::autorize(CoreAccessType::getTypeFromAdmin());
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
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getAvatar() {
        $avatar = $this->getStringValue("avatar");

        if (empty($avatar)) {
            $avatar = "Resources/Avatars/NoPic.png";
        }
        return $avatar;
    }

    /**
     * Site internet du client.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getWebsite() {
        return $this->getStringValue("website");
    }

    /**
     * Signature du client.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getSignature() {
        return $this->getStringValue("signature");
    }

    /**
     * Template du client.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getTemplate() {
        return $this->getStringValue("template");
    }

    /**
     * Affecte le template du client.
     *
     * @param string $template
     * @param bool $force
     */
    public function setTemplate($template, $force = false) {
        if (!$this->hasValue("template") || $force) {
            $this->setDataValue("template", $template);
        }
    }

    /**
     * Langue du client.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getLangue() {
        return $this->getStringValue("langue");
    }

    /**
     * Affecte la langue du client.
     *
     * @param string $langue
     * @param bool $force
     */
    public function setLangue($langue, $force = false) {
        if (!$this->hasValue("langue") || $force) {
            $this->setDataValue("langue", $langue);
        }
    }

    /**
     * Retourne la liste des accès que dispose le client.
     *
     * @return CoreAccessType[] array(0 => CoreAccessType)
     */
    public function &getRights() {
        if (!$this->hasRights()) {
            $this->checkRights();
        }
        return $this->getDataValue("rights");
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getZone() {
        $zone = "SESSION";
        return $zone;
    }

    /**
     * Routine de vérification des droits du client.
     */
    private function checkRights() {
        $accessTypes = array();

        if ($this->hasRank()) {
            $coreSql = CoreSql::getInstance();

            $coreSql->select(
            CoreTable::USERS_RIGHTS_TABLE, array(
                "zone",
                "page",
                "identifiant"), array(
                "user_id = '" . $this->getId() . "'")
            );

            if ($coreSql->affectedRows() > 0) {
                foreach ($coreSql->fetchArray() as $rights) {
                    $accessTypes[] = CoreAccessType::getTypeFromDatabase($rights);
                }
            }
        }

        $this->setDataValue("rights", $accessTypes);
    }

    /**
     * Détermine si les droits du client ont été vérifiés.
     *
     * @return bool
     */
    private function hasRights() {
        return $this->hasValue("rights");
    }

}
