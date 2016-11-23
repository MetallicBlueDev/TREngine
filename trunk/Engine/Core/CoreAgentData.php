<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Exec\ExecUserAgent;

/**
 * Collecteur d'information sur le User-Agent.
 *
 * @author Sébastien Villemain
 */
class CoreAgentData extends CoreDataStorage {

    /**
     * Nouveau collecteur d'information sur le User-Agent.
     */
    public function __construct() {
        parent::__construct();

        $data = array();
        $this->newStorage($data);
    }

    /**
     * Adresse IP du client.
     *
     * @return string
     */
    public function &getAddressIp(): string {
        if (!$this->exist("agentIp")) {
            $this->searchAddressIp();
        }
        return $this->getStringValue("agentIp");
    }

    /**
     * Hôte du client.
     *
     * @return string
     */
    public function &getHost(): string {
        if (!$this->exist("agentHost")) {
            $this->searchHost();
        }
        return $this->getStringValue("agentHost");
    }

    /**
     * User-Agent complet de cette session.
     *
     * @return string
     */
    public function &getUserAgent(): string {
        if (!$this->exist("userAgent")) {
            $this->searchUserAgent();
        }
        return $this->getStringValue("userAgent");
    }

    /**
     * Type de système d'exploitation du client.
     *
     * @return string
     */
    public function &getOsCategory(): string {
        if (!$this->exist("agentOsCategory")) {
            $this->searchOs();
        }
        return $this->getStringValue("agentOsCategory");
    }

    /**
     * Système d'exploitation du client.
     *
     * @return string
     */
    public function &getOsName(): string {
        if (!$this->exist("agentOsName")) {
            $this->searchOs();
        }
        return $this->getStringValue("agentOsName");
    }

    /**
     * Type de navigateur du client.
     *
     * @return string
     */
    public function &getBrowserCategory(): string {
        if (!$this->exist("agentBrowserCategory")) {
            $this->searchBrowserData();
        }
        return $this->getStringValue("agentBrowserCategory");
    }

    /**
     * Nom du navigateur du client.
     *
     * @return string
     */
    public function &getBrowserName(): string {
        if (!$this->exist("agentBrowserName")) {
            $this->searchBrowserData();
        }
        return $this->getStringValue("agentBrowserName");
    }

    /**
     * Version du navigateur du client.
     *
     * string string
     */
    public function &getBrowserVersion(): string {
        if (!$this->exist("agentBrowserVersion")) {
            $this->searchBrowserData();
        }
        return $this->getStringValue("agentBrowserVersion");
    }

    /**
     * Chemin référent qu'a suivi le client.
     *
     * @var string
     */
    public function &getReferer(): string {
        if (!$this->exist("agentReferer")) {
            $this->searchReferer();
        }
        return $this->getStringValue("agentReferer");
    }

    /**
     * Recherche l'adresse IP du client.
     */
    private function searchAddressIp() {
        $this->setDataValue("agentIp", ExecUserAgent::getAddressIp());
    }

    /**
     * Recherche l'hôte du client.
     */
    private function searchHost() {
        $this->setDataValue("agentHost", ExecUserAgent::getHost($this->getAddressIp()));
    }

    /**
     * Recherche la chaine User-Agent.
     */
    private function searchUserAgent() {
        $this->setDataValue("userAgent", ExecUserAgent::getRawUserAgent());
    }

    /**
     * Recherche le système d'exploitation du client.
     */
    private function searchOs() {
        $osData = ExecUserAgent::getOsData($this->getUserAgent());

        $this->setDataValue("agentOsCategory", $osData['category']);
        $this->setDataValue("agentOsName", $osData['name']);
    }

    /**
     * Recherche le navigateur du client.
     */
    private function searchBrowserData() {
        $browserData = ExecUserAgent::getBrowserData($this->getUserAgent());

        $this->setDataValue("agentBrowserCategory", $browserData['category']);
        $this->setDataValue("agentBrowserName", $browserData['name']);
        $this->setDataValue("agentBrowserVersion", $browserData['version']);
    }

    /**
     * Recherche le chemin référent que le client a suivi.
     */
    private function searchReferer() {
        $this->setDataValue("agentReferer", ExecUserAgent::getReferer());
    }

}
