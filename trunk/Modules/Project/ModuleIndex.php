<?php

namespace TREngine\Modules\Project;

use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreUrlRewriting;
use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Lib\LibForm;
use TREngine\Engine\Exec\ExecJQuery;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class ModuleIndex extends ModuleModel {

    const PROJECT_TABLE = "project";

    public function display() {
        $this->displayProjectsList();
    }

    public function displayProjectsList() {
        CoreSql::getInstance()->select(self::PROJECT_TABLE, array(
            "projectid",
            "name",
            "date",
            "language",
            "progress"), array(), array(
            "progress ASC",
            "date DESC")
        );

        CoreHtml::getInstance()->addCssTemplateFile("module_project.css");
        $libMakeStyle = new LibMakeStyle("module_project_list");
        $libMakeStyle->assign("title", PROJECTS_LIST_TITLE);
        $libMakeStyle->assign("description", PROJECTS_LIST_DESCRIPTION);

        if (CoreSql::getInstance()->affectedRows() > 0) {
            CoreSql::getInstance()->addArrayBuffer("projectList");
            $projects = CoreSql::getInstance()->getBuffer("projectList");
            $libMakeStyle->assign("projects", $projects);
            $libMakeStyle->assign("nbProjects", count($projects) . " " . NB_PROJECT);
        } else {
            $libMakeStyle->assign("nbProjects", NO_PROJECT);
        }
        $libMakeStyle->display();
    }

    public function displayProject() {
        // Identifiant du projet
        $projectId = CoreRequest::getInteger("projectId", -1, "GET");

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
            CoreSql::getInstance()->select(self::PROJECT_TABLE, $values, array(
                "projectid = '" . $projectId . "'"));

            if (CoreSql::getInstance()->affectedRows() == 1) {
                $projectInfo = CoreSql::getInstance()->fetchArray();

                // Préparation de l'entête
                ExecJQuery::getSlimbox();
                CoreHtml::getInstance()->addJavascriptJquery("$('.project_description_img a').slimbox();");
                CoreHtml::getInstance()->addCssTemplateFile("module_project.css");

                // Création de la page
                $form = new LibForm(
                "project_description", CoreUrlRewriting::getLink("?mod=project&view=download&&projectId=" . $projectInfo['projectid'])
                );
                $form->setTitle($projectInfo['name']);

                $libMakeStyle = new LibMakeStyle("module_project_description");
                $libMakeStyle->assign("projectInfo", $projectInfo);

                $form->addHtmlInFieldset($libMakeStyle->render());
                $form->addFieldset(PROJECT_DESCRIPTION);

                if (!empty($projectInfo['description'])) {
                    $form->addHtmlInFieldset($projectInfo['description']);
                } else {
                    $form->addHtmlInFieldset(NO_DESCRIPTION);
                }

                $form->addSpace();
                $form->addFieldset(DOWNLOAD_DETAILS);

                $form->addInputSubmit("download_binaire", DOWNLOAD_BINAIRE, !empty($projectInfo['binairelink']) ? "" : "disabled=\"disabled\"");
                $form->addInputSubmit("download_source", DOWNLOAD_SOURCE, !empty($projectInfo['sourcelink']) ? "" : "disabled=\"disabled\"");

                if (empty($projectInfo['binairelink']) && empty($projectInfo['sourcelink'])) {
                    $form->addHtmlInFieldset(NO_DOWNLOAD);
                } else {
                    $form->addHtmlInFieldset("");
                }
                echo $form->render();
            } else {
                $this->displayProjectList();
            }
        } else {
            $this->displayProjectsList();
        }
    }

    public function download() {
//        $projectId = CoreRequest::getInteger("projectId", -1, "POST");
//        $type = CoreRequest::getInteger("type", -1, "POST");
    }

    public function setting() {
        return "Pas de setting...";
    }

}
