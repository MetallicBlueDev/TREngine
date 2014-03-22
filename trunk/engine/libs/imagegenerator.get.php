<?php

if (isset($_GET['mode']) && $_GET['mode'] == "code" && !empty($_GET['code']))
{	// Methode captcha code
	// Cryptage
	$text = md5($_GET['code']);
	$text = substr($text, 2, 5);
	
    header("Content-type: image/png");
    $im = imagecreatefromjpeg("../includes/imagegenerator/captcha.jpg");
    $id = imagecreatefromjpeg("../includes/imagegenerator/captcha.jpg");
    $grey = imagecolorallocate($im, 128, 128, 128); 
    $black = imagecolorallocate($im, 0, 0, 0);
    $font = "../includes/imagegenerator/Alanden_.ttf";
	
    for ($i = 0; $i < 5; $i++) {
		$angle = mt_rand(10, 30); 
		if (mt_rand(0,1) == 1) $angle =- $angle;
		imagettftext($im, 12, $angle, 11+(20*$i), 15, $grey, $font, substr($text,$i,1));
		imagettftext($im, 12, $angle, 10+(20*$i), 16, $black, $font, substr($text,$i,1));
    }

    imagecopymerge($im, $id, 0, 0, 0, 0, 120, 30, 50);
    imagepng($im);
    imagedestroy($im);
    imagedestroy($id);
}
else if (isset($_GET['mode']) && $_GET['mode'] == "text" && !empty($_GET['text']))
{	// Methode text & mail
	// Décryptage
	$text = $_GET['text'];
	$text = urldecode($text);
	$text = base64_decode($text);
	if (preg_match("/_AT_/", $text)) {
		$text = str_replace("_AT_", "@", $text);
		$text = str_replace("_POINT_", ".", $text);
	}
	
    header("Content-type: image/png");
	
	if (strlen($text) < 5) $more_pixel = 5;
	else if (strlen($text) > 100) $more_pixel = -((strlen($text) - 60)/1.8);
	else if (strlen($text) > 60) $more_pixel = -((strlen($text) - 60));
	else if (strlen($text) > 50) $more_pixel = -((strlen($text) - 50));
	else $more_pixel = 0;
	
	$taille_texte = 6;
	$width = (strlen($text) * ($taille_texte/1.05)) + $more_pixel +2;
	$image = imagecreate($width, 13);
	$font = imagecolorallocate($image, 106, 115, 124);
	$couleur_texte = imagecolorallocate($image, 5, 88, 123); // couleur eclat bleue / blue color
	// $couleur_texte = imagecolorallocate($image, 128, 255, 169); // couleur bleue / real blue color
	// $couleur_texte = imagecolorallocate($image, 0, 0, 0); // couleur noir / black color
	// $couleur_texte = imagecolorallocate($image, 255, 255, 255); // couleur blanc / white color
	// $couleur_texte = imagecolorallocate($image, 128, 255, 255); // couleur rouge / red color
	// $couleur_texte = imagecolorallocate($image, 85, 255, 128); // couleur verte / green color
	
	imagettftext($image, $taille_texte, 0, 2, 12, $couleur_texte, "../includes/imagegenerator/nokiafc22.ttf", $text);
	imagecolortransparent($image, $font); // implication de la transparence
	imagepng($image);
}


?>