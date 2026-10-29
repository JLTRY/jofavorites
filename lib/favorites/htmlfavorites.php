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

require_once("htmlitem.php");
require_once("htmldirectory.php");
require_once("htmlfile.php");
require_once("htmlroot.php");


class HtmlFavorites
{
    public static function convertNodeToJson($node, $parent) {
        $out = false;
        
        // Ajouter le nom du tag
        if (in_array(strtoupper($node->nodeName), array("DL", "DT", "H3", "A"))) {
            //print("=>node " . $node->nodeName . "parent :". (($parent)?$parent->gettitle(): "null") . "<br>"); 
            switch (strtoupper($node->nodeName)) {
                case "DL":
                    break;
                case "H3":
                    if ($parent == null) {
                        $htmlitem = new htmlroot($parent, array());
                    } else {
                        $htmlitem = new htmldirectory($parent, array());
                    }
                    $htmlitem->modify(array("title" => trim($node->nodeValue)));
                    $parent = $htmlitem;
                    break;
                case "A":
                    $htmlitem = new htmlfile($parent, array("title" => trim($node->nodeValue)));
                    break;
                default:
                    break;
            }
        }
        // Ajouter les attributs
        if ($out && $node->hasAttributes()) {
            $attributes= array();
            foreach ($node->attributes as $attr) {
                if (in_array(strtoupper($attr->nodeName), array("HREF", "ICON_URI"))) {
                    $attributes[strtoupper($attr->nodeName)] = $attr->nodeValue;
                }
            }
            if (array_key_exists("HREF",  $attributes)) {
                $htmlitem->modify(array("url" => $attributes["HREF"]));
            }
        }
        // Ajouter les nœuds enfants
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $newparent = self::convertNodeToJson($child, $parent);
                if ($newparent != $parent) {
                    $parent = $newparent;
                }
                if ((strtoupper($child->nodeName) == "DL") && ($parent)) {
                    $parent = $parent->getparent();
                }
            }
        }
        if (in_array(strtoupper($node->nodeName), array("DL", "DT", "H3", "A", "P"))) {
            //print("<=node " . $node->nodeName .":parent :". (($parent)?$parent->gettitle(): "null") . "<br>");
        }
        return $parent;
    }

    public static function converthtmltoroot($file)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(file_get_contents($file), LIBXML_NOERROR);
        libxml_clear_errors(); // Efface les erreurs capturées
        $root = new htmlroot(-1, "");
        self::convertNodeToJson($dom->documentElement, $root);
        return $root;
    }
}
