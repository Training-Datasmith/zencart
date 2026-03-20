<?php

declare (strict_types=1);
/**
 * products class
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
 * products class
 * Deprecated class formerly used for managing various product information
 *
 * @deprecated v2.1.0 - use Product class instead
 * or call zen_get_products_name(), zen_get_handler_from_type(), zen_get_products_allow_add_to_cart()
 * @since ZC v1.2.0d
 */
class products extends base
{
    /**
     * @since ZC v1.2.0d
     * @return mixed[]
     */
    public function get_products_in_category($zf_category_id, $zf_recurse = true, $zf_product_ids_only = false): array
    {
        global $db;
        $za_products_array = [];
        // get top level products
        $zp_products_query = 'select ptc.*, pd.products_name
                            from ' . TABLE_PRODUCTS_TO_CATEGORIES . ' ptc
                            left join ' . TABLE_PRODUCTS_DESCRIPTION . " pd\n                            on ptc.products_id = pd.products_id\n                            and pd.language_id = '" . (int) $_SESSION['languages_id'] . "'\n                            where ptc.categories_id='" . (int) $zf_category_id . "'\n                            order by pd.products_name";
        $zp_products = $db->Execute($zp_products_query);
        while (!$zp_products->EOF) {
            if ($zf_product_ids_only) {
                $za_products_array[] = $zp_products->fields['products_id'];
            } else {
                $za_products_array[] = ['id' => $zp_products->fields['products_id'], 'text' => $zp_products->fields['products_name']];
            }
            $zp_products->move_next();
        }
        if ($zf_recurse) {
            $zp_categories_query = 'select categories_id from ' . TABLE_CATEGORIES . "\n                                where parent_id = '" . (int) $zf_category_id . "'";
            $zp_categories = $db->Execute($zp_categories_query);
            while (!$zp_categories->EOF) {
                $za_sub_products_array = $this->get_products_in_category($zp_categories->fields['categories_id'], true, $zf_product_ids_only);
                $za_products_array = array_merge($za_products_array, $za_sub_products_array);
                $zp_categories->move_next();
            }
        }
        return $za_products_array;
    }
    /**
     * @since ZC v1.2.0d
     */
    public function products_name($zf_product_id)
    {
        global $db;
        $zp_product_name_query = 'select products_name from ' . TABLE_PRODUCTS_DESCRIPTION . "\n                                where language_id = '" . $_SESSION['languages_id'] . "'\n                                and products_id = '" . (int) $zf_product_id . "'";
        $zp_product_name = $db->Execute($zp_product_name_query);
        return $zp_product_name->fields['products_name'];
    }
    /**
     * @since ZC v1.2.0d
     */
    public function get_admin_handler($type): string
    {
        return $this->get_handler($type) . '.php';
    }
    /**
     * @since ZC v1.2.0d
     */
    public function get_handler($type)
    {
        global $db;
        // this is a fallback safety to protect against damaged (inaccessible) data caused by incorrect code in custom product types
        if ((int) $type == 0) {
            $type = 1;
        }
        $sql = 'select type_handler from ' . TABLE_PRODUCT_TYPES . " where type_id = '" . (int) $type . "'";
        $handler = $db->Execute($sql);
        return $handler->fields['type_handler'];
    }
    /**
     * @since ZC v1.2.0d
     */
    public function get_allow_add_to_cart($zf_product_id)
    {
        global $db;
        $sql = 'select products_type from ' . TABLE_PRODUCTS . " where products_id='" . (int) $zf_product_id . "'";
        $result = $db->Execute($sql);
        if ($result->EOF) {
            return false;
        }
        $sql = 'select allow_add_to_cart from ' . TABLE_PRODUCT_TYPES . " where type_id = '" . (int) $result->fields['products_type'] . "'";
        $result = $db->Execute($sql);
        return !$result->EOF ? $result->fields['allow_add_to_cart'] : 0;
    }
}