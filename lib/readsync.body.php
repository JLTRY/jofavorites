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
require_once("jsonfavorites.php");
require_once("htmlfavorites.php");

function readsync($params, &$result)
{
    if (array_key_exists('jsonfile', $params)) {
        $jsonfile = $params['jsonfile'];
        if (!file_exists($jsonfile)) {
            $result = "File does not exist " . $jsonfile;
            return;
        }
        $root = JsonFavorites::convertjsontoroot($jsonfile);
    } elseif (array_key_exists('htmlfile', $params)) {
        $htmlfile = $params['htmlfile'];
        if (!file_exists($htmlfile)) {
            $result = "File does not exist " . $htmlfile;
            return;
        }
        $root = HtmlFavorites::converthtmltoroot($htmlfile);
    } elseif (array_key_exists('serfile', $params)) {
        $serfile = $params['serfile'];
        if (!file_exists($serfile)) {
            $result = "File does not exist " . $serfile;
            return;
        } 
        $root = htmlroot::load($serfile);
        if ($root == null) {
            $result = "root is null : $serfile";
            return;
        }
    }
    $children = array();
    $result = "";
    if (is_array($params) == false) {
        $result = "error:" . $params;
        return false;
    }
    if (array_key_exists('url', $params)) {
        $children = [ new htmlfile(null, array("title" => $params['url'], "bmkUri" => $params['url']))];
        $field = 'url';
        $find = true;
    } elseif (array_key_exists('path', $params)) {
        $arpath = explode("/", $params["path"]);
        $find = $root->finditemwithpath($arpath, $children);
        $field = 'path';
    } elseif (array_key_exists('like', $params)) {
        $find = $root->finditemnamed($params["like"], $children, true);
        $field = 'like';
    } elseif (array_key_exists('tag', $params)) {
        $find = $root->finditemwithtag($params["tag"], $children);
        if ($find) {
            $dirroot = new htmldirectory(null, array());
            $dirroot->addChildren($children);
            $children = array();
            $children[] = $dirroot;
        }
        $field = 'tag';
    } elseif (array_key_exists('keyword', $params) && $params["keyword"] != 1) {
        $find = $root->finditemwithkeyword($params["keyword"], $children);
        $field = 'keyword';
    } elseif (array_key_exists('name', $params)) {
        $name = $params["name"];
        if (array_key_exists('url', $params)) {
            $children = [ new htmlfile(null, ["title" => $name, "bmkUri" => $params["url"]]) ];
            $find = true;
        } else {
            $find = $root->finditemnamed($name, $children);
        }
        $field = 'name';
    } elseif (array_key_exists('id', $params)) {
        $id =  urldecode($params["id"]);
        $find = $root->finditemwithid($id, $children);
        $field = 'id';
    } else {
        $children[] = $root;
        $find = true;
    }
    $output = "html";
    if (array_key_exists('output', $params)) {
        $output = $params['output'];
    }

    if (array_key_exists('level', $params)) {
        $level = $params['level'];
    } else {
        $level = 9999;
    }
    if (array_key_exists('open', $params)) {
        $openlevel = $params['open'];
    } else {
        $openlevel = 9999;
    }
    if (array_key_exists('keyword', $params)) {
        $keyword = true;
    } else {
        $keyword = false;
    }
    if (array_key_exists('image', $params)) {
        $image = $params['image'];
    } else {
        $image = "File:Nuvola_apps_download_manager.png";
    }
    if (array_key_exists('head', $params)) {
        $head =  $params['head'];
    } else {
        $head = "";
    }
    if (array_key_exists('class', $params)) {
        $class = $params['class'];
    } else {
        $class = "";
    }
    if ($find == false) {
        $result = "not found :" . $params[$field] . ":";
        return -1;
    } else {
        if ($output == 'menuitem') {
            $result .= '<BODY BGCOLOR="#F9C784" OnLoad="FolderInit();ShowAll();">';
            $result .= '<script language="javascript" src="scripts/tools.js"></script>';
            $result .= '<script language="javascript" src="scripts/menuitem.js"></script>';
        }
        if ($output == 'menuitemdiv') {
            $result .= '<script language="javascript" src="scripts/tools.js"></script>';
            $result .= '<script language="javascript" src="scripts/menuitem.js"></script>';
        }
        //$result .= '<link rel="stylesheet" type="text/css" href="http://www.jltryoen.fr/favorites/css/css3treeview.css">';
        if (preg_match("/css3treeview/", $output)) {
            $result .= '<div class="css-treeview ' . $class . '">' . $head;
        }
        foreach ($children as $child) {
            switch ($output) {
                case 'json':
                    $child->writejson(0, $result, false);
                    break;
                case 'jsonfull':
                    $child->writejson(0, $result, true);
                    break;
                case 'wiki':
                    $child->writewiki(0, $result);
                    break;
                case 'wikitext':
                    $child->writewikitext(0, $result);
                    break;
                case 'link':
                    $result = $child->geturi();
                    break;
                case 'checkurl':
                                //error_reporting(-1);
                    ini_set('max_execution_time', 3600);
                    $result = "checkurl";
                    $result = $child->geturi(true, true);
                    break;
                case 'wikiimagelink':
                    $child->writewikiimagelink($image, $result);
                    break;
                case 'title':
                    $result = $child->gettitle();
                    break;
                case 'html':
                    $child->writehtml(0, $result, false, $level, $keyword);
                    break;
                case 'htmlkw':
                    $child->writehtml(0, $result, false, $level, true);
                    break;
                case 'htmlshort':
                    $child->writehtml(0, $result, true, $level, $keyword);
                    break;
                case 'htmlshortkw':
                    $child->writehtml(0, $result, true, $level, true);
                    break;
                case 'menuitem':
                    $result .= '<script language="javascript">';
                    $child->writemenuitem(0, $result);
                    $result .= '</script>';
                    break;
                case 'menuitemdiv':
                    $result .= '<script language="javascript">';
                    $child->writemenuitem(0, $result);
                    $result .= '</script>';
                    break;
                case 'css3treeview':
                    $child->writecss3treeview(0, $result, false, $level, $keyword, $openlevel);
                    break;
                case 'css3treeviewshort':
                    $child->writecss3treeview(0, $result, true, $level, $keyword, $openlevel);
                    break;
                case 'css3treeviewkw':
                    $child->writecss3treeview(0, $result, false, $level, true, $openlevel);
                    break;
                case 'css3link':
                    $result .= '<span class="css-treeview">';
                    $child->writecss3treeview(0, $result, true, $level, false, $openlevel);
                    $result .="</span>";
                    break;
                case 'css3linkkw':
                    $result .= '<span class="css-treeview">';
                    $child->writecss3treeview(0, $result, true, $level, $keyword, $openlevel);
                    $result .="</span>";
                    break;
            }
            if (
                (count($children) > 1) &&
                (strpos($output, "css3") === false)
            ) {
                $result .= "<br>";
            }
        }
        if ($output == 'menuitem') {
            $result .= '<script language="javascript">';
            $result .=  'writetabMenuItemsTop(document);';
            $result .= '</script>';
            $result .=  '</BODY>';
        }
        if ($output == 'menuitemdiv') {
            $result .= '<script language="javascript">';
            $result .= 'writetabMenuItemsTop(document);FolderInit();ShowAll();';
            $result .= '</script>';
        }
        if (preg_match("/css3treeview/", $output)) {
           $result .= '</div>';
        }
    }
    return 0;
}
