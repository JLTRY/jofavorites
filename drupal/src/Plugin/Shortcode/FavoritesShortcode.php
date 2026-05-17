<?php
namespace Drupal\shortcode_favorites\Plugin\Shortcode;
define ('_JEXEC', 1);
require_once(dirname(__FILE__) . "/../../../lib/readsync.body.php");
use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;



/**
 * Wraps content in a div with class highlight.
 *
 * @Shortcode(
 *   id = "favorites",
 *   title = @Translation("favorites"),
 *   description = @Translation("Shows favorites")
 * )
 */
      
class FavoritesShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes([
      'output' => 'css3treeview',
	  'name' => '',
	  'path' => '',
	  'keyword' => '',
	  'like' => ''],
      $attributes
    );
	// Filter away empty attributes.
    $attributes = array_filter($attributes);
	$content = "";
    $attributes['jsonfile'] = realpath(dirname(__FILE__) . "/../../../../../../joomla_6.0/files/jofavorites/bookmarks.home2.json");
	readsync($attributes, $content);

	// Build element attributes to be used in twig.
    $element_attributes = [
	   // Default element to div.
      'type' => 'div',
    ];


    $output = [
      '#theme' => 'shortcode_favorites',
    // Not required for rendering, just for extra context.
      '#attributes' => $element_attributes,
	  '#attached' => ['library' => ['shortcode_favorites/styling']],
      '#text' => $content
    ];
	//return $this->render($output);
	$renderer = \Drupal::service('renderer');
    return $renderer->renderRoot($output);
    //return $this->render($output);
	return $content;
	
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[favorites] name=Dv output=css3treeview [/favorites]') . '</strong> ';
    $output[] = $this->t('Shows favorites') . '</p>';
    return implode(' ', $output);
  }
}
