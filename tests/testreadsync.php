<?php
require_once("../lib/readsync.body.php");

$params = array("jsonfile" => "F:\Sites\data\weave\bookmarks.home2.json",
 "name" => "Dv",
 "output" => "css3treeview",
);

$result ="";
readsync($params, $result);
$result .= '<link rel="stylesheet" type="text/css" href="http://www.jltryoen.fr/favorites/css/css3treeview.css">';
echo $result;