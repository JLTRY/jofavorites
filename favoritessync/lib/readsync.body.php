<?php
require_once("htmlitem.php");
require_once("htmldirectory.php");
require_once("htmlfile.php");
require_once("htmlroot.php");




function readsync($_get, &$result, $nouser = DEFAULT_USER)
{
	if (array_key_exists('nouser', $_get))
	{
		$nouser = $_get['nouser'];
	}
	$serfile = FirefoxUsers::getserfile($nouser);
	if ($serfile != NULL)
	{
		$root = htmlroot::load($serfile);
        $options = array(
          'path'           => 'logs',           // path to the logfile ('.' = logfile life in same directory)
          'filename'       => 'log',         // main name, _ and date will be added
          'syslog'         => false,         // true = use system log function (works only in txt format)
          'filePermission' => 0644,          // or 0777
          'maxSize'        => 0.002,         // in MB
          'format'         => 'htm',         // use txt, csv or htm
          'template'       => 'barecss',     // for htm format only: plain, terminal or barecss
          'timeZone'       => 'UTC',         // UTC or what you like
          'dateFormat'     => 'Y-m-d H:i:s', // see http://php.net/manual/en/function.date.php
          'backtrace'      => true,          // true = slower but with line number of call
        );
        if (false) {
            $root->addlogger($options);
        }
		$children = array();
		$result = "";
		if (is_array($_get)== false)
		{
			$result = "error:" . print_r($_get, true);
			return false;
		}
		if (array_key_exists('url', $_get))
		{
			$children = [ new htmlfile(null, array("title" => $_get['url'], "bmkUri" => $_get['url']))];
			$field = 'url';
			$find = true;
		}
		elseif (array_key_exists('path', $_get))
		{
			$arpath = explode("/", $_get["path"]);
			$find = $root->finditemwithpath($arpath, $children);
			$field = 'path';
		}
		elseif (array_key_exists('like', $_get))
		{
			$find = $root->finditemnamed($_get["like"], $children, true);
			$field = 'like';
		}
		elseif (array_key_exists('tag', $_get))
		{
			$find = $root->finditemwithtag($_get["tag"], $children);
			if ($find)
			{
				$dirroot = new htmldirectory(null, array());
				$dirroot->addChildren($children);
				$children = array();
				$children[] = $dirroot;
			}
			$field = 'tag';
		}
		elseif (array_key_exists('keyword', $_get)&& $_get["keyword"] != 1)
		{
			$find = $root->finditemwithkeyword($_get["keyword"], $children);
			$field = 'keyword';
		}
		elseif (array_key_exists('name', $_get))
		{
			$name =  urldecode($_get["name"]);
            if (array_key_exists('url', $_get))
            {
                $children = [ new htmlfile(null, ["title" => $name, "bmkUri" => $_get["url"]]) ];
                $find = true;
            }else {    
                $find = $root->finditemnamed($name , $children);
            }
			$field = 'name';
		}
		elseif (array_key_exists('id', $_get))
		{
			$id =  urldecode($_get["id"]);
			$find = $root->finditemwithid($id , $children);
			$field = 'id';
		}
		else
		{
			$children[] = $root;
			$find = true;
		}
		$output = "html";
		if (array_key_exists('output', $_get))
		{
			$output = $_get['output'];
		}
	
		if (array_key_exists('level', $_get))
		{
			$level = $_get['level'];
		}
		else
		{
			$level = 9999;
		}
		if (array_key_exists('open', $_get))
		{
			$openlevel = $_get['open'];
		}
		else
		{
			$openlevel = 9999;
		}
		if (array_key_exists('keyword', $_get))
		{
			$keyword = true;
		}
		else
		{
			$keyword = false;
		}
		if (array_key_exists('image', $_get))
		{
			$image= $_get['image'];
		}
		else
		{
			$image="File:Nuvola_apps_download_manager.png"; 
		}
        if (array_key_exists('span', $_get)) {
            $style = 'style="display:inline-block;"';
        } else {
            $style = "";
        }
        if (array_key_exists('class', $_get)) {
            $class = $_get['class'];
        } else {
            $class = "";
        }
		if ($find == false)
		{
			$result = "not found :" . $_get[$field] . ":";
			return -1;
		}
		else
		{
			if ($output == 'menuitem')
			{
				$result .= '<BODY BGCOLOR="#F9C784" OnLoad="FolderInit();ShowAll();">';
				$result .= '<script language="javascript" src="scripts/tools.js"></script>';
				$result .= '<script language="javascript" src="scripts/menuitem.js"></script>';
			}
			if ($output == 'menuitemdiv')
			{
				$result .= '<script language="javascript" src="scripts/tools.js"></script>';
				$result .= '<script language="javascript" src="scripts/menuitem.js"></script>';
			}
            //$result .= '<link rel="stylesheet" type="text/css" href="http://www.jltryoen.fr/favorites/css/css3treeview.css">';
			if (preg_match("/css3treeview/", $output))
			{
				$result .= '<div class="css-treeview ' . $class . '" ' . $style . '>';
				//$result .= '<nowiki>';
			}
			foreach($children as $child)
			{
				switch ($output) {
					case 'json':
						$child->writejson(0, $result,false);
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
						$result .= '<script language="javascript">';						$child->writemenuitem(0, $result);
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
				if ((count($children) > 1)&&
					(strpos($output, "css3") === false)) {
					$result .="<br>";
				}
			}
			if ($output == 'menuitem')
			{
				$result .= '<script language="javascript">';
				$result .=  'writetabMenuItemsTop(document);';
				$result .= '</script>';
				$result .=  '</BODY>';
			}
			if ($output == 'menuitemdiv')
			{
				$result .= '<script language="javascript">';
				$result .= 'writetabMenuItemsTop(document);FolderInit();ShowAll();';
				$result .= '</script>';
			}
            if (preg_match("/css3treeview/", $output)) {
				//$result .= '</nowiki>';
				$result .= '</div>';
                //$result .= '</' . $div . '>';
			}
		}
	}
	return 0;
}
?>
