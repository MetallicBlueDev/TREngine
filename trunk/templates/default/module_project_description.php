<div class="project_description_header">
	<div>
		<div class="project_description_img">
			<a href="<?php echo $projectInfo['img']; ?>">
				<?php
					Core_Loader::classLoader("Exec_Image");
					echo Exec_Image::resize($projectInfo['img'], 128, 128);
				?>
			</a>
		</div>
		<div class="project_text, description">
			<b><?php echo $projectInfo['name']; ?></b> (<?php echo $projectInfo['language']; ?>)
			<br /><?php echo RECORDED_DATE . ": " . $projectInfo['date']; ?>
			<br /><?php echo PERCENT_COMPLETE . ": " . $projectInfo['progress']; ?>%
			<br /><?php echo OFFICIAL_WEBSITE . ": " . (!empty($projectInfo['website']) ? $projectInfo['website'] : TR_ENGINE_URL); ?>
			<br />
			<?php
			if (!empty($projectInfo['sourcelink'])) {
				
			}
			?>
		</div>
	</div>
</div>
