<?php
// ----------------------------------------------------------------------- //
// -- TR ENGINE = PUISSANCE + SIMPLICITÉ + ÉVOLUTIVITÉ					-- //
// -- Puissance par un codage efficace									-- //
// -- Simplicité par une interface intuitive							-- //
// -- Évolutivité par une grande souplesse d'adaptation du moteur		-- //
// ----------------------------------------------------------------------- //
// -- Une création de Trancer-Studio.net [www.trancer-studio.net]		-- //
// ----------------------------------------------------------------------- //
// Donnée : par require_once()
// ----------------------------------------------------------------------- //
// ----------------------------------------------------------------------- //
// -- Fichier de configuration du site
// -- http://www.trancer-studio.net
// ----------------------------------------------------------------------- //

// ----------------------------------------------------------------------- //
// Informations générales
//
// Adresse email du webmaster (principal)
$config["TR_ENGINE_MAIL"] = "the-trancer.postmaster@orange.fr";
//
// Statut du site ("open" = ouvert | "close" = fermé)
$config["TR_ENGINE_STATUT"] = "open";
// ----------------------------------------------------------------------- //


// -------------------------------------------------------------------------//
// Données des sessions
//
// Durée en jours de validité des fichiers de sessions mise en cache
$config['cacheTimeLimit'] = 7;
//
// Préfixe des noms des cookies
$config['cookiePrefix'] = "tr";
//
// Cles de décriptage UNIQUE (généré aleatoirement a l'installation)
$config['cryptKey'] = "A4bT9D4V";
// -------------------------------------------------------------------------//


?>