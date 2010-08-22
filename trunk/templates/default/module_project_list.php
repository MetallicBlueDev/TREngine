<?php
$progress = "";
foreach($projects as $key => $projectItem) {
	if ($projectItem->progress < 25) $progress = "_25";
	else if ($projectItem->progress < 50) $progress = "_50";
	else if ($projectItem->progress < 75) $progress = "_75";
	else $progress = "";
?>

<div class="project_body<?php echo $progress; ?>">
	<div>
		<div class="project_img"><img src="<?php echo TR_ENGINE_DIR; ?>/templates/default/project/<?php echo strtolower($projectItem->language); ?>.png" /></div>
		<div class="project_text">
			<b><?php echo $projectItem->name; ?></b> (<?php echo $projectItem->language; ?>) <i><?php echo $projectItem->progress; ?>%</i>
		</div>
	</div>
</div>


<?php

}

?>