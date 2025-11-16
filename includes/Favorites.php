<?php
/**
 * 
 */

namespace MediaWiki\Extension\JOFavorites;
use MediaWiki\Parser\PPFrame_Hash;
define ('_JEXEC', 1);

class Favorites{


	public function Render( $input, $args, $parser, PPFrame_HAsh $frame ) {
		if (!array_key_exists('output', $args)) {
			$args['output'] = 'css3treeview';
		}
        $config = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig();
        $args['jsonfile'] = $config->get( "JOFavoritesbookmarksfile" );
        require_once(dirname(__FILE__) . "/../lib/readsync.body.php");
		\readsync($args, $input);
        $parser->getOutput()->addModules(["ext.favorites.favorites"]);
		return $input;
	}
}

?>