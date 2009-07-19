<?php
if (preg_match("/table.class.php/ie", $_SERVER['PHP_SELF'])) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Constantes pour les noms des tables de base de donn�e
 * Utilisabe via Core_Table::MaTable
 * 
 * @author S�bastien Villemain
 */
class Core_Table {
	
	/**
	 * Prefix de chaque table
	 */ 
	private static $prefix = "";
	
	// Nom des tables
	public static $CONFIG_TABLE = "configs";
	public static $USERS_TABLE = "users";
	
	private static $tables = array(
		"CONFIG_TABLE", "USERS_TABLE"
	);
	
	/**
	 * Ajoute le prefixe pour chaque table
	 * 
	 * @param $prefix
	 */
	public static function setPrefix($prefix) {
		// Aucun pr�fixe n'a �t� renseign�
		if (!self::$prefix && $prefix != "") {
			self::$prefix = $prefix;
			// Application du pr�fixe
			foreach (self::$tables as $value) {
				self::$$value = $prefix . "_" . self::$$value;
			}
		}
	}
}
?>