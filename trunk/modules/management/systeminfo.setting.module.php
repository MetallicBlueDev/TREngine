<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Management_Systeminfo extends Module_Model {
	public function setting() {
		Core_Loader::classLoader("Libs_Tabs");
		
		$accountTabs = new Libs_Tabs("systeminfotab");
		$accountTabs->addTab(SYSTEMINFO_SYSTEM_INFO_TAB, $this->tabSystemInfo());
		$accountTabs->addTab(SYSTEMINFO_PHP_INFO_TAB, $this->tabPhpInfo());
		
		return $accountTabs->render();
	}
	
	private function tabSystemInfo() {
		$content = "<table style=\"width: 100%;\">"
		. "<tr><td style=\"width: 30%:\"><b>" . SYSTEMINFO_SYSTEM_INFO_SETTING . "</b></td><td style=\"width: 70%;\"><b>" . SYSTEMINFO_SYSTEM_INFO_VALUE . "</b></td></tr>";
		
		$modeActivedContent = "";
		$modeActived = Core_CacheBuffer::getModeActived();
		foreach($modeActived as $mode => $actived) {
			$modeActivedContent .= " " . $mode . "="
			. (($actived) ? "yes" : "no");
		}
		
		$infos = array("TR ENGINE VERSION" => TR_ENGINE_VERSION, 
			"TR ENGINE PHP VERSION" => TR_ENGINE_PHP_VERSION, 
			"TR ENGINE PHP OS" => TR_ENGINE_PHP_OS,
			"TR ENGINE actived cache" => $modeActivedContent,
			"TR ENGINE DIR" => TR_ENGINE_DIR, 
			"TR ENGINE URL" => TR_ENGINE_URL, 
			"TR ENGINE MAIL" => TR_ENGINE_MAIL, 
			"TR ENGINE valid cache time" => Core_Main::$coreConfig['cacheTimeLimit'] . " days",
			"TR ENGINE UrlRewriting" => (($urlRewriting == 1) ? "on" : "off"),
			"PHP built on" => php_uname(),
			"PHP version" => phpversion(),
			"WebServer to PHP interface" => php_sapi_name(), 
			"Database version" => Core_Sql::getVersion(),
			"Database collation" => Core_Sql::getCollation()
		);
		
		foreach($infos as $key => $value) {
			$content .= "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>";
		}
		$content .= "</table>";
		return $content;
	}
	
	private function tabPhpInfo() {
		$output = "";
		
		ob_start();
		phpinfo(INFO_GENERAL | INFO_CONFIGURATION);
		$phpinfo = ob_get_contents();
		ob_end_clean();

		preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpinfo, $output);
		$output = preg_replace('#<table#', '<table class="adminlist" align="center"', $output[1][0]);
		$output = preg_replace('#(\w),(\w)#', '\1, \2', $output);
		$output = preg_replace('#border="0" cellpadding="3" width="600"#', 'border="0" cellspacing="1" cellpadding="4" width="95%"', $output);
		$output = preg_replace('#<hr />#', '', $output);
		$output = str_replace('<div class="center">', '', $output);
		$output = str_replace('</div>', '', $output);

		return $output;
	}
}

?>