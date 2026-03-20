<?php

/**
 * Page Template
 *
 * Main index page
 * Displays greetings, welcome text (define-page content), and various centerboxes depending on switch settings in Admin
 * Centerboxes are called as necessary
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: simon1066 2025 Jan 13 Modified in v2.2.0 $
 */
?>
<div class="centerColumn" id="indexDefault">
<h1 id="indexDefaultHeading"><?php 
echo HEADING_TITLE;
?></h1>

<?php 
if (SHOW_CUSTOMER_GREETING == 1) {
    ?>
<h2 class="greeting"><?php 
    echo zen_customer_greeting();
    ?></h2>
<?php 
}
?>

<?php 
if (DEFINE_MAIN_PAGE_STATUS >= 1 and DEFINE_MAIN_PAGE_STATUS <= 2) {
    /**
     * get the Define Main Page Text
     */
    ?>
<div id="indexDefaultMainContent" class="content"><?php 
    require $define_page;
    ?></div>
<?php 
}
?>

<?php 
$show_display_category = $db->Execute(SQL_SHOW_PRODUCT_INFO_MAIN);
while (!$show_display_category->EOF) {
    if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MAIN_FEATURED_PRODUCTS') {
        /**
         * display the Featured Products Center Box
         */
        require $template->get_template_dir('tpl_modules_featured_products.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_featured_products.php';
    }
    ?>

<?php 
    if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MAIN_SPECIALS_PRODUCTS') {
        /**
         * display the Special Products Center Box
         */
        require $template->get_template_dir('tpl_modules_specials_default.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_specials_default.php';
    }
    ?>

<?php 
    if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MAIN_NEW_PRODUCTS') {
        /**
         * display the New Products Center Box
         */
        require $template->get_template_dir('tpl_modules_whats_new.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_whats_new.php';
    }
    ?>

<?php 
    if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MAIN_UPCOMING') {
        /**
         * display the Upcoming Products Center Box
         */
        include DIR_WS_MODULES . zen_get_module_directory(FILENAME_UPCOMING_PRODUCTS);
    }
    ?>

<?php 
    if ($show_display_category->fields['configuration_key'] == 'SHOW_PRODUCT_INFO_MAIN_FEATURED_CATEGORIES') {
        /**
         * display the Featured Categories Center Box
         */
        require $template->get_template_dir('tpl_modules_featured_categories.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_featured_categories.php';
    }
    ?>


<?php 
    $show_display_category->move_next();
}
// !EOF
?>
</div>

