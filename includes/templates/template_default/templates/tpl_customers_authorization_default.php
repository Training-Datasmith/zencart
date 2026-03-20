<?php

/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=customers_authorization.
 * Displays information if customer authorization checks fail.
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: lat9 2025 Sep 24 Modified in v2.2.0 $
 */
?>
<div class="centerColumn" id="customerAuthDefault">
    <h1 id="customerAuthDefaultHeading"><?php 
echo $customer_authorization_heading_title;
?></h1>
<?php 
if ($message_stack->size('account') > 0) {
    echo $message_stack->output('account');
}
?>
    <div id="customerAuthDefaultImage">
        <?php 
echo zen_image(DIR_WS_TEMPLATE_IMAGES . OTHER_IMAGE_CUSTOMERS_AUTHORIZATION, OTHER_IMAGE_CUSTOMERS_AUTHORIZATION_ALT);
?>
    </div>

    <div id="customerAuthDefaultMainContent" class="content">
        <?php 
echo $main_content;
?>
    </div>

    <div id="customerAuthDefaultSecondaryContent" class="content"><?php 
echo CUSTOMERS_AUTHORIZATION_STATUS_TEXT;
?></div>

    <div class="buttonRow forward">
        <a href="<?php 
echo zen_href_link(CUSTOMERS_AUTHORIZATION_FILENAME, '', 'SSL');
?>">
            <?php 
echo zen_image_button(BUTTON_IMAGE_CONTINUE, BUTTON_CONTINUE_ALT);
?>
        </a>
    </div>
</div>
