<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Exec\ExecAgent;

/**
 * Information de base sur l'agent.
 *
 * @author Sébastien Villemain
 */
class CoreAgentData extends CoreDataStorage {

    /**
     * Nouvel agent de stockage.
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
     * User agent complet du client.
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
     * Système d'exploitation du client.
     *
     * @return string
     */
    public function &getOs(): string {
        if (!$this->exist("agentOsName")) {
            $this->searchOs();
        }
        return $this->getStringValue("agentOsName");
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
     * Referer du client.
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
        $this->setDataValue("agentIp", ExecAgent::getAddressIp());
    }

    /**
     * Recherche l'hôte du client.
     */
    private function searchHost() {
        $this->setDataValue("agentHost", ExecAgent::getHost($this->getAddressIp()));
    }

    /**
     * Recherche la chaine User agent.
     */
    private function searchUserAgent() {
        $this->setDataValue("userAgent", ExecAgent::getRawUserAgent());
    }

    /**
     * Recherche le système d'exploitation du client.
     */
    private function searchOs() {
        $this->setDataValue("agentOsName", ExecAgent::getOsName($this->getUserAgent()));
    }

    /**
     * Recherche le navigateur du client.
     */
    private function searchBrowserData() {
        $browserData = ExecAgent::getBrowserData($this->getUserAgent());

        $this->setDataValue("agentBrowserVersion", $browserData[0]);
        $this->setDataValue("agentBrowserName", $browserData[1]);
    }

    /**
     * Recherche le Referer du client.
     */
    private function searchReferer() {
        $this->setDataValue("agentReferer", ExecAgent::getReferer());
    }

}