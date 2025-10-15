<?php
require_once("readsync.body.php");

$params = array("jsonfile" => "F:\Sites\data\weave\bookmarks.home2.json",
 "name" => "Dv",
 "output" => "html"
);

$results ="";
readsync($params, $results);
echo $results;