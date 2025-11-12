<?php
/**
 * 
 */

require_once(dirname(__FILE__) . "/../../../weave/favoritessync/lib/readsync.body.php");

class Favorites {

	public static function Setup(Parser &$parser) {
		$parser->setHook( 'favorites', 'Favorites::Render' );
		return true;
	}
	public static function Render( $input, $args, $parser, PPFrame $frame  ) {
		if (!array_key_exists('output', $args)) {
			$args['output'] = 'css3treeview';
		}
		readsync($args, $input);
		//$parser->recursiveTagParse( $input, $frame );
		return $input;
		/*array($input,  "markerType" => 'nowiki' );/*<div>".$input."</div>";*/
	}
}

?>