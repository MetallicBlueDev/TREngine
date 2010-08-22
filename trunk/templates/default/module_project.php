<?php

$projectTemplateName = "";
if (!empty($projects)) {
	include(TR_ENGINE_DIR . "/templates/default/module_project_list.php");
} else if (!empty($project)) {
	include(TR_ENGINE_DIR . "/templates/default/module_project_description.php");
}

?>

<div style="text-align: center;"><?php echo $nbProjects; ?></div>