<?php
/**
 * 
 */

namespace MediaWiki\Extension\JOFavorites;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Extension\JOFavorites\Favorites;
use MediaWiki\Extension\JOFavorites\FavoritesParser;



class FavoritesHooks implements ParserFirstCallInitHook, BeforePageDisplayHook {

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param \OutputPage $out
	 * @param \Skin $skin
	 */
    public function onBeforePageDisplay( $out, $skin ): void {
        $config = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig();
		$uriroot = $config->get( "JOFavoritesuriroot" );
        if ($uriroot == null) {
            $uriroot = "http://joomla.jltryoen.fr/";
        }
        $script = "<script>var uriroot=\"" . $uriroot  ."\";</script>";
        $out->addHeadItem("itemName", $script);
    }


    /**
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
     *
     * @param Parser $parser
     */
    public function onParserFirstCallInit( $parser ): void {
        $parser->setHook( 'favorites', [ new Favorites(), 'Render' ] );
        $parser->setFunctionHook( 'favorites', [new FavoritesParser(), 'RenderFunction'] );
    }
}