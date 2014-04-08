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
    public static $BANNED_TABLE = "banned";

    /**
     * Table des blocks.
     *
     * @var string
     */
    public static $BLOCKS_TABLE = "blocks";

    /**
     * Table de la configuration du moteur.
     *
     * @var string
     */
    public static $CONFIG_TABLE = "configs";

    /**
     * Table des menus.
     *
     * @var string
     */
    public static $MENUS_TABLES = "menus";

    /**
     * Table des modules.
     *
     * @var string
     */
    public static $MODULES_TABLE = "modules";

    /**
     * Table des utilisateurs.
     *
     * @var string
     */
    public static $USERS_TABLE = "users";

    /**
     * Table des administrateurs.
     *
     * @var string
     */
    public static $USERS_ADMIN_TABLE = "users_admin";

}
