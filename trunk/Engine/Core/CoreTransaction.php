<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de transaction de base.
 *
 * @author Sébastien Villemain
 */
abstract class CoreTransaction extends CoreDataStorage {

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
     * @throws FailEngine
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
     * Destruction de la communication.
     */
    public function __destruct() {
        $this->netDeconnect();
    }

    /**
     * Etablie une connexion au serveur.
     */
    public function netConnect() {

    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return bool
     */
    public function netConnected(): bool {
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
     * @return bool true succès
     */
    public function &netSelect(): bool {
        $rslt = false;
        return $rslt;
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getTransactionHost(): string {
        return $this->getStringValue("host");
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getTransactionUser(): string {
        return $this->getStringValue("user");
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getTransactionPass(): string {
        return $this->getStringValue("pass");
    }

    /**
     * Retourne le type de base (exemple mysqli).
     *
     * @return string
     */
    public function &getTransactionType(): string {
        return $this->getStringValue("type");
    }

    /**
     * Détermine si le gestionnaire est utilisable.
     *
     * @return bool
     */
    protected function canUse(): bool {
        return false;
    }

    /**
     * Lance une exception pour gérant ce type de transaction.
     *
     * @param string $message
     * @throws FailEngine
     */
    protected function throwException(string $message) {
        throw new FailEngine($message);
    }

}
