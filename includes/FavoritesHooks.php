<?php
/**
 * 
 */

namespace MediaWiki\Extension\JOFavorites;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Extension\JOFavorites\Favorites;
use MediaWiki\Extension\JOFavorites\FavoritesParser;



class FavoritesHooks implements ParserFirstCallInitHook {

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ): void {
        wfDebugLog( 'bootstrap5', "onParserFirstCallInit" );
		$parser->setHook( 'favorites', [ new Favorites(), 'Render' ] );
        $parser->setFunctionHook( 'favorites', [new FavoritesParser(), 'RenderFunction'] );
        wfDebugLog( 'favorites', "onParserFirstCallInit 2" );
	}
}