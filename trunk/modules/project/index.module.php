<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

class Module_Project_Index extends Libs_ModuleModel {

    const PROJECT_TABLE = "project";

    public function display() {
        $this->displayProjectsList();
    }

    public function displayProjectsList() {
        Core_Sql::getInstance()->select(self::PROJECT_TABLE, array(
            "projectid",
            "name",
            "date",
            "language",
            "progress"), array(), array(
            "progress ASC",
            "date DESC")
        );

        Core_Html::getInstance()->addCssTemplateFile("module_project.css");
        $libsMakeStyle = new Libs_MakeStyle("module_project_list");
        $libsMakeStyle->assign("title", PROJECTS_LIST_TITLE);
        $libsMakeStyle->assign("description", PROJECTS_LIST_DESCRIPTION);

        if (Core_Sql::getInstance()->affectedRows() > 0) {
            Core_Sql::getInstance()->addArrayBuffer("projectList");
            $projects = Core_Sql::getInstance()->getBuffer("projectList");
            $libsMakeStyle->assign("projects", $projects);
            $libsMakeStyle->assign("nbProjects", count($projects) . " " . NB_PROJECT);
        } else {
            $libsMakeStyle->assign("nbProjects", NO_PROJECT);
        }
        $libsMakeStyle->display();
    }

    public function displayProject() {
        // Identifiant du projet
        $projectId = Core_Request::getInteger("projectId", -1, "GET");

        if ($projectId >= 0) {
            $values = array(
                "projectid",
                "name",
                "date",
                "language",
                "sourcelink",
                "binairelink",
                "description",
                "img",
                "progress",
                "website");
            Core_Sql::getInstance()->select(self::PROJECT_TABLE, $values, array(
                "projectid = '" . $projectId . "'"));

            if (Core_Sql::getInstance()->affectedRows() == 1) {
                $projectInfo = Core_Sql::getInstance()->fetchArray();

                // Préparation de l'entête
                Exec_JQuery::getSlimbox();
                Core_Html::getInstance()->addJavascriptJquery("$('.project_description_img a').slimbox();");
                Core_Html::getInstance()->addCssTemplateFile("module_project.css");

                // Création de la page
                $form = new Libs_Form(
                "project_description", Core_UrlRewriting::getLink("?mod=project&view=download&&projectId=" . $projectInfo['projectid'])
                );
                $form->setTitle($projectInfo['name']);

                $libsMakeStyle = new Libs_MakeStyle("module_project_description");
                $libsMakeStyle->assign("projectInfo", $projectInfo);

                $form->addHtmlInFieldset($libsMakeStyle->render());
                $form->addFieldset(PROJECT_DESCRIPTION);

                if (!empty($projectInfo['description']))
                    $form->addHtmlInFieldset($projectInfo['description']);
                else
                    $form->addHtmlInFieldset(NO_DESCRIPTION);

                $form->addSpace();
                $form->addFieldset(DOWNLOAD_DETAILS);

                $form->addInputSubmit("download_binaire", DOWNLOAD_BINAIRE, !empty($projectInfo['binairelink']) ? "" : "disabled=\"disabled\"");
                $form->addInputSubmit("download_source", DOWNLOAD_SOURCE, !empty($projectInfo['sourcelink']) ? "" : "disabled=\"disabled\"");

                if (empty($projectInfo['binairelink']) && empty($projectInfo['sourcelink']))
                    $form->addHtmlInFieldset(NO_DOWNLOAD);
                else
                    $form->addHtmlInFieldset("");
                echo $form->render();
            } else {
                $this->displayProjectList();
            }
        } else {
            $this->displayProjectsList();
        }
    }

    public function download() {
        $projectId = Core_Request::getInteger("projectId", -1, "POST");
        $type = Core_Request::getInteger("type", -1, "POST");
    }

    public function setting() {
        return "Pas de setting...";
    }

}

?>