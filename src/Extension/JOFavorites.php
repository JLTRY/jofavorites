<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.JOFavorites
 * @copyright  (C) 2025 JL TRYOEN <https://www.jltryoen.fr>
 *
 * Version 1.0.1
 *
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.jltryoen.fr
*/

namespace JLTRY\Plugin\Content\JOFavorites\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\Event\Event;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Utility\Utility;
use Joomla\Event\SubscriberInterface;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

define('PF_REGEX_FAV_PATTERN', "#{favorites (.*?)}#s");



/**
 * JOFavorites Content Plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.JOCodeHighlight
 */
class JOFavorites extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare'  => 'onContentPrepare',
            'onAjaxJofavorites' => 'onAjaxJOFavorites'
            
        ];
    }

    /**
     * @param   string    The context of the content being passed to the plugin.
     * @param   object    The article object.  Note $article->text is also available
     * @param   object    The article params
     * @param   integer  The 'page' number
     */
    public function onContentPrepare(ContentPrepareEvent $event)
    {
        //Escape fast
        if (!$this->getApplication()->isClient('site')) {
            return;
        }

        if (!$this->params->get('enabled', 1)) {
            return true;
        }
        // use this format to get the arguments for both Joomla 4 and Joomla 5
        // In Joomla 4 a generic Event is passed
        // In Joomla 5 a concrete ContentPrepareEvent is passed
        [$context, $article, $params, $page] = array_values($event->getArguments());
        // Simple performance check to determine whether bot should process further.
		if ( strpos( $article->text, '{favorites' ) === false ) {
			return true;
		}
		preg_match_all(PF_REGEX_FAV_PATTERN, $article->text, $matches);
        if (!$this->params->get('jsonfileupload', "")) {
            $this->getApplication()->enqueueMessage("Please fill parameter jsonfile", "error");
            return true;
        }
		// Number of plugins
		$count = count($matches[0]);
		// plugin only processes if there are any instances of the plugin in the text
		if ($count) {
			for ($i = 0; $i < $count; $i++)
			{
				$_result = array("jsonfile" => str_replace("/", DIRECTORY_SEPARATOR, JPATH_ROOT . DIRECTORY_SEPARATOR .$this->params->get('jsonfileupload')));
				if (@$matches[1][$i]) {
					$inline_params = $matches[1][$i];
					$pairs = explode(';', trim($inline_params));
					foreach ($pairs as $pair) {
						$pos = strpos($pair, "=");
						$key = substr($pair, 0, $pos);
						$value = substr($pair, $pos + 1);
						$_result[$key] = $value;
					}
					$favorites = $this->favorites($_result);
					//$startfavorite = "<!-- start favorites " . $matches[1][$i] . $favorites . "} -->\n";
					//$endfavorite = "\n<!-- end favorites " . $matches[1][$i] . "} -->";
					$p_content = $favorites;
					
					$article->text = str_replace("{favorites " . $matches[1][$i] . "}", $p_content, $article->text);
				}
			}

		}
		else
		{
			$row->text = str_replace("{favorites", "erreur de syntaxe: {favorites sname=xxx output=wiki|html like=yyy path=zzz}", $row->text);
		}
		return true;
	}

	function readsync($_params, &$content) {
		require_once(dirname(__FILE__) . "/../../lib/readsync.body.php");
		return readsync($_params, $content);
	}

	function getsync($_params, &$content){
		$timeout = null;
		$username = $this->params->get('user');
		$password = $this->params->get('password');
		$url = $this->params->get('url'). "?" . http_build_query($_params);
		$options = new \Joomla\Registry\Registry;
		$options->set('transport.curl',
			array(
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_USERPWD => "$username:$password",
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC
			)
		);
		$options->set('follow_location',true);
		$headers = array();
		try{
			$response = JHttpFactory::getHttp($options)->get($url, $headers, $timeout);
		}
		catch (UnexpectedValueException $e)
		{
			throw new RuntimeException('Could not get data from remote server: ' . $e->getMessage(), 500);
		}
		catch (RuntimeException $e)
		{
			// There was an error connecting to the server or in the post request
			throw new RuntimeException('Could not connect to remote server: ' . $e->getMessage(), 500);
		}
		catch (Exception $e)
		{
			// An unexpected error in processing; don't let this failure kill the site
			throw new RuntimeException('Unexpected error connecting to server: ' . $e->getMessage(), 500);
		}
		$content .= $response->getBody();
	}
	
	/**
		* Function to insert Favorites world
		*
		* Method is called by the onContentPrepare or onPrepareContent
		*
		* @param string The text string to find and replace
	*/       
	function favorites( $_params )
	{
		$content = "new favorites<br>";
		if (is_array( $_params )== false)
		{
			return  "errorf:" . print_r($_params, true);
		}
		if (!array_key_exists('output', $_params)) {
			$_params['output'] = "css3treeview";
		}
		/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
		/* in ConsoleApplication it does not exist); */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->getRegistry()->addRegistryFile('media/plg_content_jofavorites/joomla.asset.json');
		$wa->usePreset("plugin.jofavorites");
		//Log::add('favorites:'. print_r($_params, true), Log::WARNING, 'favoris');
		if ($this->params->get('external')) {
			$this->getsync($_params, $content);
		} else {
			$this->readsync($_params, $content);
		}
		//Log::add('favorites:=>:'. print_r($content, true), Log::WARNING, 'favoris');
		return $content;
	}



    public function onAjaxJOFavorites(Event $event): void
    {
        
        // Permissions check: Only allow backend users, etc.
        $app = \Joomla\CMS\Factory::getApplication();
        //$app->enqueueMessage("onAjaxJoFavoritesUpload", "warning");
        if (!$app->isClient('administrator')) {
            echo new \Joomla\CMS\Response\JsonResponse(null, 'Forbidden', true);
            $app->close();
        }

        $input = $app->input;
        $file = $input->files->get('file', null, 'raw');
        if (!$file || !isset($file['tmp_name'])) {
            //$app->enqueueMessage("onAjaxJoFavoritesUpload no file uploaded", "warning");
            echo new \Joomla\CMS\Response\JsonResponse(null, 'No file uploaded', true);
            $app->close();
        }

        $uploadDir = JPATH_ROOT . '/files/jofavorites/';
        \Joomla\CMS\Filesystem\Folder::create($uploadDir);

        $filename = \Joomla\CMS\Filesystem\File::makeSafe($file['name']);
        $dest = $uploadDir . $filename;

        if (\Joomla\CMS\Filesystem\File::upload($file['tmp_name'], $dest)) {
            $relativePath = 'files/jofavorites/' . $filename;
            //$app->enqueueMessage("onAjaxJoFavoritesUpload " . $relativePath, "warning");
            $output = ['path' => $relativePath];
        } else {
            //$app->enqueueMessage("onAjaxJoFavoritesUpload upload failed", "warning");
            $output = 'Upload failed';
        }
        if ($event instanceof ResultAwareInterface) {
            $event->addResult($output);
        } else {
            $event->setArgument('result', $output);
        }
        //$app->close();
    }
}
