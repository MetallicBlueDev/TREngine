<!DOCTYPE html>
<html lang="en">
    <h1><?php echo $errorMessageTitle; ?></h1>
    <br /><br />
    <?php if (!empty($errorMessage)) { ?>
        Error details:<br />
        <ul>
            <?php foreach ($errorMessage as $value) { ?>
                <li><?php echo $value; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>
</html>