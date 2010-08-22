<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Project_Index extends Module_Model {
	
	public function display() {
		$this->displayProjectsList();
	}

	public function displayProjectsList() {
		Core_Sql::select(Core_Table::$PROJECT_TABLE, 
			array("projectid", "name", "date", "language", "progress"),
			array(),
			array("progress ASC", "date DESC")
		);

		Core_Html::getInstance()->addCssTemplateFile("module_project.css");
		$libsMakeStyle = new Libs_MakeStyle("module_project_list");
		$libsMakeStyle->assign("title", PROJECTS_LIST_TITLE);
		$libsMakeStyle->assign("description", PROJECTS_LIST_DESCRIPTION);

		if (Core_Sql::affectedRows() > 0) {
			Core_Sql::addBuffer("projectList");
			$projects = Core_Sql::getBuffer("projectList");
			$libsMakeStyle->assign("projects", $projects);
			$libsMakeStyle->assign("nbProjects", count($projects) .  " " . NB_PROJECT);
		} else {
			$libsMakeStyle->assign("nbProjects", NO_PROJECT);
		}
		$libsMakeStyle->display();
	}

	public function displayProject(){
		// Identifiant du projet
		$projectId = Core_Request::getInt("projectId", -1);

		if ($projectId >= 0) {
			$values = array("name", "date", "language", "sourcelink", "binairelink", "description", "img", "progress", "website");
			Core_Sql::select(Core_Table::$PROJECT_TABLE, $values, array("projectid = '" . $projectId . "'"));

			if (Core_Sql::affectedRows() == 1) {
				Core_Loader::classLoader("Exec_JQuery");
				Exec_JQuery::getSlimbox();

				Core_Html::getInstance()->addJavascriptJquery("$('.project_description_img a').slimbox();");
				
				Core_Html::getInstance()->addCssTemplateFile("module_project.css");
				$libsMakeStyle = new Libs_MakeStyle("module_project_description");
				$projectInfo = Core_Sql::fetchArray();
				$libsMakeStyle->assign("projectInfo", $projectInfo);
				$libsMakeStyle->display();
			} else {
				$this->displayProjectList();
			}
		} else {
			$this->displayProjectsList();
		}
	}
	
	public function setting() {
		return "Pas de setting...";
	}
}


?>