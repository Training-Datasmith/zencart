<?php

declare(strict_types=1);
/**
 * Class pulldown
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 * @since ZC v1.5.8
 */

abstract class pulldown extends base
{
    protected string $attributes_join;
    protected bool $show_id;
    protected int $set_selected;
    protected string $parameters;
    protected string $condition;
    /**
     * @var array
     */
    protected $exclude = [];
    /**
     * @var int
     */
    protected $count = 0;

    protected string $keywords;
    protected $keyword_search_fields;
    protected $results;
    protected $sort;
    protected $sql;
    protected array $values;

    /**
     *
     */
    public function __construct()
    {
        $this->exclude = [];

        $this->show_id = false;

        $this->set_selected = 0;
        $this->values = [];

        $this->keywords = '';

        $this->attributes_join = '';

        $this->condition = ' ';

        // default styling
        $this->parameters = '';
        //$this->parameters = 'required size="15" class="form-control" id="products_id"';
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function setDefault(int $id)
    {
        $this->set_selected = $id;
        return $this;
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function showID(bool $status)
    {
        $this->show_id = $status;
        return $this;
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function setOptionFilter(int $filter_id)
    {
        $this->includeAttributes(true);
        $this->condition .= ' AND pa.options_id =' . $filter_id;
        return $this;
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function exclude(array $array)
    {
        $this->exclude = $array;
        return $this;
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function includeAttributes(bool $status)
    {
        $this->attributes_join = '';
        if ($status) {
            $this->attributes_join = ' RIGHT JOIN ' . TABLE_PRODUCTS_ATTRIBUTES . ' pa on (p.products_id = pa.products_id)';
        }
        return $this;
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function setSearchTerms(string $keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return mixed
     * @since ZC v1.5.8
     */
    abstract protected function processSQL();

    /**
     * @return mixed
     * @since ZC v1.5.8
     */
    abstract protected function setSQL();

    /**
     * @return void
     * @since ZC v1.5.8
     */
    protected function runSQL()
    {
        global $db;

        $this->sql .= $this->condition;

        if (empty($this->keywords)) {
            $this->keywords = ($_REQUEST['keywords'] ?? '');
        }

        if (!empty($this->keywords)) {
            $this->sql .= zen_build_keyword_where_clause(
                $this->keyword_search_fields,
                zen_db_input(zen_db_prepare_input($this->keywords))
            );
        }

        $this->sql .= $this->sort;
        $this->results = $db->Execute($this->sql);
        $this->count = $this->results->count();
    }

    /**
     *
     * @return string
     * @since ZC v2.1.0
     */
    public function generatePulldownHtml(string $name, string $parameters = '', bool $required = false)
    {
        $this->processSQL();

        if (empty($parameters)) {
            $parameters = $this->parameters;
        }

        return zen_draw_pull_down_menu($name, $this->values, $this->set_selected, $parameters, $required);
    }
}
