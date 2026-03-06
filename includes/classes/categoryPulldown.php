<?php

declare(strict_types=1);
/**
 * Class categoryPulldown
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 * @since ZC v1.5.8
 */

class categoryPulldown extends pulldown
{
    private bool $show_full_path;
    private bool $show_parent;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->show_parent = false;
        $this->show_full_path = false;

        $this->sort = ' ORDER BY categories_name';

        $this->keyword_search_fields = [
            'cd.categories_name',
            'c.parent_id',
            'cd.categories_description',
            'c.categories_id',
        ];
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function showParent(bool $status): static
    {
        $this->show_parent = $status;
        return $this;
    }

    /**
     *
     * @return $this
     * @since ZC v1.5.8
     */
    public function showFullPath(bool $status): static
    {
        $this->show_full_path = $status;
        return $this;
    }

    /**
     * @return mixed|void
     * @since ZC v1.5.8
     */
    protected function setSQL()
    {
        $this->attributes_join = str_replace('p.products_id', 'ptoc.products_id', $this->attributes_join);
        $this->sql = 'SELECT DISTINCT c.categories_id, cd.categories_name
            FROM ' . TABLE_CATEGORIES . ' c
            LEFT JOIN ' . TABLE_CATEGORIES_DESCRIPTION . ' cd ON (c.categories_id = cd.categories_id AND cd.language_id = ' . (int)$_SESSION['languages_id'] . ')
            LEFT JOIN ' . TABLE_PRODUCTS_TO_CATEGORIES . ' ptoc on (c.categories_id = ptoc.categories_id) 
            ' . $this->attributes_join . '
            WHERE TRUE ';
    }

    /**
     * @return mixed|void
     * @since ZC v1.5.8
     */
    protected function processSQL()
    {
        $this->setSQL();
        $this->runSQL();

        foreach ($this->results as $result) {
            if (in_array($result['categories_id'], $this->exclude)) {
                continue;
            }
            $this->values[] = [
                'id' => $result['categories_id'],
                'text' => $this->categoryText($result),
            ];
        }
    }

    /**
     * @param $category
     *
     * @return string|string[]|null
     * @since ZC v1.5.8
     */
    private function categoryText(array $category)
    {
        if (!empty($this->attributes_join)) {
            if ($this->show_full_path) {
                return zen_output_generated_category_path($category['categories_id']);
            }
            return $category['categories_name'];
        }
        $parent = '';
        if ($this->show_parent) {
            $parent = zen_get_categories_parent_name($category['categories_id']);
            if ($parent != '') {
                $parent = ' : in ' . $parent;
            }
        }
        return $category['categories_name'] . $parent . ($this->show_id ? ' - ID# ' . $category['categories_id'] : '');
    }
}
