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
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Log\Log;

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
    
    public static function parseAttributes($string, &$retarray)
    {
        $pairs = explode(';', trim($string));
        foreach ($pairs as $pair) {
            if ($pair == "") {
                continue;
            }
            $pos = strpos($pair, "=");
            $key = substr($pair, 0, $pos);
            $value = substr($pair, $pos + 1);
            $retarray[$key] = $value;
        }
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
        if (strpos($article->text, '{favorites') === false) {
            return true;
        }
        preg_match_all(PF_REGEX_FAV_PATTERN, $article->text, $matches);
        if (!$this->params->get('jsonfileupload', "") && !$this->params->get('external')) {
            $this->getApplication()->enqueueMessage("Please fill parameter jsonfile", "error");
            return true;
        }
        // Number of plugins
        $count = count($matches[0]);
        // plugin only processes if there are any instances of the plugin in the text
        if ($count) {
            for ($i = 0; $i < $count; $i++) {
                if (@$matches[1][$i]) {
                    $inline_params = $matches[1][$i];
                    // accept both syntax {favorites name=xxx;output=css3treeview} {favorites name="xxx" output="css3treeview"}
                    if ( strpos( $inline_params, "\"") === false ) {
                        $localparams = array();
                        self::parseAttributes($inline_params, $localparams);
                    } else {
                        $localparams = Utility::parseAttributes($inline_params);
                    }
                    $localparams[($this->params->get('filetype')== 0)?"jsonfile":"htmlfile"] = str_replace(
                                                "/",
                                                DIRECTORY_SEPARATOR,
                                                JPATH_ROOT . DIRECTORY_SEPARATOR .
                                                (($this->params->get('filetype')== 0)?$this->params->get('jsonfileupload'):
                                                                                      $this->params->get('htmlfileupload'))
                                                );
                    $favorites = $this->favorites($localparams);
                    $startfavorite = "<!-- start favorites " .  $inline_params .  "} -->";
                    $endfavorite = "\n<!-- end favorites " .  $inline_params . "} -->";
                    $p_content = $startfavorite . $favorites . $endfavorite;
                    $article->text = str_replace("{favorites " .  $inline_params . "}", $p_content, $article->text);
                }
            }
        } else {
            $row->text = str_replace(
                "{favorites",
                "erreur de syntaxe: {favorites sname=xxx output=wiki|html like=yyy path=zzz}",
                $row->text
            );
        }
        return true;
    }

    private function readsync($_params, &$content)
    {
        require_once(dirname(__FILE__) . "/../../lib/readsync.body.php");
        return readsync($_params, $content);
    }

    private function getsync($_params, &$content)
    {
        $timeout = null;
        $username = $this->params->get('user');
        $password = $this->params->get('password');
        $syncurl =  $this->params->get('url');
        if (parse_url($syncurl, PHP_URL_QUERY) != null) { 
            $separ = "&";
        } else {
            $separ = "?";
        }
        $url = $syncurl . $separ . http_build_query($_params);
        $options = new \Joomla\Registry\Registry();
        $options->set(
            'transport.curl',
            array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERPWD => "$username:$password",
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC
            )
        );
        $options->set('follow_location', true);
        $headers = array();
        try {
            $response =  \Joomla\CMS\Http\HttpFactory::getHttp($options)->get($url, $headers, $timeout);
        } catch (UnexpectedValueException $e) {
            throw new RuntimeException('Could not get data from remote server: ' . $e->getMessage(), 500);
        } catch (RuntimeException $e) {
        // There was an error connecting to the server or in the post request
            throw new RuntimeException('Could not connect to remote server: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
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
    private function favorites($_params)
    {
        if (is_array($_params) == false) {
            return  "error for params:" . $_params;
        }
        if (!array_key_exists('output', $_params)) {
            $_params['output'] = "css3treeview";
        }
        /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
        /* in ConsoleApplication it does not exist); */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addRegistryFile('media/plg_content_jofavorites/joomla.asset.json');
        $wa->usePreset("plugin.jofavorites");
        $wa->addInlineScript('var uriroot="' . URI::root() . '";', ['position' => 'before'], [], ['plugin.jofavorites']);
        if ($this->params->get('external')) {
            $this->getsync($_params, $content);
        } else {
            $this->readsync($_params, $content);
        }
        //Log::add('favorites:=>:'. print_r($content, true), Log::WARNING, 'favoris');
        return $content;
    }

    public function onAjaxJOFavoritesUpload($file)
    {
        if (!$file || !isset($file['tmp_name'])) {
            //$app->enqueueMessage("onAjaxJoFavoritesUpload no file uploaded", "warning");
            echo new \Joomla\CMS\Response\JsonResponse(null, 'No file uploaded', true);
            $app->close();
        }
        $uploadDir = JPATH_ROOT . '/files/jofavorites/';
        Folder::create($uploadDir);
        $filename = File::makeSafe($file['name']);
        $dest = $uploadDir . $filename;
        if (File::upload($file['tmp_name'], $dest)) {
            $relativePath = 'files/jofavorites/' . $filename;
        //$app->enqueueMessage("onAjaxJoFavoritesUpload " . $relativePath, "warning");
            $output = ['path' => $relativePath];
        } else {
        //$app->enqueueMessage("onAjaxJoFavoritesUpload upload failed", "warning");
            $output = 'Upload failed';
        }
        return $output;
    }
    
    
     public function onAjaxJOFavoritesGrabIcon($url64, $url)
     {
        if (($url == null) && ($url64 == null))
        {
            return "";
        }
        if ($url == null) {
            $url = base64_decode($url64);
        }
        Log::add('onAjaxJOFavoritesGrabIco:<=url:'. $url, Log::WARNING, 'favorites');
        require_once(dirname(__FILE__) . "/../../lib/PHP-Grab-Favicon/get-fav.php");
        $grap_favicon = array(
            'URL' => $url,   // URL of the Page we like to get the Favicon from
            'SAVE'=> false,   // Save Favicon copy local (true) or return only favicon url (false)
            'DIR' =>  JPATH_ROOT ."/files/jofavorites",   // Local Dir the copy of the Favicon should be saved
            'TRY' => true,   // Try to get the Favicon frome the page (true) or only use the APIs (false)
            'DEV' => false   // Give all Debug-Messages ('debug') or only make the work (null)
        );
        return \grap_favicon($grap_favicon);
    }
    

    public function onAjaxJOFavorites(Event $event): void
    {
        // Permissions check: Only allow backend users, etc.
        $app = Factory::getApplication();
        //$app->enqueueMessage("onAjaxJoFavoritesUpload", "warning");
        $input = $app->input;
        $method = $input->getCmd('method', null);
        switch ($method) {
            case 'upload':
                if (!$app->isClient('administrator')) {
                    $event->setArgument('result', 'Forbidden');
                    return;
                }
                $output = $this->onAjaxJOFavoritesUpload($input->files->get('file', null, 'raw'));
                break;
            case 'grabicon';
                $output = $this->onAjaxJOFavoritesGrabIcon($input->getCmd('url64', null), $input->getCmd('url', null));
                Log::add('favorites:=>:'. print_r($output, true), Log::WARNING, 'favorites');
                break;
            case 'readsync';
                $params = $input->getArray();
                $params["jsonfile"] = str_replace(
                                                "/",
                                                DIRECTORY_SEPARATOR,
                                                JPATH_ROOT . DIRECTORY_SEPARATOR . $this->params->get('jsonfileupload')
                                            );
                $output = $this->favorites($params);
                Log::add('favorites:=>:'. print_r($output, true), Log::WARNING, 'favorites');
                break;
        }
        if ($event instanceof ResultAwareInterface) {
            $event->addResult($output);
        } else {
            $event->setArgument('result', $output);
        }
    }
}
