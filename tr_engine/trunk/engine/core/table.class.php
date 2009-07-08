<?php
if (preg_match("/table.class.php/ie", $_SERVER['PHP_SELF'])) {
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
	public static $CONFIG_TABLE = "configs";
	public static $USERS_TABLE = "users";
	public static $BANNED_TABLE = "banned";
	public static $BLOCKS_TABLE = "blocks";
	
	private static $tables = array(
		"CONFIG_TABLE", "USERS_TABLE",
		"BANNED_TABLE", "BLOCKS_TABLE"
	);
	
	/**
	 * Ajoute le prefixe pour chaque table
	 * 
	 * @param $prefix
	 */
	public static function setPrefix($prefix) {
		// Aucun préfixe n'a été renseigné
		if (!self::$prefix && $prefix != "") {
			self::$prefix = $prefix;
			// Application du préfixe
			foreach (self::$tables as $value) {
				self::$$value = $prefix . "_" . self::$$value;
			}
		}
	}
}
?>