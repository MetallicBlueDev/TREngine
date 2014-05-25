<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de transaction de base.
 *
 * @author Sébastien Villemain
 */
abstract class Core_Transaction extends Core_DataStorage {

    /**
     * Objet de connexion.
     *
     * @var mixed (resource / mysqli / etc.)
     */
    protected $connId = null;

    /**
     * Nouveau modèle de transaction.
     */
    protected function __construct() {
        parent::__construct();
    }

    /**
     * Paramètre la connexion, test la connexion puis engage une connexion.
     *
     * @param array $transaction
     * @throws Fail_Engine
     */
    public function initialize(array &$transaction) {
        if (!$this->initialized()) {
            $this->newStorage($transaction);

            if ($this->canUse()) {
                // Connexion au serveur
                $this->netConnect();

                if (!$this->netConnected()) {
                    $this->throwException("Connect");
                }

                // Sélection d'une base de données
                if (!$this->netSelect()) {
                    $this->throwException("Select");
                }
            } else {
                $this->throwException("CanUse");
            }
        }
    }

    /**
     * Lance une exception pour gérant ce type de transaction.
     *
     * @param string $message
     * @throws Fail_Engine
     */
    protected function throwException($message) {
        throw new Fail_Engine($message);
    }

    /**
     * Destruction de la communication.
     */
    public function __destruct() {
        $this->netDeconnect();
    }

    /**
     * Détermine si le gestionnaire est utilisable.
     *
     * @return boolean
     */
    protected function canUse() {
        return false;
    }

    /**
     * Etablie une connexion au serveur.
     */
    public function netConnect() {

    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return boolean
     */
    public function netConnected() {
        return ($this->connId !== null) ? true : false;
    }

    /**
     * Déconnexion du serveur.
     */
    public function netDeconnect() {

    }

    /**
     * Sélectionne un noeud dans la transaction.
     *
     * @return boolean true succès
     */
    public function &netSelect() {
        $rslt = false;
        return $rslt;
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getTransactionHost() {
        return $this->getDataValue("host");
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getTransactionUser() {
        return $this->getDataValue("user");
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getTransactionPass() {
        return $this->getDataValue("pass");
    }

    /**
     * Retourne le type de base (exemple mysqli).
     *
     * @return string
     */
    public function &getTransactionType() {
        return $this->getDataValue("type");
    }

}
