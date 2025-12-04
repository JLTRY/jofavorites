<?php

namespace MediaWiki\Extension\JOFavorites;
if (!defined("_JEXEC")){
    define ('_JEXEC', 1);
}

class FavoritesParser {


	public static function extractOptions( array $options ) {
		$results = array();
		foreach ( $options as $option ) {
			$pair = explode( '=', $option );
			if ( count( $pair ) == 2 ) {
					$name = trim( $pair[0] );
					$value = trim( $pair[1] );
					$results[$name] = $value;
			}
		}
		//Now you've got an array that looks like this:
		//	  [foo] => bar
		//	  [apple] => orange

		return $results;
	}



	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value
	 *
	 * @param array string $options
	 * @return array $results
	 */

	public function RenderFunction( $parser ) {
		//Suppose the user invoked the parser function like so:
		//{{#favorites:foo=bar|apple=orange}}
		$opts = array();
		// Argument 0 is $parser, so begin iterating at 1
		for ( $i = 1; $i < func_num_args(); $i++ ) {
			$opts[] = func_get_arg( $i );
		}
		//The $opts array now looks like this:
		//	  [0] => 'foo=bar'
		//	  [1] => 'apple=orange'

		//Now we need to transform $opts into a more useful form...
		$options = self::extractOptions( $opts );

		if (!array_key_exists('output', $options)) {
			$options['output'] = 'css3treeview';
		}
        $config = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig();
        $options['jsonfile'] = $config->get( "JOFavoritesbookmarksfile" );
        require_once(dirname(__FILE__) . "/../lib/readsync.body.php");
		\readsync($options, $input);
        $parser->getOutput()->addModules(["ext.favorites.favorites"]);
		if (($options['output'] == 'mediawiki')||
			($options['output'] == 'link') || 
			($options['output'] == 'title') ||
			($options['output'] == 'wikiimagelink')) 
		{
			return array( $input, 'noparse' => false, 'isHTML' => false);
		}
		else {
			return array( $input, 'noparse' => true, 'isHTML' => true ,"markerType" => 'nowiki');
		}
	}
}
?>