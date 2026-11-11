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
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Utility\Utility;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

define('JOFAV_EXEXP_FAV_PATTERN', "#{favorites (.*?)}#s");

/**
 * JOFavorites Content Plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.JOFavorites
 */
class JOFavorites extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare'  => 'onContentPrepare',
            'onAjaxJofavorites' => 'onAjaxJOFavorites',
        ];
    }

    public static function parseAttributes($string, &$retarray)
    {
        $pairs = explode(';', trim($string));
        foreach ($pairs as $pair) {
            if ($pair === '') {
                continue;
            }
            $pos = strpos($pair, '=');
            $key = substr($pair, 0, $pos);
            $value = substr($pair, $pos + 1);
            $retarray[$key] = $value;
        }
    }

    public function onContentPrepare(ContentPrepareEvent $event)
    {
        if (!$this->getApplication()->isClient('site')) {
            return;
        }
        if (!$this->params->get('enabled', 1)) {
            return true;
        }

        [$context, $article, $params, $page] = array_values($event->getArguments());
        if (strpos($context, 'com_content') === false) {
            return true;
        }
        if (strpos($article->text, '{favorites') === false) {
            return true;
        }

        preg_match_all(JOFAV_EXEXP_FAV_PATTERN, $article->text, $matches);
        if (!$this->params->get('jsonfileupload', '') && !$this->params->get('external')) {
            $this->getApplication()->enqueueMessage('Please fill parameter jsonfile', 'error');
            return true;
        }

        $count = count($matches[0]);
        if ($count) {
            for ($i = 0; $i < $count; $i++) {
                if (@$matches[1][$i]) {
                    $inline_params = $matches[1][$i];
                    Log::add('found favorites :=>:' . $inline_params . ':' . $context, Log::WARNING, 'jofavorites');
                    if (strpos($inline_params, '"') === false) {
                        $localparams = array();
                        self::parseAttributes($inline_params, $localparams);
                    } else {
                        $localparams = Utility::parseAttributes($inline_params);
                    }
                    $localparams[($this->params->get('filetype') == 0) ? 'jsonfile' : 'htmlfile'] = str_replace(
                        '/',
                        DIRECTORY_SEPARATOR,
                        JPATH_ROOT . DIRECTORY_SEPARATOR . (($this->params->get('filetype') == 0)
                            ? $this->params->get('jsonfileupload')
                            : $this->params->get('htmlfileupload'))
                    );
                    $favorites = $this->favorites($localparams);
                    $startfavorite = '<!-- start favorites ' . $inline_params . '} -->';
                    $endfavorite = "\n<!-- end favorites " . $inline_params . '} -->';
                    $p_content = $startfavorite . $favorites . $endfavorite;
                    $article->text = str_replace('{favorites ' . $inline_params . '}', $p_content, $article->text);
                }
            }
        }

        return true;
    }

    /**
     * Render favorites using the installed JLTRY\LibFavorites Joomla library.
     *
     * @param   array  $_params  Rendering options
     * @param   mixed  $content  Rendered output
     *
     * @return  mixed
     */
    private function readsync($_params, &$content)
    {
        $libraryEntryPoint = JPATH_LIBRARIES . '/libfavorites/src/readsync.body.php';
        if (!is_file($libraryEntryPoint)) {
            throw new \RuntimeException('The JLTRY Favorites library is not installed: ' . $libraryEntryPoint);
        }

        require_once $libraryEntryPoint;

        if (!function_exists('JLTRY\\LibFavorites\\readsync')) {
            throw new \RuntimeException('The installed JLTRY Favorites library does not expose JLTRY\\LibFavorites\\readsync().');
        }

        return \JLTRY\LibFavorites\readsync($_params, $content);
    }

    private function getsync($_params, &$content)
    {
        $timeout = null;
        $username = $this->params->get('user');
        $password = $this->params->get('password');
        $syncurl = $this->params->get('url');
        $separ = (parse_url($syncurl, PHP_URL_QUERY) !== null) ? '&' : '?';
        $url = $syncurl . $separ . http_build_query($_params);
        $options = new \Joomla\Registry\Registry();
        $options->set('transport.curl', array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => "$username:$password",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        ));
        $options->set('follow_location', true);
        $headers = array();
        Log::add('favorites getsync:=>:' . print_r($url, true), Log::WARNING, 'favorites');

        try {
            $response = \Joomla\CMS\Http\HttpFactory::getHttp($options)->get($url, $headers, $timeout);
        } catch (\UnexpectedValueException $e) {
            throw new \RuntimeException('Could not get data from remote server: ' . $e->getMessage(), 500);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Could not connect to remote server: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unexpected error connecting to server: ' . $e->getMessage(), 500);
        }

        Log::add('favorites getsync:<=' . $response->getBody(), Log::WARNING, 'favorites');
        $content .= $response->getBody();
    }

    /**
     * Render favorites markup.
     *
     * @param   array  $_params  Rendering options
     *
     * @return  string
     */
    private function favorites($_params)
    {
        if (!is_array($_params)) {
            return 'error for params:' . $_params;
        }
        if (!array_key_exists('output', $_params)) {
            $_params['output'] = 'css3treeview';
        }

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addRegistryFile('media/libfavorites/joomla.asset.json');
        $wa->usePreset('jltry.favorites');
        $wa->addInlineScript('var uriroot="' . Uri::root() . '";', ['position' => 'before'], [], ['jltry.favorites']);

        if ($this->params->get('external')) {
            $this->getsync($_params, $content);
        } else {
            $this->readsync($_params, $content);
        }

        return $content;
    }

    public function onAjaxJOFavoritesUpload($file)
    {
        if (!$file || !isset($file['tmp_name'])) {
            echo new \Joomla\CMS\Response\JsonResponse(null, 'No file uploaded', true);
            Factory::getApplication()->close();
        }

        $uploadDir = JPATH_ROOT . '/files/jofavorites/';
        Folder::create($uploadDir);
        $filename = File::makeSafe($file['name']);
        $dest = $uploadDir . $filename;
        if (File::upload($file['tmp_name'], $dest)) {
            return ['path' => 'files/jofavorites/' . $filename];
        }

        return 'Upload failed';
    }

    public function onAjaxJOFavoritesGrabIcon($url64, $url)
    {
        if (($url === null) && ($url64 === null)) {
            return '';
        }
        if ($url === null) {
            $url = base64_decode($url64);
        }

        Log::add('onAjaxJOFavoritesGrabIco:<=url:' . $url, Log::WARNING, 'favorites');
        require_once dirname(__FILE__) . '/../../lib/PHP-Grab-Favicon/get-fav.php';
        $grap_favicon = array(
            'URL' => $url,
            'SAVE' => false,
            'DIR' => JPATH_ROOT . '/files/jofavorites',
            'TRY' => true,
            'DEV' => false,
        );

        return \grap_favicon($grap_favicon);
    }

    public function onAjaxJOFavorites(AjaxEvent $event): void
    {
        $app = Factory::getApplication();
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
            case 'grabicon':
                $output = $this->onAjaxJOFavoritesGrabIcon($input->getCmd('url64', null), $input->getCmd('url', null));
                Log::add('favorites:=>:' . print_r($output, true), Log::WARNING, 'favorites');
                break;
            case 'readsync':
                $params = $input->getArray();
                $params['jsonfile'] = str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    JPATH_ROOT . DIRECTORY_SEPARATOR . $this->params->get('jsonfileupload')
                );
                $output = $this->favorites($params);
                Log::add('favorites:=>:' . print_r($output, true), Log::WARNING, 'favorites');
                break;
            default:
                $output = null;
        }

        $event->setArgument('result', $output);
    }
}
