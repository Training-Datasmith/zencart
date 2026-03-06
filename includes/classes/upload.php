<?php

declare(strict_types=1);
/**
 * upload Class.
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * upload Class.
 * This class is used to manage file uploads
 *
 * @since ZC v1.0.3
 */
//
// This is the old UPLOAD_FILENAME_EXTENSIONS which was in the database
if (!defined('UPLOAD_FILENAME_EXTENSIONS_LIST')) {
    define('UPLOAD_FILENAME_EXTENSIONS_LIST', 'jpg,jpeg,gif,png,eps,cdr,ai,pdf,tif,tiff,bmp,zip');
}

class upload extends base
{
    protected $file;
    protected $destination;
    protected $extensions;
    public $filename;
    protected $message_location;
    protected $permissions;
    protected $tmp_filename;

    public function __construct($file = '', $destination = '', $permissions = '644', $extensions = [])
    {
        $this->set_file($file);
        $this->set_destination($destination);
        $this->set_permissions($permissions);

        if (empty($extensions)) {
            $extensions = explode(' ', (string) preg_replace('/[.,;\s]+/', ' ', UPLOAD_FILENAME_EXTENSIONS_LIST));
        }
        $this->set_extensions($extensions);

        $this->set_output_messages('direct');

        if (!empty($this->file) && !empty($this->destination)) {
            $this->set_output_messages('session');

            if (($this->parse() == true) && ($this->save() == true)) {
                return;
            }

            // self destruct
            foreach ($this as $key => $val) {
                $this->$key = null;
            }
        }
    }

    /**
     * @param string $key  - differentiates between different files uploaded
     * @return bool
     * @since ZC v1.0.3
     */
    public function parse($key = '')
    {
        if (empty($_FILES[$this->file])) {
            return false;
        }
        if (zen_not_null($key)) {
            $file = [
                'name'     => $_FILES[$this->file]['name'][$key],
                'type'     => $_FILES[$this->file]['type'][$key],
                'size'     => $_FILES[$this->file]['size'][$key],
                'tmp_name' => $_FILES[$this->file]['tmp_name'][$key],
            ];
        } else {
            $file = [
                'name'     => $_FILES[$this->file]['name'],
                'type'     => $_FILES[$this->file]['type'],
                'size'     => $_FILES[$this->file]['size'],
                'tmp_name' => $_FILES[$this->file]['tmp_name'],
            ];
        }

        if (!zen_not_null($file['tmp_name'])) {
            return false;
        }
        //if ($file['tmp_name'] == 'none') return false;
        //if (!is_uploaded_file($file['tmp_name'])) return false;

        if (!is_uploaded_file($file['tmp_name'])) {
            $this->message_stack(WARNING_NO_FILE_UPLOADED, 'warning');

            return false;
        }

        if (zen_not_null($file['tmp_name']) && ($file['tmp_name'] != 'none') && is_uploaded_file($file['tmp_name'])) {
            if (zen_not_null($file['size']) and ($file['size'] > MAX_FILE_UPLOAD_SIZE)) {
                $this->message_stack(ERROR_FILE_TOO_BIG, 'error');

                return false;
            }
            if (str_ends_with((string) $file['name'], '.htaccess') || (sizeof($this->extensions) > 0 && !in_array(strtolower(substr((string) $file['name'], strrpos((string) $file['name'], '.') + 1)), $this->extensions))) {
                $this->message_stack(ERROR_FILETYPE_NOT_ALLOWED . ' .' . implode(', .', $this->extensions), 'error');

                return false;
            }

            $this->set_file($file);
            $this->set_filename($file['name']);
            $this->set_tmp_filename($file['tmp_name']);

            return $this->check_destination();

        }
        if ($file['name'] != '' && $file['tmp_name'] != '') {
            $this->message_stack(WARNING_NO_FILE_UPLOADED, 'warning');

            return false;
        }
    }

