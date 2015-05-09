<?php

namespace TREngine\Engine\Exec;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Constructeur de fichier
 *
 * @author Sébastien Villemain
 *
 */
class ExecJQuery {

    public static function getTreeView($identifier) {
        $coreHtml = CoreHtml::getInstance();
        $coreHtml->addCssResourceFile("jquery.treeview.css");
        $coreHtml->addJavascriptFile("jquery.treeview.js");
        $coreHtml->addJavascriptJquery("$('" . $identifier . "').treeview({animated: 'fast', collapsed: true, unique: true, persist: 'location'});");
    }

    public static function getJdMenu() {
        $coreHtml = CoreHtml::getInstance();
        // Ajout du fichier de style
        $coreHtml->addCssResourceFile("jquery.jdMenu.css");

        // Ajout des fichier javascript
        $coreHtml->addJavascriptFile("jquery.dimensions.js");
        $coreHtml->addJavascriptFile("jquery.positionBy.js");
        $coreHtml->addJavascriptFile("jquery.bgiframe.js");
        $coreHtml->addJavascriptFile("jquery.jdMenu.js");

        // Ajout du code d'execution
        $coreHtml->addJavascriptJquery("$('ul.jd_menu').jdMenu();");
    }

    public static function getIdTabs() {
        $coreHtml = CoreHtml::getInstance();
        $coreHtml->addJavascriptFile("jquery.idTabs.js");
        $coreHtml->addCssResourceFile("jquery.idTabs.css");
    }

    public static function getSlimbox() {
        $coreHtml = CoreHtml::getInstance();
        $coreHtml->addJavascriptFile("jquery.slimbox.js");
        $coreHtml->addCssResourceFile("jquery.slimbox.css");
    }

}

?>
