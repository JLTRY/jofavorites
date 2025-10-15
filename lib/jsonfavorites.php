<?php
require_once("htmlitem.php");
require_once("htmldirectory.php");
require_once("htmlfile.php");
require_once("htmlroot.php");
require_once("log.inc.php");

class JsonFavorites {
	private $log;
	private $file;
	private $jsoncontent;

	public function __construct($file, $log=false) {
		$this->log = $log;
		$this->file = $file;
		$this->jsoncontent = array();
	}

	public function get_contents() {
		if (!file_exists($this->file)) {
			echo "file does not exist" + $this->file;
		}
		if ($this->jsoncontent == array()) {
			$str = file_get_contents($this->file);
			$this->jsoncontent = json_decode($str, true);
		}
		return $this->jsoncontent;
	}

	public function get_accountkeys() {
		return array();
	}

	public function getinfo($type) {
		$this->get_contents();
		if ($type == "collections") {
			if (array_key_exists("lastModified", $this->jsoncontent)) {
				return (object)array("bookmarks" =>  $this->jsoncontent["lastModified"]);
			}
		}
		return (object)array();
	}
	
	public function getusername() {
		return "";
	}

	public function getcollection($type) {
		$this->get_contents();
		if ($type == 'bookmarks') {
			// logscreen("read from" . $this->file, $this->log);
			if (array_key_exists('children', $this->jsoncontent))
				return $this->jsoncontent['children'][1];
			if (array_key_exists('bookmarks', $this->jsoncontent))
				return $this->jsoncontent['bookmarks']['toolbar'];
			return array();
		}
	}

	public static function converttofile($bookmark, $parent)
	{
		$file = new htmlfile($parent, $bookmark);
		/*foreach($bookmark as $key => $value)
		{
			if (!property_exists($file, 'm_' . $key))
				echo "does not exist" . $key;
		}*/
		return $file;
	}


	 public static function converttoitemlist($bookmark, $parent, $mapbookmark)
	{
		if (array_key_exists('children', $bookmark))
		{
			foreach($bookmark['children'] as $idchild)
			{
				$child = null;
				if (array_key_exists($idchild, $mapbookmark))
					$child = $mapbookmark[$idchild];
				/*logdump($child);*/
				if (isset($child))
				{
					if (array_key_exists('children', $child))
						converttodirectory($child, $parent, $mapbookmark);
					else
						converttofile($child, $parent);
				}
			}
		}
		else
		{
			my_log("error for" . print_r($bookmark, true));
		}
	}


	public static function converttodirectory($bookmark, $parent, $mapbookmark, $recurse = true)
	{
		$directory = new htmldirectory($parent, $bookmark);
		if ($recurse)
			self::converttoitemlist($bookmark, $directory, $mapbookmark);
		else
			return $directory;
		//logdump(gettype($mapbookmark[$id]));
	}


	public static function converttoroot($jsonarray, $root)
	{
		$listroot = array();
		$mapbookmark = array();
		foreach ($jsonarray as $jsonbookmark)
		{
			logdump($jsonbookmark);
			$bookmark = get_object_vars($jsonbookmark);
			if (array_key_exists('parentName', $bookmark)&& 
				(($bookmark['parentName'] == "root") ||
				 ($bookmark['parentName'] == "")))
			{
				$listroot[] = $bookmark;
			}
			if (array_key_exists('id', $bookmark))
				$mapbookmark[$bookmark['id']] = $bookmark;
		}
		foreach($listroot as $bookmark)
		{
			self::converttodirectory($bookmark, $root, $mapbookmark);
		}
	}



	 public static function convertjsontoitemlist($bookmark, $parent)
	{
		if (array_key_exists('children', $bookmark))
		{
			foreach($bookmark['children'] as $child)
			{
				if (array_key_exists('children', $child)) {
					self::convertjsontodirectory($child, $parent);
				}
				else {
					if (array_key_exists('uri', $child))
						$child['bmkUri'] = $child['uri'];
					self::converttofile($child, $parent);
				}
			}
		}
		else
		{
			my_log("error for" . print_r($bookmark, true));
		}
	}


	public static function convertjsontodirectory($bookmark, $parent, $recurse = true)
	{
		$directory = new htmldirectory($parent, $bookmark);
		if ($recurse)
			self::convertjsontoitemlist($bookmark, $directory);
		else
			return $directory;
	}

	public static function convertjsontoroot($file)
	{
		$jsonfile = new JsonFavorites($file);
        $jsonfile->get_contents();
		$jsonbookmarks = $jsonfile->getcollection("bookmarks");
		$root = new htmlroot("", -1, array());
		self::convertjsontodirectory($jsonbookmarks, $root);
		return $root;
	}
}