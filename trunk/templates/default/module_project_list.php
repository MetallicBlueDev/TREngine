<div class="title"><span><?php echo $title; ?></span></div>
<div class="description" style="width: 90%;"><span><?php echo $description; ?></span></div>

<?php
$progress = "";
foreach ($projects as $key => $projectItem) {
    if ($projectItem['progress'] < 25)
        $progress = "_25";
    else if ($projectItem['progress'] < 50)
        $progress = "_50";
    else if ($projectItem['progress'] < 75)
        $progress = "_75";
    else
        $progress = "";
    ?>

    <?php echo CoreHtml::getLink('?mod=project&view=displayProject&projectId=' . $projectItem['projectid'], '
        <div class="project_body' . $progress . '">
            <div>
                <div class="project_img"><img alt="" src="templatestest/default/project/' . strtolower($projectItem['language']) . '.png" /></div>
                <div class="project_text">
                    <b>' . $projectItem['name'] . '</b> (' . $projectItem['language'] . ')
                    <br /><i>' . PERCENT_COMPLETE . ': " ' . $projectItem['progress'] . '%</i>
                </div>
            </div>
        </div>');
    ?>

    <?php
}
?>

<div style="text-align: center;"><?php echo $nbProjects; ?></div>