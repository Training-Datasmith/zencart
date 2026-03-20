<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * This observer could be used to allow file-downloads to be served from any publicly accessible URL
 * as long as the URL doesn't require special authentications that the download customer would not
 * know the credentials for. Thus it could conveniently serve files from Dropbox and others.
 *
 * @since ZC v1.5.6
 */
class Zc_Observer_Downloads_Via_Url extends base
{
    public function __construct()
    {
        $this->attach($this, ['NOTIFY_CHECK_DOWNLOAD_HANDLER', 'NOTIFY_DOWNLOAD_READY_TO_START', 'NOTIFY_MODULE_DOWNLOAD_TEMPLATE_DETAILS', 'NOTIFY_MODULE_DOWNLOADABLE_FILE_EXISTS']);
    }
    /**
     * Parse the file details for display on template page
     *
     * @param string $eventID name of the observer event fired
     * @param array $array $download->fields data
     * @param array $data array passed by reference
     * @since ZC v1.5.6
     */
    protected function update_notify_module_download_template_details(&$class, $event_id, $array, array &$data)
    {
        // available fields:
        //   $data['service'] = 'local'
        //   $data['filename'] = db query result from orders_products_filename
        //   $data['expiry_timestamp']
        //   $data['expiry']
        //   $data['downloads_remaining']
        //   $data['unlimited_downloads']
        //   $data['file_exists']
        //   $data['is_downloadable']
        //   $data['filesize']
        //   $data['date_purchased_day']
        //   $data['download_maxdays']
        //   $data['products_name']
        //   $data['orders_products_download_id'] = id for URL link
        //   $data['download_count']
        $file_parts = $this->parse_file_parts($data['filename']);
        if ($file_parts === false) {
            return;
        }
        $data['service'] = $file_parts[0];
        // use just the filename portion, for customer-facing display purposes
        $data['filename'] = substr((string) $file_parts[1], strrpos((string) $file_parts[1], '/') + 1);
        $data['filesize'] = isset($file_parts[2]) ? number_format($file_parts[2], 0) : '';
        $data['filesize_units'] = '';
        $data['is_downloadable'] = $data['file_exists'] = $this->test_file_exists();
    }
    /**
     * This observer should set $handler to blank if it fails to validate whether $filename exists at the destination URL.
     * If validation passes, simply set $handler to the service name (first chars before first colon in filename).
     * If there is no way to verify, do nothing to $handler.
     *
     * @param string $eventID name of the observer event fired
     * @param string $filename filename to verify exists
     * @param string $handler  name of external service handler
     * @since ZC v1.5.6
     */
    protected function update_notify_test_downloadable_file_exists(&$class, $event_id, $filename, &$handler)
    {
        $result = $this->test_file_exists();
        if ($result === false) {
            $handler = '';
        }
    }
    /**
     *
     * @param string $eventID name of the observer event fired
     * @param array $var deprecated array, used only for backward compatibility
     * @param array $fields data feeding all download activities
     * @param string $origin_filename  (mutable)
     * @param string $browser_filename (mutable)
     * @param string $source_directory (mutable)
     * @param boolean $file_exists (mutable)
     * @param string $service (mutable)
     * @since ZC v1.5.6
     */
    protected function update_notify_check_download_handler(&$class, $event_id, $var, &$fields, &$origin_filename, &$browser_filename, &$source_directory, &$file_exists, &$service)
    {
        $file_parts = $this->parse_file_parts($origin_filename);
        if ($file_parts === false) {
            return;
        }
        if ($file_parts[0] == 'http' || $file_parts[0] == 'https') {
            $origin_filename = $file_parts[1];
            $browser_filename = substr((string) $origin_filename, strrpos((string) $origin_filename, '/') + 1);
            $source_directory = $file_parts[0];
            $file_exists = true;
            $service = $file_parts[0];
        }
    }
    /**
     * This fires when the download module wants to redirect to the external download URL
     * So, this method parses the passed file, obtains the URL, and does the redirect
     *
     * @param string $eventID name of the observer event fired
     * @param string $ipaddress customer IP
     * @param string $service (mutable)
     * @param string $origin_filename (mutable)
     * @param string $browser_filename (mutable)
     * @param string $source_directory (mutable)
     * @param integer $downloadFilesize (mutable)
     * @param string $mime_type (mutable)
     * @param array $fields  array of data from db query feeding the download page
     * @param string $browser_extra_headers (mutable)
     * @since ZC v1.5.6
     */
    protected function update_notify_download_ready_to_start(&$class, $event_id, $ipaddress, string &$service, &$origin_filename, &$browser_filename, &$source_directory, &$download_filesize, $mime_type, $fields, $browser_extra_headers)
    {
        // verify that the passed "file" is an http/https URL
        if ($source_directory != 'http' && $source_directory != 'https') {
            $file_parts = $this->parse_file_parts($origin_filename);
            if ($file_parts === false || $file_parts[0] != 'http' && $file_parts[0] != 'https') {
                return;
            }
            $origin_filename = $file_parts[1];
            $browser_filename = substr((string) $origin_filename, strrpos((string) $origin_filename, '/') + 1);
            $source_directory = $file_parts[0];
            $download_filesize = $file_parts[2];
        }
        // prepare redirect URL
        $url = $this->build_redirect_url($service . ':' . $origin_filename);
        // redirect to external download script
        header('HTTP/1.1 303 See Other');
        zen_redirect($url);
        zen_exit();
    }
    /**
     * parse file details to determine if its download should be handled by a simple HTTP URL
     * Evidence is the that filename will use colons as delimiters ... http://domain/filename:filesize
     * (filesize is optional)
     *
     * @param string $filename
     * @since ZC v1.5.6
     */
    private function parse_file_parts($filename): array|false
    {
        $file_parts = explode(':', $filename);
        if (preg_match('~^(https?://)(?!=.*)~', $filename, $matches)) {
            return $file_parts;
        }
        return false;
    }
    /**
     * return URL for redirect
     *
     * @return string $url
     * @since ZC v1.5.6
     */
    private function build_redirect_url(string $url): string
    {
        return $url;
    }
    /**
     * Use a tool to test whether the file at $filename exists
     * If it does not exist, return false
     *
     * @return boolean Result of test
     * @since ZC v1.5.6
     */
    private function test_file_exists(): bool
    {
        //@TODO maybe try a CURL request to see if the file exists ... but request only the headers, not the full file response.
        return true;
    }
}