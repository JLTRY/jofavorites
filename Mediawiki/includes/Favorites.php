<?php
/**
 * 
 */

namespace MediaWiki\Extension\JOFavorites;
use MediaWiki\Parser\PPFrame_Hash;

use JLTRY\LibFavorites\ReadSync;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}


class Favorites{

	public function Render( $input, $args, $parser, PPFrame_HAsh $frame ) {
		if (!array_key_exists('output', $args)) {
			$args['output'] = 'css3treeview';
		}
        $config = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig();
        $args['jsonfile'] = $config->get( "JOFavoritesbookmarksfile" );

		ReadSync::readsync($args, $input);
        $parser->getOutput()->addModules(["ext.favorites.favorites"]);
		return $input;
	}
}

?>