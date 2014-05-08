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
		$projectId = Core_Request::getInt("projectId", -1, "GET");

		if ($projectId >= 0) {
			$values = array("projectid", "name", "date", "language", "sourcelink", "binairelink", "description", "img", "progress", "website");
			Core_Sql::select(Core_Table::$PROJECT_TABLE, $values, array("projectid = '" . $projectId . "'"));

			if (Core_Sql::affectedRows() == 1) {
				$projectInfo = Core_Sql::fetchArray();

				// Préparation de l'entête
				Core_Loader::classLoader("Exec_JQuery");
				Exec_JQuery::getSlimbox();
				Core_Html::getInstance()->addJavascriptJquery("$('.project_description_img a').slimbox();");
				Core_Html::getInstance()->addCssTemplateFile("module_project.css");

				// Création de la page
				Core_Loader::classLoader("Libs_Form");
				$form = new Libs_Form(
					"project_description",
					Core_Html::getLink("?mod=project&view=download&&projectId=" . $projectInfo['projectid'])
				);
				$form->setTitle($projectInfo['name']);

				$libsMakeStyle = new Libs_MakeStyle("module_project_description");
				$libsMakeStyle->assign("projectInfo", $projectInfo);

				$form->addHtmlInFieldset($libsMakeStyle->render());
				$form->addFieldset(PROJECT_DESCRIPTION);

				if (!empty($projectInfo['description'])) $form->addHtmlInFieldset($projectInfo['description']);
				else $form->addHtmlInFieldset(NO_DESCRIPTION);

				$form->addSpace();
				$form->addFieldset(DOWNLOAD_DETAILS);

				$form->addInputSubmit("download_binaire", DOWNLOAD_BINAIRE, !empty($projectInfo['binairelink']) ? "" : "disabled=\"disabled\"");
				$form->addInputSubmit("download_source", DOWNLOAD_SOURCE, !empty($projectInfo['sourcelink']) ? "" : "disabled=\"disabled\"");

				if (empty($projectInfo['binairelink']) && empty($projectInfo['sourcelink'])) $form->addHtmlInFieldset(NO_DOWNLOAD);
				else $form->addHtmlInFieldset("");
				echo $form->render();
			} else {
				$this->displayProjectList();
			}
		} else {
			$this->displayProjectsList();
		}
	}

	public function download() {
		$projectId = Core_Request::getInt("projectId", -1, "POST");
		$type = Core_Request::getInt("type", -1, "POST");
	}
	
	public function setting() {
		return "Pas de setting...";
	}
}


?>