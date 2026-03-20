<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\View_Builders;

use Zencart\Traits\Notifier_Manager;
/**
 * @since ZC v1.5.8
 */
class Base_Controller
{
    use Notifier_Manager;
    protected array $info_box;
    public function __construct(protected \Zencart\Request\Request $request, protected $message_stack, protected \Zencart\View_Builders\Table_View_Definition $table_definition, protected $formatter)
    {
        $this->info_box = ['header' => [], 'content' => []];
    }
    /**
     * @since ZC v1.5.8
     */
    public function process_request(): void
    {
        $action = $this->get_action();
        $method = $action == '' ? 'processDefaultAction' : 'processAction' . ucfirst($action);
        if (method_exists($this, $method)) {
            $this->{$method}();
        }
        $this->notify('NOTIFY_TABLEVIEW_PROCESSREQUEST', [], $method);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function get_action(): string
    {
        return $this->request->input('action', '');
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_box_header(string $content, array $params = []): void
    {
        $this->info_box['header'][] = ['text' => $content, 'params' => $params];
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_box_form(string $content): void
    {
        $this->info_box['content']['form'] = $content;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_box_header()
    {
        return $this->info_box['header'];
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_box_content(string $content, array $params = []): void
    {
        $this->info_box['content'][] = ['text' => $content, 'params' => $params];
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_box_content()
    {
        return $this->info_box['content'];
    }
    /**
     * @since ZC v1.5.8
     */
    public function page_link(): string
    {
        $page = $this->request->input($this->table_definition->get_parameter('pagerVariable'), 1);
        return $this->table_definition->get_parameter('pagerVariable') . '=' . $page;
    }
    /**
     * @since ZC v1.5.8
     */
    public function col_key_link(): string
    {
        return $this->table_definition->col_key_name() . '=' . $this->current_field_value($this->table_definition->get_parameter('colKey'));
    }
    /**
     * @since ZC v1.5.8
     */
    public function current_field_value($field)
    {
        $current_row = $this->formatter->current_row_from_request();
        if (is_null($current_row)) {
            return null;
        }
        return $current_row->{$field};
    }
    /**
     * @since ZC v1.5.8
     */
    public function output_message_list($error_list, $error_type): void
    {
        if (!count($error_list)) {
            return;
        }
        foreach ($error_list as $error) {
            $this->message_stack->add_session($error, $error_type);
        }
    }
}