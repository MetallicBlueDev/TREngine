<div class="title"><span><?php echo $title; ?></span></div>
<div class="description" style="width: 90%;"><span><?php echo $description; ?></span></div>

<?php
$progress = "";
foreach($projects as $key => $projectItem) {
	if ($projectItem->progress < 25) $progress = "_25";
	else if ($projectItem->progress < 50) $progress = "_50";
	else if ($projectItem->progress < 75) $progress = "_75";
	else $progress = "";
?>

<a href="<?php echo Core_Html::getLink('?mod=project&view=displayProject&projectId=' . $projectItem->projectid); ?>">
<div class="project_body<?php echo $progress; ?>">
	<div>
		<div class="project_img"><img alt="" src="templates/default/project/<?php echo strtolower($projectItem->language); ?>.png" /></div>
		<div class="project_text">
			<b><?php echo $projectItem->name; ?></b> (<?php echo $projectItem->language; ?>)
			<br /><i><?php echo PERCENT_COMPLETE . ": " . $projectItem->progress; ?>%</i>
		</div>
	</div>
</div>
</a>

<?php
}
?>

<div style="text-align: center;"><?php echo $nbProjects; ?></div>