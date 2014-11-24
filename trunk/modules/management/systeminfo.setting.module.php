<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class Module_Management_Systeminfo extends ModuleModel {

    public function setting() {
        $accountTabs = new LibsTabs("systeminfotab");
        $accountTabs->addTab(SYSTEMINFO_SYSTEM_INFO_TAB, $this->tabSystemInfo());
        $accountTabs->addTab(SYSTEMINFO_PHP_INFO_TAB, $this->tabPhpInfo());

        return $accountTabs->render();
    }

    private function tabSystemInfo() {
        $firstLine = array(
            array(
                30,
                SYSTEMINFO_SYSTEM_INFO_SETTING),
            array(
                70,
                SYSTEMINFO_SYSTEM_INFO_VALUE)
        );
        $rack = new LibsRack($firstLine);

        $modeActivedContent = "";
        $coreCache = CoreCache::getInstance();
        $modeActived = $coreCache->getCacheList();
        $currentMode = $coreCache->getTransactionType();
        foreach ($modeActived as $mode) {
            $actived = ($currentMode === $mode);
            $modeActivedContent .= " " . $mode . "="
            . (($actived) ? "yes" : "no");
        }

        $coreMain = CoreMain::getInstance();
        $infos = array(
            "TR ENGINE VERSION" => TR_ENGINE_VERSION,
            "TR ENGINE PHP VERSION" => TR_ENGINE_PHP_VERSION,
            "TR ENGINE PHP OS" => TR_ENGINE_PHP_OS,
            "TR ENGINE actived cache" => $modeActivedContent,
            "TR ENGINE DIR" => TR_ENGINE_INDEXDIR,
            "TR ENGINE URL" => TR_ENGINE_URL,
            "TR ENGINE MAIL" => TR_ENGINE_MAIL,
            "TR ENGINE valid cache time" => $coreMain->getCacheTimeLimit() . " days",
            "TR ENGINE UrlRewriting" => (($coreMain->doUrlRewriting()) ? "on" : "off"),
            "PHP built on" => php_uname(),
            "PHP version" => phpversion(),
            "WebServer to PHP interface" => php_sapi_name(),
            "Database version" => CoreSql::getInstance()->getVersion(),
            "Database collation" => CoreSql::getInstance()->getCollation()
        );

        foreach ($infos as $key => $value) {
            $rack->addLine(array(
                $key,
                $value));
        }
        return $rack->render();
    }

    private function tabPhpInfo() {
        $output = "";

        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION);
        $phpinfo = ob_get_contents();
        ob_end_clean();

        preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpinfo, $output);
        $output = preg_replace('#<table#', '<table class="table"', $output[1][0]);
        $output = preg_replace('#(\w),(\w)#', '\1, \2', $output);
        $output = preg_replace('#border="0" cellpadding="3" width="600"#', 'border="0" cellspacing="1" cellpadding="4" width="95%"', $output);
        $output = preg_replace('#<hr />#', '', $output);
        $output = str_replace('<div class="center">', '', $output);
        $output = str_replace('</div>', '', $output);
        $output = str_replace('class="e"', '', $output);
        $output = str_replace('class="v"', '', $output);

        return $output;
    }

}

?>