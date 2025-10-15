<?php
require_once("checkurl.php");

class htmlfile extends htmlitem
{
	protected $m_loadInSidebar = false;
	protected $m_pos = 0;
	protected $m_queryId = 0;
	protected $m_tags = array();
	protected $m_keyword = "";
	protected $m_iconUri = "";
	function __construct($parent, $params)
	{
		parent::__construct($parent, $params); 
		$class_vars = get_class_vars('htmlfile');
		foreach($class_vars as $attr => $value)
		{
			$field = substr($attr, 2);
			if (array_key_exists($field, $params)) {
				$this->$attr = $params[$field];
			}
		}
	}

	function gettype()
	{
		return 'file';
	}

	function geturi($check = false, $encode=true)
	{
		if ($check == false) {
			return $this->m_bmkUri;
		}
		else {
			$result = checkurl($this->m_bmkUri);
			//$result = $get_headers2($this->m_bmkUri);
			if ($result == true)
			{
				if ($encode) 
					return json_encode(true);
				else
					return true;//"url OK:" . $this->m_title . ":" . $this->m_bmkUri;
			}
			else
			{
				$result = array(
						"m_id" => $this->m_id,
						"m_title" => $this->m_title,
						"m_path" => $this->getpath(),
						"m_bmkUri" => $this->m_bmkUri,
						"m_checkurl" => $result);
				if ($encode) {
					return json_encode($result);
				}
				else {
					return $result;
				}
			}
		}
	}

	function modify($params)
	{
		if (array_key_exists('url', $params))
		{
			if ($this->m_bmkUri != $params['url'])
			{
				$this->m_bmkUri = $params['url'];
				$this->m_modified = true;
			}
		}
		if (array_key_exists('keyword', $params))
		{
			if ($this->m_keyword != $params['keyword'])
			{
				$this->m_keyword = $params['keyword'];
				$this->m_modified = true;
			}
		}
		parent::modify($params);
	}


	function __toresult()
	{
		return   "<DT><A HREF=\"" . $this->m_bmkUri . "\">" . $this->m_title . "</a>";
	}


	function writehtml($level, &$result, $short=false, $maxlevel=9999, $showkeyword=false, $openlevel=9999)
	{
		if (($this->m_type == 'separator') || ($this->m_type == 'text/x-moz-place-separator'))
		{
			$result .= "<hr>";
		}
		else
		{
			if (($level == 0)&& ($short == false))
				$result .= "<UL>";
			htmlitem::writelevel($level, $result);
			if ($short == false)
				$result .= "<LI>";
			if ($showkeyword && $this->m_keyword!="")
				$keyword = sprintf("[%s]", strtoupper($this->m_keyword));
			else
				$keyword = "";
			if ($short == false)
				$result .= sprintf("%s <sup><small>%s</small></sup>", "<a HREF=\"" . $this->m_bmkUri . "\"" . " icon=\"" . $this->m_iconUri .  "\">" . $this->m_title ."</a>", $keyword);
			elseif ($showkeyword && $this->m_keyword!="")
				$result .= sprintf("%s", "<a HREF=\"" . $this->m_bmkUri. "\"" . " icon=\"" . $this->m_iconUri .  "\"><small>" . $keyword . "</small></a>");
			else
				$result .= sprintf("%s", "<a HREF=\"" . $this->m_bmkUri . "\"" . " icon=\"" . $this->m_iconUri .  "\">" . $this->m_title ."</a>");
			if (($this->m_description != "") && ($short== false))
			{
				htmlitem::writelevel($level, $result);
				$result .= sprintf("<DD>%s\n", $this->m_description);
			}
			if ($short == false)
				$result .= "</LI>";
			if (($level == 0)&& ($short == false))
				$result .= "</UL>";
		}
	}


	function writecss3treeview($level, &$result, $short= false,$maxlevel=9999, $showkeyword=false, $openlevel=1, $minlevel = 1,$type='all')
	{
		if (($type == 'all') || ($type == 'file'))
		{
			$this->log_warning("writecss3treeview:". $this->m_title);
			if (($this->m_type == 'separator') || ($this->m_type == 'text/x-moz-place-separator'))
			{
				$result .= "<hr>";
			}
			else
			{
				if (($level == 0)&& ($short == false))
					$result .= "<UL>";
				htmlitem::writelevel($level, $result);
				if ($short == false)
					$result .= "<LI>";
				if ($showkeyword && $this->m_keyword != "")
					$keyword = sprintf("[%s]", strtoupper($this->m_keyword));
				else
					$keyword = "";
				if ($short == false)
					$result .= sprintf("%s <sup><small>%s</small></sup>", "<a class=\"htmlfile\" HREF=\"" . $this->m_bmkUri . "\"" ." icon=\"" . $this->m_iconUri .  "\">" . $this->m_title . "</a>", $keyword);
				elseif ($showkeyword && $this->m_keyword!="")
					$result .= sprintf("%s", "<a class=\"htmlfile\" HREF=\"" . $this->m_bmkUri . "\"" .  " icon=\"" . $this->m_iconUri .  "\"><sup>" . $keyword . "</sup></a>");
				else
					$result .= sprintf("%s", "<a class=\"htmlfile\" HREF=\"" . $this->m_bmkUri . "\"" . " icon=\"" . $this->m_iconUri .  "\">" . $this->m_title .  " </a>");
				if (($this->m_description != "") && ($short== false))
				{
					htmlitem::writelevel($level, $result);
					$result .= sprintf("<DD>%s\n", $this->m_description);
				}
				if ($short == false)
					$result .= "</LI>";
				if (($level == 0)&& ($short == false))
					$result .= "</UL>";
			}
		}
	}
	function writewiki($level, &$result)
	{
		if ($level != 0)
		{
			$result .= "\n";
			for ($i=0 ; $i < $level ; $i++)
				$result .= "*";
		}
		$result .= sprintf("<a HREF=\"%s\">%s</a>", $this->m_bmkUri, $this->m_title);
		/*sprintf("\n*[%s %s]", $this->m_bmkUri , $this->m_title);*/
		if ($this->m_description != "")
		{
			$result .= sprintf("<br>%s", $this->m_description);
		}
	}

	function writewikitext($level, &$result)
	{
		$result .= "\n";
		for ($i=0 ; $i < $level-1 ; $i++)
			$result .= "*";
		$result .= sprintf("*[%s %s]", $this->m_bmkUri , $this->m_title);
	}

	function writewikiimagelink($image, &$result)
	{
		$result = sprintf("[[%s|link=%s]][%s %s]", $image, $this->m_bmkUri,  $this->m_bmkUri , $this->m_title);

	}
	function writejson($level, &$result, $full, $encode = true)
	{
		$fields = array("id"=> $this->m_id,
						"type" => $this->m_type,
						"title" => $this->m_title,
						"parentName" => $this->m_parent->gettitle(),
						"bmkUri" => $this->m_bmkUri,
						"tags" => $this->m_tags,
						"keyword" => $this->m_keyword,
						"description" => $this->m_description,
						"loadInSidebar" => false,
						"parentid" => $this->m_parent->getid(),
						"sortindex" => $this->m_sortindex
						);
		if ($encode)
			$result .= json_encode($fields);
		else
			$result = $fields;
	}

	function writemenuitem($level, &$result)
	{
		$result .="new MenuItem(\"";
		$result .= $this->m_bmkUri;
		$result .= "\",\"";
		$result .= $this->m_title;
		$result .= "\",\"\", \"_blank\")\n";
	}


}
?>