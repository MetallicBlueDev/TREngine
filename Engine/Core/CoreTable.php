<?php

namespace TREngine\Engine\Core;

/**
 * Référence le nom des tables en base de données utilisées par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreTable
{

    /**
     * Table des bannissements.
     *
     * @var string
     */
    const BANNED = "banned";

    /**
     * Table des blocks.
     *
     * @var string
     */
    const BLOCKS = "blocks";

    /**
     * Table de la configuration du moteur.
     *
     * @var string
     */
    const CONFIG = "configs";

    /**
     * Table des menus.
     *
     * @var string
     */
    const MENUS = "menus";

    /**
     * Table des modules.
     *
     * @var string
     */
    const MODULES = "modules";

    /**
     * Table de configuration des modules.
     *
     * @var string
     */
    const MODULES_CONFIGS = "modules_configs";

    /**
     * Table des utilisateurs.
     *
     * @var string
     */
    const USERS = "users";

    /**
     * Table des droits des utilisateurs.
     *
     * @var string
     */
    const USERS_RIGHTS = "users_rights";

}