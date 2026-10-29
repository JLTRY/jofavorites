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

class htmlroot extends htmldirectory
{
    protected $m_user;
    protected $m_lastmodification;
    protected $m_map;
    protected $m_accountkeys;

    function __construct($user, $lastmodification, $accountkeys = array(), $title = "root", $url = "")
    {
        $this->m_user = $user;
        $this->m_lastmodification = $lastmodification;
        $this->m_map = array();
        $this->m_accountkeys = $accountkeys;
        parent::__construct(null, array("type" => "root", "title" => $title, "bmkUri" => $url));
    }

    function getaccountkeys()
    {
        return $this->m_accountkeys;
    }

    function setaccountkeys($ar)
    {
        $this->m_accountkeys = $ar;
    }
    public function __sleep()
    {
        return array("m_user",
        "m_lastmodification",
        "m_title",
        "m_description",
        "m_parent",
        "m_listchildren",
        "m_id",
        "m_modified",
        "m_sortindex",
        "m_accountkeys");
    }


    function getlastmodification()
    {
        return $this->m_lastmodification;
    }

    function setlastmodification($last)
    {
        $this->m_lastmodification = $last;
    }



    static function load($file)
    {
        $root = unserialize(file_get_contents($file));
        return $root;
    }
    function save($serfile)
    {
        file_put_contents($serfile, serialize($this));
    }

    function savejson($serfile)
    {
        file_put_contents($serfile, json_encode($this));
    }
}
