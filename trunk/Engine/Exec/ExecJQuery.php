<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreHtml;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de manipulation de JQuery.
 *
 * @author Sébastien Villemain
 */
class ExecJQuery {

    public static function checkTreeView($identifier) {
        $coreHtml = CoreHtml::getInstance();
        $coreHtml->addCssResourceFile("jquery.treeview.css");
        $coreHtml->addJavascriptFile("jquery.treeview.js");
        $coreHtml->addJavascriptJquery("$('" . $identifier . "').treeview({animated: 'fast', collapsed: true, unique: true, persist: 'location'});");
    }

    public static function checkJdMenu() {
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

    public static function checkSlimbox() {
        $coreHtml = CoreHtml::getInstance();
        $coreHtml->addJavascriptFile("jquery.slimbox.js");
        $coreHtml->addCssResourceFile("jquery.slimbox.css");
    }

}
