<?php
/**
* @copyright Copyright (C) 2025 Jean-Luc TRYOEN. All rights reserved.
* @license GNU/GPL
*
* Version 1.0.0
*
* @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @link        https://www.jltryoen.fr
*/

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

require_once("../lib/readsync.body.php");

$params = array("jsonfile" => "F:\Sites\data\weave\bookmarks.home2.json",
 "name" => "Dv",
 "output" => "css3treeview",
);

$result ="";
readsync($params, $result);
$result .= '<link rel="stylesheet" type="text/css" href="http://www.jltryoen.fr/favorites/css/css3treeview.css">';
echo $result;