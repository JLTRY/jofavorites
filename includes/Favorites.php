<?php
/**
 * 
 */

namespace MediaWiki\Extension\JOFavorites;
use MediaWiki\Parser\PPFrame_Hash;
define ('_JEXEC', 1);

class Favorites{


	public static function Render( $input, $args, $parser, PPFrame_HAsh $frame ) {
		if (!array_key_exists('output', $args)) {
			$args['output'] = 'css3treeview';
		}
        $args['jsonfile'] = dirname(__FILE__) . "/../../../data/weave/bookmarks.home2.json";
        require_once(dirname(__FILE__) . "/../lib/readsync.body.php");
		\readsync($args, $input);
        $parser->getOutput()->addModules(["ext.favorites.favorites"]);
		return $input;
	}
}

?>