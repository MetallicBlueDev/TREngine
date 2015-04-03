<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Exec\ExecAgent;

/**
 * Information de base sur l'agent.
 *
 * @author Sébastien Villemain
 */
class CoreAgentData extends CoreDataStorage {

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
    public function &getAddressIp() {
        if (!$this->exist("agentIp")) {
            $this->searchAddressIp();
        }
        return $this->getDataValue("agentIp");
    }

    /**
     * Hôte du client.
     *
     * @return string
     */
    public function &getHost() {
        if (!$this->exist("agentHost")) {
            $this->searchHost();
        }
        return $this->getDataValue("agentHost");
    }

    /**
     * User agent complet du client.
     *
     * @return string
     */
    public function &getUserAgent() {
        if (!$this->exist("userAgent")) {
            $this->searchUserAgent();
        }
        return $this->getDataValue("userAgent");
    }

    /**
     * Système d'exploitation du client.
     *
     * @return string
     */
    public function &getOs() {
        if (!$this->exist("agentOsName")) {
            $this->searchOs();
        }
        return $this->getDataValue("agentOsName");
    }

    /**
     * Nom du navigateur du client.
     *
     * string string
     */
    public function &getBrowserName() {
        if (!$this->exist("agentBrowserName")) {
            $this->searchBrowserData();
        }
        return $this->getDataValue("agentBrowserName");
    }

    /**
     * Version du navigateur du client.
     *
     * string string
     */
    public function &getBrowserVersion() {
        if (!$this->exist("agentBrowserVersion")) {
            $this->searchBrowserData();
        }
        return $this->getDataValue("agentBrowserVersion");
    }

    /**
     * Referer du client.
     *
     * @var string
     */
    public function &getReferer() {
        if (!$this->exist("agentReferer")) {
            $this->searchReferer();
        }
        return $this->getDataValue("agentReferer");
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
