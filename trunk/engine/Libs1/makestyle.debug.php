<html>
<b><?php echo $errorMessageTitle; ?></b>
<br /><br />



<?php if (!empty($errorMessage)) { ?>
Error details:<br />
<ul>
<?php foreach($errorMessage as $value) { ?>
<li><?php echo $value; ?></li>
<?php } ?>
</ul>
<?php } ?>
</html>