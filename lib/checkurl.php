<?php

/**
* @copyright Copyright (C) 2025 Jean-Luc TRYOEN. All rights reserved.
* @license GNU/GPL
*
* Version 1.0.0
*
* @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @link        https://www.jltryoen.fr
*/

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

function checkurl($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEVALUE, time());
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0");
    $res = curl_exec($curl);
    if ($res === false) {
        $result = false;
    } else {
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (($statusCode >= 200 ) && ($statusCode < 308)) {
            $result = true;
        } else {
             $result = $statusCode;
        }
        curl_close($curl);
    }
    return $result;
}

function checkurl2($url)
{
    $headers = @get_headers($url);
    if (strpos($headers[0], '404') === false) {
        $result = true;
    } else {
        $result = $headers[0];
    }
    return $result;
}


function get_headers2($url, $format = 0)
{
    $url_info = parse_url($url);
    $port = isset($url_info['port']) ? $url_info['port'] : 80;
    $fp = fsockopen($url_info['host'], $port, $errno, $errstr, 30);
    if ($fp) {
        $head = "HEAD " . @$url_info['path'] . "?" . @$url_info['query'];
        $head .= " HTTP/1.0\r\nHost: " . @$url_info['host'] . "\r\n\r\n";
        fputs($fp, $head);
        while (!feof($fp)) {
            if ($header = trim(fgets($fp, 1024))) {
                if ($format == 1) {
                    $h2 = explode(':', $header);
                    // the first element is the http header type, such as HTTP/1.1 200 OK,
                    // it doesn't have a separate name, so we have to check for it.
                    if ($h2[0] == $header) {
                        $headers['status'] = $header;
                    } else {
                        $headers[strtolower($h2[0])] = trim($h2[1]);
                    }
                } else {
                    $headers[] = $header;
                }
            }
        }
        return true;
    } else {
        return false;
    }
}
