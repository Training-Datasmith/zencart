<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 29 Modified in v2.2.0 $
 */
namespace Zencart\View_Builders;

use Zencart\File_System\File_System;
use Zencart\Plugin_Manager\Plugin_Manager;
use Zencart\Plugin_Support\Installer_Factory;
use Zencart\Plugin_Support\Plugin_Status;
/**
 * @since ZC v1.5.8
 */
class Plugin_Manager_Controller extends Base_Controller
{
    protected Plugin_Manager $plugin_manager;
    protected Installer_Factory $installer_factory;
    /**
     * @since ZC v1.5.8
     */
    public function init(Plugin_Manager $plugin_manager, Installer_Factory $installer_factory): void
    {
        $this->plugin_manager = $plugin_manager;
        $this->installer_factory = $installer_factory;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_default_action()
    {
        if ($this->current_field_value('unique_key') === null) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER));
        }
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        if ($this->current_field_value('status') == 1) {
            $this->set_box_content('<br>' . sprintf(TEXT_VERSION_INSTALLED, $this->current_field_value('version')) . '<br>');
        }
        $this->set_box_content('<br>' . TEXT_INFO_DESCRIPTION . '<br>' . zen_lookup_admin_menu_language_override('plugin_description', $this->current_field_value('unique_key'), $this->current_field_value('description')));
        if (!empty($this->current_field_value('author'))) {
            $this->set_box_content(sprintf(TEXT_PLUGIN_AUTHOR, $this->current_field_value('author')));
        }
        if ((int) $this->current_field_value('status') === Plugin_Status::NOT_INSTALLED) {
            $this->set_box_content('<a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=install') . '" class="btn btn-primary" role="button">' . TEXT_INSTALL . '</a>');
        }
        if ($available = $this->plugin_manager->is_new_download_available($this->current_field_value('zc_contrib_id'), $this->current_field_value('version'))) {
            $this->set_box_content(sprintf(TEXT_NEW_PLUGIN_DOWNLOAD_AVAILABLE, $available['latest_plugin_version'], $available['id']));
        } elseif (!empty($this->current_field_value('zc_contrib_id'))) {
            $this->set_box_content(sprintf(TEXT_PLUGIN_DOWNLOAD_PAGE, $this->current_field_value('zc_contrib_id')));
        }
        if ($this->plugin_manager->is_upgrade_available($this->current_field_value('unique_key'), $this->current_field_value('version'))) {
            $this->set_box_content('<a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=upgrade') . '" class="btn btn-primary" role="button">' . TEXT_UPGRADE_AVAILABLE . '</a>');
        }
        if ((int) $this->current_field_value('status') === Plugin_Status::ENABLED) {
            $this->set_box_content('<a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=disable') . '" class="btn btn-primary" role="button">' . TEXT_DISABLE . '</a>');
            $this->set_box_content('<a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=uninstall') . '" class="btn btn-primary" role="button">' . TEXT_UNINSTALL . '</a>');
        } elseif ((int) $this->current_field_value('status') === Plugin_Status::DISABLED) {
            $this->set_box_content('<a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=enable') . '" class="btn btn-primary" role="button">' . TEXT_ENABLE . '</a>');
            $this->set_box_content('<a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=uninstall') . '" class="btn btn-primary" role="button">' . TEXT_UNINSTALL . '</a>');
        }
        if ($this->plugin_manager->has_plugin_versions_to_clean($this->current_field_value('unique_key'), $this->current_field_value('version')) && !empty($this->current_field_value('version'))) {
            $this->set_box_content('<br>' . TEXT_INFO_CLEANUP);
            $this->set_box_content('<a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=cleanup') . '" class="btn btn-primary" role="button">' . TEXT_CLEANUP . '</a>');
        }
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_install()
    {
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        $this->set_box_form(zen_draw_form('plugininstall', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=doInstall', 'post'));
        $this->set_box_content('<br>' . TEXT_INFO_DESCRIPTION . '<br>' . zen_lookup_admin_menu_language_override('plugin_description', $this->current_field_value('unique_key'), $this->current_field_value('description')));
        $versions = $this->plugin_manager->get_plugin_versions_for_plugin($this->current_field_value('unique_key'));
        $has_multiple = count($versions) > 1;
        $first_key = key($versions);
        if ($has_multiple) {
            foreach ($versions as $version) {
                $checked = $version['version'] == $first_key;
                $this->set_box_content('<br><label class="radio-inline">' . zen_draw_radio_field('version', $version['version'], $checked) . $version['version']);
            }
        }
        if (!$has_multiple) {
            $this->set_box_content(zen_draw_hidden_field('version', $first_key));
        }
        $this->set_box_content('<br><button type="submit" class="btn btn-primary">' . TEXT_INSTALL . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_do_install()
    {
        if (!$this->request->has('version')) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $installer = $this->installer_factory->make($this->current_field_value('unique_key'), $this->request->input('version'));
        $installed = $installer->process_install($this->current_field_value('unique_key'), $this->request->input('version'));
        if (!$installed) {
            $this->output_message_list($installer->get_error_container()->get_friendly_errors(), 'error');
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $this->message_stack->add_session(TEXT_INSTALL_SUCCESS, 'success');
        zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_uninstall()
    {
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        $this->set_box_form(zen_draw_form('pluginuninstall', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=doUninstall', 'post') . zen_draw_hidden_field('version', $this->current_field_value('version')));
        $this->set_box_content('<br>' . TEXT_CONFIRM_UNINSTALL . '<br>');
        $this->set_box_content('<br><button type="submit" class="btn btn-danger">' . TEXT_UNINSTALL . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_do_uninstall()
    {
        if (!$this->request->has('version')) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $installer = $this->installer_factory->make($this->current_field_value('unique_key'), $this->request->input('version'));
        $uninstalled = $installer->process_uninstall($this->current_field_value('unique_key'), $this->request->input('version'));
        if (!$uninstalled) {
            $this->output_message_list($installer->get_error_container()->get_friendly_errors(), 'error');
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $this->notify('NOTIFY_PLUGINMANAGER_DO_UNINSTALL', ['plugin_key' => $this->current_field_value('unique_key'), 'version' => $this->request->input('version')]);
        $this->message_stack->add_session(TEXT_UNINSTALL_SUCCESS, 'success');
        zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_upgrade()
    {
        if (!$this->plugin_manager->is_upgrade_available($this->current_field_value('unique_key'), $this->current_field_value('version'))) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $versions = $this->plugin_manager->get_versions_for_upgrade($this->current_field_value('unique_key'), $this->current_field_value('version'));
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        $this->set_box_form(zen_draw_form('pluginupgrade', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=confirmUpgrade', 'post'));
        $this->set_box_content('<br>' . TEXT_INFO_UPGRADE . '<br>');
        $first_key = key($versions);
        foreach ($versions as $version) {
            $checked = $version == $first_key;
            $this->set_box_content('<br><label class="radio-inline">' . zen_draw_radio_field('version', $version, $checked) . $version);
        }
        $this->set_box_content('<br><button type="submit" class="btn btn-primary">' . TEXT_UPGRADE . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_confirm_upgrade()
    {
        $error = false;
        $versions = $this->plugin_manager->get_versions_for_upgrade($this->current_field_value('unique_key'), $this->current_field_value('version'));
        if (!$this->plugin_manager->is_upgrade_available($this->current_field_value('unique_key'), $this->current_field_value('version'))) {
            $error = true;
        }
        if (!$this->request->has('version')) {
            $error = true;
        }
        if (!in_array($this->request->input('version'), $versions)) {
            $error = true;
        }
        if ($error) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        $this->set_box_form(zen_draw_form('pluginupgrade', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=doUpgrade', 'post') . zen_draw_hidden_field('version', $this->request->input('version')));
        $this->set_box_content('<br>' . TEXT_CONFIRM_UPGRADE . '<br>' . sprintf(TEXT_INFO_UPGRADE_CONFIRM, $this->request->input('version')) . '<br><br>' . TEXT_INFO_UPGRADE_WARNING);
        $this->set_box_content('<br><button type="submit" class="btn btn-primary">' . TEXT_UPGRADE . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_do_upgrade()
    {
        $error = false;
        $versions = $this->plugin_manager->get_versions_for_upgrade($this->current_field_value('unique_key'), $this->current_field_value('version'));
        if (!$this->plugin_manager->is_upgrade_available($this->current_field_value('unique_key'), $this->current_field_value('version'))) {
            $error = true;
        }
        if (!$this->request->has('version')) {
            $error = true;
        }
        if (!in_array($this->request->input('version'), $versions)) {
            $error = true;
        }
        if ($error) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $installer = $this->installer_factory->make($this->current_field_value('unique_key'), $this->request->input('version'));
        $upgraded = $installer->process_upgrade($this->current_field_value('unique_key'), $this->request->input('version'), $this->current_field_value('version'));
        if (!$upgraded) {
            $this->output_message_list($installer->get_error_container()->get_friendly_errors(), 'error');
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $this->notify('NOTIFY_PLUGINMANAGER_DO_UPGRADE', ['plugin_key' => $this->current_field_value('unique_key'), 'version' => $this->request->input('version'), 'old_version' => $this->current_field_value('version')]);
        $this->message_stack->add_session(TEXT_UPGRADE_SUCCESS, 'success');
        zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_clean_up()
    {
        $versions = $this->plugin_manager->get_plugin_versions_to_clean($this->current_field_value('unique_key'), $this->current_field_value('version'));
        $this->set_box_header('<h4>' . zen_output_string_protected(zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name'))) . '</h4>');
        $this->set_box_form(zen_draw_form('pluginupgrade', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=confirmCleanUp', 'post'));
        $this->set_box_content('<br>' . TEXT_INFO_SELECT_CLEAN . '<br>');
        foreach ($versions as $version) {
            $this->set_box_content('<br>' . zen_draw_checkbox_field('version[]', $version['version']) . ' ' . $version['version']);
        }
        $this->set_box_content('<br><button type="submit" class="btn btn-danger">' . TEXT_CONFIRM . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_confirm_clean_up()
    {
        if (!$this->request->has('version') || !is_array($this->request->input('version'))) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=cleanup'));
        }
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        $this->set_box_form(zen_draw_form('pluginupgrade', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=doCleanUp', 'post'));
        $this->set_box_content('<br>' . TEXT_INFO_CONFIRM_CLEAN . '<br>');
        foreach ($this->request->input('version') as $version) {
            $this->set_box_content('<br>' . $version . zen_draw_hidden_field('version[]', $version));
        }
        $this->set_box_content('<br><button type="submit" class="btn btn-danger">' . TEXT_CONFIRM . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_do_cleanup()
    {
        if (!$this->request->has('version') || !is_array($this->request->input('version'))) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=cleanup'));
        }
        $error = '';
        foreach ($this->request->input('version') as $version) {
            $path = DIR_FS_CATALOG . 'zc_plugins/' . $this->current_field_value('unique_key') . '/' . $version;
            (new File_System())->delete_directory($path);
            if (is_dir($path)) {
                $error .= ' :' . $path;
            }
        }
        if ($error === '') {
            $this->message_stack->add_session(TEXT_CLEANUP_SUCCESS, 'success');
        } else {
            $this->message_stack->add_session(TEXT_CLEANUP_ERROR . $error, 'error');
        }
        $this->notify('NOTIFY_PLUGINMANAGER_DO_CLEANUP', ['plugin_key' => $this->current_field_value('unique_key'), 'version' => $this->request->input('version')]);
        zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link()));
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_enable()
    {
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        $this->set_box_form(zen_draw_form('pluginuninstall', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=doEnable', 'post') . zen_draw_hidden_field('version', $this->current_field_value('version')));
        $this->set_box_content('<br>' . TEXT_CONFIRM_ENABLE . '<br>');
        $this->set_box_content('<br><button type="submit" class="btn btn-primary">' . TEXT_ENABLE . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_do_enable()
    {
        if (!$this->request->has('version')) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $installer = $this->installer_factory->make($this->current_field_value('unique_key'), $this->request->input('version'));
        $installer->process_enable($this->current_field_value('unique_key'), $this->request->input('version'));
        $this->notify('NOTIFY_PLUGINMANAGER_DO_ENABLE', ['plugin_key' => $this->current_field_value('unique_key'), 'version' => $this->request->input('version')]);
        $this->message_stack->add_session(TEXT_ENABLE_SUCCESS, 'success');
        zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_disable()
    {
        $this->set_box_header('<h4>' . zen_lookup_admin_menu_language_override('plugin_name', $this->current_field_value('unique_key'), $this->current_field_value('name')) . '</h4>');
        $this->set_box_form(zen_draw_form('pluginuninstall', FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink() . '&action=doDisable', 'post') . zen_draw_hidden_field('version', $this->current_field_value('version')));
        $this->set_box_content('<br>' . TEXT_CONFIRM_DISABLE . '<br>');
        $this->set_box_content('<br><button type="submit" class="btn btn-danger">' . TEXT_DISABLE . '</button> <a href="' . zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_action_do_disable()
    {
        if (!$this->request->has('version')) {
            zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
        }
        $installer = $this->installer_factory->make($this->current_field_value('unique_key'), $this->request->input('version'));
        $installer->process_disable($this->current_field_value('unique_key'), $this->request->input('version'));
        $this->notify('NOTIFY_PLUGINMANAGER_DO_DISABLE', ['plugin_key' => $this->current_field_value('unique_key'), 'version' => $this->request->input('version')]);
        $this->message_stack->add_session(TEXT_DISABLE_SUCCESS, 'success');
        zen_redirect(zen_href_link(FILENAME_PLUGIN_MANAGER, $this->page_link() . '&' . $this->col_keylink()));
    }
}