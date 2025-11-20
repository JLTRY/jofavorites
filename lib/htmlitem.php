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

require_once("log.inc.php");
$tab = "&nbsp&nbsp&nbsp&nbsp";
/**
    http://stackoverflow.com/questions/3573313/php-remove-object-from-array
     * Remove each instance of an object within an array (matched on a given property, $prop)
     * @param array $array
     * @param mixed $value
     * @param string $prop
     * @return array
 */
function array_remove_object(&$array, $value, $prop)
{
    return array_filter($array, function ($a) use ($value, $prop) {

        return $a->$prop !== $value;
    });
}

class htmlitem
{
    protected $m_title = "";
    protected $m_type;
    protected $m_description = "";
    protected $m_bmkUri = null;
    protected $m_parent;
    protected $m_listchildren;
    public $m_id = -1;
    protected $m_modified = false;
    protected $m_dateAdded;
    protected $m_sortindex = 10;
    protected $m_parentName;
    protected $m_parentid;
    protected $m_hasDupe = false;
    protected $m_logger = null;
    function __construct($parent, $params)
    {
        $this->m_listchildren = array();
        $this->m_parent = $parent;
        if ($this->m_parent != null) {
            $this->m_parentName = $parent->gettitle();
            $this->m_parentid = $parent->getid();
        }
        if ($parent != null) {
            $parent->addChild($this);
        }
        $class_vars = get_class_vars('htmlitem');
        foreach ($class_vars as $attr => $value) {
            $field = substr($attr, 2);
            if (array_key_exists($field, $params)) {
            //echo "adding" . $attr . ":" . $params[$field] . "\n";
                $this->$attr = $params[$field];
            }
        }
    }

    function modify($params)
    {
        if (array_key_exists('title', $params)) {
            if ($this->m_title != $params['title']) {
                $this->m_title = $params['title'];
                $this->m_modified = true;
            }
        }
        if (array_key_exists('description', $params)) {
            if ($this->m_description != $params['description']) {
                $this->m_description = $params['description'];
                $this->m_modified = true;
            }
        }
        if (array_key_exists('sortindex', $params)) {
            if ($this->m_sortindex != $params['sortindex']) {
                $this->m_sortindex = $params['sortindex'];
                $this->m_modified = true;
            }
        }
    }

    function delete()
    {
        if ($this->m_parent != null) {
            $this->m_parent->removechild($this);
        }
    }
    function gettitle()
    {
        return $this->m_title;
    }
    function getid()
    {
        return $this->m_id;
    }

    function ismodified()
    {
        return $this->m_modified;
    }
    function addChild($child)
    {
        $this->m_listchildren[] = $child;
        $child->setparent($this);
    }

    function removeChild($child)
    {
        array_remove_object($this->m_listchildren, $child->getid(), 'm_id');
    }

    function addChildren($children)
    {
        $this->m_listchildren = $children;
    }
    function getids(&$map)
    {
        $map[$this->getid()] = $this;
        foreach ($this->m_listchildren as $child) {
            $child->getids($map);
        }
    }
    function setparent($parent)
    {
        $this->m_parent = $parent;
    }

    function getparent()
    {
        return $this->m_parent;
    }

    function getpath()
    {
        if ($this->m_parent) {
            return $this->m_parent->getpath() . '/' .  $this->gettitle();
        } else {
            return $this->gettitle();
        }
    }

    function getchildren()
    {
        return $this->m_listchildren;
    }

    function getlevel($level = 0)
    {
        if (count($this->m_listchildren) == 0) {
            return $level;
        }
        $leveli = $level;
        foreach ($this->m_listchildren as $child) {
            $leveli = max($child->getlevel($level), $leveli);
        }
        return $leveli + 1;
    }

    function removeallchildren()
    {
        foreach ($this->m_listchildren as $child) {
            if ($child->getparent() == $this) {
                $child->setparent(null);
            }
        }
        $this->m_listchildren = array();
    }

    function writelevel($level, &$result)
    {
        if ($level >= 0) {
            for ($i = 0; $i < $level; $i++) {
                $result .= "\t";
            }
        }
    }

    function buildmapid1(&$map)
    {
        $map[$this->m_id] = $this;
        foreach ($this->m_listchildren as $child) {
            $child->buildmapid1($map);
        }
    }

    function finditemnamed($title, &$array, $match = false, $immediate = 9999)
    {
        $ret = false;
        if (
            ($immediate >= 0) &&
            ((($match == false) && ($this->m_title == $title)) ||
            (($match == true) && (preg_match("/" . $title . "/", $this->m_title))))
        ) {
            $array[] = $this;
            $ret = true;
        }
        if ($ret == false) {
            foreach ($this->m_listchildren as $child) {
                $find = $child->finditemnamed($title, $array, $match, $immediate - 1);
                if ($find) {
                    $ret = $find;
                }
            }
        }
        return $ret;
    }


    function finditemwithid($id, &$array)
    {
        $ret = false;
        if ($this->m_id == $id) {
            $array[] = $this;
            $ret = true;
        }
        if ($ret == false) {
            foreach ($this->m_listchildren as $child) {
                $find = $child->finditemwithid($id, $array);
                if ($find) {
                    $ret = $find;
                }
            }
        }
        return $ret;
    }

    function finditemwithpath(&$path, &$array, $level = 9999)
    {
        $ret = false;
        $arraychild = array();
        $ret = $this->finditemnamed($path[0], $arraychild, false, $level);
        if (count($path) == 1) {
            foreach ($arraychild as $child) {
                array_push($array, $child);
            }
        } elseif ($ret == true) {
            array_shift($path);
            foreach ($arraychild as $child) {
                $ret1 = $child->finditemwithpath($path, $array, 1);
            }
            if ($ret1) {
                $ret = true;
            }
        }
        return $ret;
    }

    function finditemid($id, $level = 0)
    {
        $ret = null;
        if (($this->m_id == $id)) {
            $ret = $this;
        } elseif ($level > 50) {
            return null;
        }
        foreach ($this->m_listchildren as $child) {
            $ret = $child->finditemid($id, $level + 1);
            if ($ret != null) {
                break;
            }
        }
        return $ret;
    }



    function finditemwithtag($tag, &$array)
    {
        $ret = false;
        if (
            property_exists($this, 'm_tags') &&
            is_array($this->m_tags) &&
            in_array($tag, $this->m_tags)
        ) {
            $array[] = $this;
            $ret = true;
        }
        foreach ($this->m_listchildren as $child) {
            $find = $child->finditemwithtag($tag, $array);
            if ($find) {
                $ret = $find;
            }
        }
        return $ret;
    }

    function finditemwithkeyword($keyword, &$array)
    {
        $ret = false;
        if (
            property_exists($this, 'm_keyword') &&
            ($keyword ==  $this->m_keyword)
        ) {
            $array[] = $this;
            $ret = true;
        }
        foreach ($this->m_listchildren as $child) {
            $find = $child->finditemwithkeyword($keyword, $array);
            if ($find) {
                $ret = $find;
            }
        }
        return $ret;
    }



    function getitemmodified(&$array)
    {
        $ret = false;
        if ($this->ismodified()) {
            $array[] = $this;
            $ret = true;
        }
        foreach ($this->m_listchildren as $child) {
            $find = $child->getitemmodified($array);
            if ($find) {
                $ret = $find;
            }
        }
        return $ret;
    }
}
