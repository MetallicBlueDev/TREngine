<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

/**
 * Nom des tables de base de données utilisée par le moteur.
 *
 * @author Sébastien Villemain
 */
class Core_Table {

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
     * Table des administrateurs.
     *
     * @var string
     */
    const USERS_ADMIN_TABLE = "users_admin";

}
