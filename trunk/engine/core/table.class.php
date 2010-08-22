<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Constantes pour les noms des tables de base de donnée
 * Utilisabe via Core_Table::MaTable
 * 
 * @author Sébastien Villemain
 */
class Core_Table {
	
	/**
	 * Prefix de chaque table
	 */ 
	private static $prefix = "";
	
	// Nom des tables
	public static $BANNED_TABLE = "banned";
	public static $BLOCKS_TABLE = "blocks";
	public static $CONFIG_TABLE = "configs";
	public static $MENUS_TABLES = "menus";
	public static $MODULES_TABLE = "modules";
	public static $USERS_TABLE = "users";
	public static $USERS_ADMIN_TABLE = "users_admin";

	// Tables additionnelles pour les modules
	public static $PROJECT_TABLE = "project";
}
?>