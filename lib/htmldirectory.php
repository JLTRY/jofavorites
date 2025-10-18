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

class htmldirectory extends htmlitem
{
    function __construct($parent, $params)
    {
        parent::__construct($parent, $params);
    }

    function modify($params)
    {
        parent::modify($params);
    }

    function gettype()
    {
        return 'directory';
    }

    function callmethodjson($method, $args)
    {
        $children = array();
        foreach ($this->m_listchildren as $child) {
            $argsi = $args;
            $argsi['encode'] = false;
            $res = call_user_func_array(array($child, $method), $argsi);
            if (is_array($res)) {
                $children[] = $res;
            }
        }
        if (count($children)) {
            $fields = array("id" => $this->m_id,
                            "title" => $this->m_title,
                            "children" => $children);
        } else {
            $fields = true;
        }
        if ($args['encode']) {
            $result = json_encode($fields);
        } else {
            $result = $fields;
        }
        return  $result;
    }

    function geturi($check = false, $encode = true)
    {
        $str = "";
        if ($check == false) {
            return "#";
        } else {
            $str =  $this->callmethodjson('geturi', array("check" => $check, "encode" => $encode));
            return $str;
        }
    }

    function writehtml($level, &$result, $short = false, $maxlevel = 9999, $showkeyword = false, $type = 'all')
    {
        if (($level > 0) && ($level < $maxlevel)) {
            $result .= "<LI>";
            if ($this->m_title != "") {
                htmlitem::writelevel($level, $result);
                $result .= "<H4>" . $this->m_title . "</H4>";
            }
            if (($this->m_description != "") && ($short == false)) {
                htmlitem::writelevel($level, $result);
                $result .= "<DD>" . $this->m_description . "\n";
            }
        }
        if ($level < $maxlevel) {
            if (sizeof($this->m_listchildren)) {
                htmlitem::writelevel($level, $result);
                $result .= "<UL>\n";
            }
            foreach ($this->m_listchildren as $child) {
                $child->writehtml($level + 1, $result, $short, $maxlevel, $showkeyword, $type);
            }
            if (sizeof($this->m_listchildren)) {
                htmlitem::writelevel($level, $result);
                $result .= "</UL>\n";
            }
        }
    }


    function writecss3treeview($level, &$result, $short = false, $maxlevel = 9999, $showkeyword = false, $openlevel = 9999, $minlevel = 1, $type = 'all')
    {
        $this->log_warning("writecss3treeview:htmldirecttory:" . $this->m_title . ":" . $level . ":" . $minlevel . ":" . $maxlevel);
        if (($level >= $minlevel) && ($level < $maxlevel)) {
            htmlitem::writelevel($level, $result);
            $result .= "<LI>";
            if (($this->getlevel() >= 1) && ($level < $maxlevel - 1)) {
                $this->log_warning("writecss3treeview:" . $this->getlevel());
                if ($level < $openlevel) {
                    $result .= "<input type=\"checkbox\" checked=\"checked\" >";
                } else {
                    $result .= "<input type=\"checkbox\" >";
                }
            } else {
                $this->log_warning("writecss3treeview:" . "radio");
                $result .= "<input type=\"radio\" >";
            }
            if ($this->m_title != "") {
                if ($this->m_bmkUri != null) {
                    $result .= "<label><A class=\"htmldirectory\" HREF=\"" . $this->m_bmkUri . "\">" . $this->m_title  . "</A></label>";
                } else {
                    $result .= "<label>" . $this->m_title . "</label>";
                }
            }
        }
        if ($level < $maxlevel) {
            if (sizeof($this->m_listchildren)) {
                htmlitem::writelevel($level, $result);
                $result .= "<UL>\n";
            }
            foreach ($this->m_listchildren as $child) {
                $child->writecss3treeview($level + 1, $result, $short, $maxlevel, $showkeyword, $openlevel, $minlevel, $type);
            }
            if (sizeof($this->m_listchildren)) {
                htmlitem::writelevel($level, $result);
                $result .= "</UL>\n";
            }
        }
        if (($level > 0) && ($level < $maxlevel)) {
            htmlitem::writelevel($level, $result);
            $result .= "</LI>";
        }
    }

    function writewiki($level, &$result)
    {
        if ($level != 0) {
            $result .= "\n";
            for ($i = 0; $i < $level; $i++) {
                $result .= "*";
            }
            $result .=  $this->m_title;
        }
        foreach ($this->m_listchildren as $child) {
            $child->writewiki($level + 1, $result);
        }
    }

    function writewikitext($level, &$result)
    {
        if ($level != 0) {
            $result .= "\n";
            for ($i = 0; $i < $level; $i++) {
                $result .= "*";
            }
            $result .=  $this->m_title;
        }
        foreach ($this->m_listchildren as $child) {
            $child->writewikitext($level + 1, $result);
        }
    }

    function writejson($level, &$result, $full = false, $encode = true)
    {
        $children = array();
        foreach ($this->m_listchildren as $child) {
            if ($full) {
                $res = "";
                $child->writejson($level + 1, $res, $full, false);
                $children[] = $res;
            } else {
                $children[] = $child->getid();
            }
        }
        $fields = array("id" => $this->m_id,
                        "type" => "folder",
                        "title" => $this->m_title,
                        "description" => $this->m_description,
                        "children" => $children,
                        "parentid" => ($this->m_parent) ? $this->m_parent->getid() : -1,
                        "parentName" => ($this->m_parent) ? $this->m_parent->gettitle() : "",
                        "sortindex" => $this->m_sortindex);
        if ($encode) {
            $result .= json_encode($fields);
        } else {
            $result = $fields;
        }
    }

    function writemenuitem($level, &$result)
    {
        if ($level != 0) {
            htmlitem::writelevel($level, $result);
        }
        if ($level != 0) {
            $result .= "new MenuItem(\"#\",\"";
        } else {
            $result .= "new MenuItem(\"#\",\"";
        }

        $result .= $this->m_title;
        $result .= "\",\"\", \"_blank\"\n";
        foreach ($this->m_listchildren as $child) {
            htmlitem::writelevel($level + 2, $result);
            $result .= ",";
            $child->writemenuitem($level + 1, $result);
        }
        if ($level != 0) {
            htmlitem::writelevel($level, $result);
        }
        $result .= ")\n";
    }
}
