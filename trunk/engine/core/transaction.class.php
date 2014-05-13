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
abstract class Core_Transaction {

    /**
     * Configuration de la transaction.
     *
     * @var array
     */
    private $transaction = array(
        "host" => "",
        "user" => "",
        "pass" => "",
        "name" => "",
        "type" => "",
    );

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
        $this->transaction = null;
    }

    /**
     * Paramètre la connexion, test la connexion puis engage une connexion.
     *
     * @param array $transaction
     * @throws Fail_Engine
     */
    public function initialize(array &$transaction) {
        if ($this->transaction === null) {
            $this->transaction = $transaction;

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
     * Retourne la valeur de la clé.
     *
     * @return string
     */
    protected function &getTransactionValue($keyName) {
        return $this->transaction[$keyName];
    }

    /**
     * Change la valeur de clé.
     *
     * @param string $keyName
     * @param string $value
     */
    protected function &setTransactionValue($keyName, $value) {
        $this->transaction[$keyName] = $value;
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getTransactionHost() {
        return $this->getTransactionValue("host");
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getTransactionUser() {
        return $this->getTransactionValue("user");
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getTransactionPass() {
        return $this->getTransactionValue("pass");
    }

    /**
     * Retourne le type de base (exemple mysqli).
     *
     * @return string
     */
    public function &getTransactionType() {
        return $this->getTransactionValue("type");
    }

}
