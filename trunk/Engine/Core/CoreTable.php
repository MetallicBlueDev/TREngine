<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Référence le nom des tables de base de données utilisée par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreTable {

    /**
     * Table des bannissements.
     *
     * @var string
     */
    const BANNED_TABLE = "banned";

    /**
     * Table des blocks.
     *
     * @var string
     */
    const BLOCKS_TABLE = "blocks";

    /**
     * Table de la configuration du moteur.
     *
     * @var string
     */
    const CONFIG_TABLE = "configs";

    /**
     * Table des menus.
     *
     * @var string
     */
    const MENUS_TABLES = "menus";

    /**
     * Table des modules.
     *
     * @var string
     */
    const MODULES_TABLE = "modules";

    /**
     * Table des utilisateurs.
     *
     * @var string
     */
    const USERS_TABLE = "users";

    /**
     * Table des droits des utilisateurs.
     *
     * @var string
     */
    const USERS_RIGHTS_TABLE = "users_rights";

}