    /**
     * @param bool $overwrite
     * @since ZC v1.0.3
     */
    public function save($overwrite = true): bool
    {
        if (!$overwrite and file_exists($this->destination . $this->filename)) {
            $this->message_stack(TEXT_IMAGE_OVERWRITE_WARNING . $this->filename, 'caution');

            return true;
        }

        if (!str_ends_with((string) $this->destination, '/')) {
            $this->destination .= '/';
        }

        if (move_uploaded_file($this->file['tmp_name'], $this->destination . $this->filename)) {

            chmod($this->destination . $this->filename, $this->permissions);

            $this->message_stack(sprintf(SUCCESS_FILE_SAVED_SUCCESSFULLY, $this->filename), 'success');

            if (function_exists('zen_record_admin_activity')) {
                zen_record_admin_activity(sprintf(SUCCESS_FILE_SAVED_SUCCESSFULLY, $this->filename), 'notice');
            }

            return true;
        }

        $this->message_stack(ERROR_FILE_NOT_SAVED, 'error');

        return false;
    }

    /**
     * @param string $file
     * @since ZC v1.0.3
     */
    public function set_file($file): void
    {
        $this->file = $file;
    }

    /**
     * @param string $destination
     * @since ZC v1.0.3
     */
    public function set_destination($destination): void
    {
        $this->destination = $destination;
    }

    /**
     * @param string $permissions
     * @since ZC v1.0.3
     */
    public function set_permissions($permissions): void
    {
        $this->permissions = octdec($permissions);
    }

    /**
     * @since ZC v1.0.3
     */
    public function set_filename(string $filename): void
    {
        $this->filename = $this->sanitizeFileName($filename);
    }

    /**
     * @param string $filename
     * @since ZC v1.0.3
     */
    public function set_tmp_filename($filename): void
    {
        $this->tmp_filename = $filename;
    }

    /**
     * @param array $extensions
     * @since ZC v1.0.3
     */
    public function set_extensions($extensions): void
    {
        if (!empty($extensions)) {
            if (is_array($extensions)) {
                $this->extensions = $extensions;
            } else {
                $this->extensions = [$extensions];
            }
        } else {
            $this->extensions = [];
        }
    }

    /**
     * @since ZC v1.0.3
     */
    public function check_destination(): bool
    {
        if (!is_writeable($this->destination)) {
            if (is_dir($this->destination)) {
                $this->message_stack(sprintf(ERROR_DESTINATION_NOT_WRITEABLE, $this->destination), 'error');
            } else {
                $this->message_stack(sprintf(ERROR_DESTINATION_DOES_NOT_EXIST, $this->destination), 'error');
            }

            return false;
        }

        return true;
    }

    /**
     * @param string $location
     * @since ZC v1.0.3
     */
    public function set_output_messages($location): void
    {
        $this->message_location = match ($location) {
            'session' => 'session',
            default => 'direct',
        };
    }

    /**
     * @since ZC v1.5.5e
     */
    public function message_stack($msg = '', $type = '')
    {
        global $messageStack;
        if (!isset($messageStack) || !is_object($messageStack)) {
            return false;
        }
        if (IS_ADMIN_FLAG === true) {
            $messageStack->add_session($msg, $type);
            $messageStack->add($msg, $type);
        } else {
            if ($this->message_location == 'direct') {
                $messageStack->add_session('header', $msg, $type);
            } else {
                $messageStack->add_session('upload', $msg, $type);
            }
        }
    }

    /**
     * @since ZC v2.2.0
     */
    protected function sanitizeFileName(string $filename): string
    {
        // Convert file-extension to lowercase
        $file_pieces = pathinfo($filename);
        $filename = $file_pieces['filename'] . '.' . strtolower($file_pieces['extension']);

        // Replace spaces with hyphens
        $filename = str_replace(' ', '-', $filename);

        // Remove special characters (keep alphanumerics, dashes, underscores, and dots)
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);

        // Replace multiple dots with a single dot
        $filename = preg_replace('/\.+/', '.', (string) $filename);

        return $filename;
    }
}
