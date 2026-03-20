<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

use Tests\Support\zcUnitTestCase;

/**
 * Class testAdminSanitization
 */
class AdminSanitizationTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        global $PHP_SELF;
        $serverScript = basename((string) $_SERVER['SCRIPT_NAME']);
        $PHP_SELF = isset($_SERVER['SCRIPT_NAME']) ? $serverScript : 'home.php';
        if (basename($PHP_SELF, '.php') === 'index') {
            $PHP_SELF = isset($_GET['cmd']) ? basename($_GET['cmd'] . '.php') : $PHP_SELF;
        }
        $PHP_SELF = htmlspecialchars($PHP_SELF, ENT_COMPAT);

        parent::setUp();
        require_once(DIR_FS_CATALOG . '/admin/includes/classes/AdminRequestSanitizer.php');
    }

    public function testInstanceInstantitation(): void
    {
        $arq = AdminRequestSanitizer::getInstance();
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 0);
    }

    public function testDebugInstantitation(): void
    {
        $arq = new AdminRequestSanitizer();
        $arq->setDebug(true);
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 0);
        $this->assertTrue($arq->getDebug() === true);
    }

    public function testSimpleAlphaNumPlus(): void
    {
        $arq = new AdminRequestSanitizer();
        $arq->setDebug(true);
        $group = [
            'action_get',
            'add_products_id_get',
            'attribute_id_get',
            'attribute_page_get',
            'action_post',
            'add_products_id_post',
            'attribute_id_post',
            'attribute_page_post',
        ];
        $adminSanitizerTypes = ['SIMPLE_ALPHANUM_PLUS' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('SIMPLE_ALPHANUM_PLUS', $group);
        $arq->runSanitizers();
        $_GET = [
            'action_get' => 'test<',
            'add_products_id_get' => 'alert();',
            'attribute_id_get' => '&nbsp;',
            'attribute_page_get' => '</script>',
        ];
        $_POST = [
            'action_post' => 'test<',
            'add_products_id_post' => 'alert();',
            'attribute_id_post' => '&nbsp;',
            'attribute_page_post' => '</script>',
        ];
        $arq->runSanitizers();
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 4);
        $this->assertTrue($_GET['action_get'] == 'test');
        $this->assertTrue($_GET['add_products_id_get'] == 'alert');
        $this->assertTrue($_GET['attribute_id_get'] == 'nbsp');
        $this->assertTrue($_GET['attribute_page_get'] == '/script');
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 4);
        $this->assertTrue($_POST['action_post'] == 'test');
        $this->assertTrue($_POST['add_products_id_post'] == 'alert');
        $this->assertTrue($_POST['attribute_id_post'] == 'nbsp');
        $this->assertTrue($_POST['attribute_page_post'] == '/script');
    }

    public function testConvertInt(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'action',
            'add_products_id',
            'attribute_id',
            'attribute_page',
        ];
        $adminSanitizerTypes = ['CONVERT_INT' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('CONVERT_INT', $group);
        $arq->runSanitizers();
        $group = [
            'id' => ['sanitizerType' => 'CONVERT_INT', 'method' => 'both', 'pages' => ['edit_orders']],
        ];
        $arq->addComplexSanitization($group);

        $_GET = [
            'id' => '1k',
            'action' => '100',
            'add_products_id' => 'alert();',
            'attribute_id' => '&nbsp;',
            'attribute_page' => '</script>',
        ];
        $_POST = [
            'id' => '1k',
            'action' => '100',
            'add_products_id' => 'alert();',
            'attribute_id' => '&nbsp;',
            'attribute_page' => '</script>',
        ];

        $arq->runSanitizers();

        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 4);
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 4);
        $this->assertTrue($_GET['action'] == 100);
        $this->assertTrue($_GET['add_products_id'] == 0);
        $this->assertTrue($_GET['attribute_id'] == 0);
        $this->assertTrue($_GET['attribute_page'] == 0);
        $this->assertTrue($_POST['action'] == 100);
        $this->assertTrue($_POST['add_products_id'] == 0);
        $this->assertTrue($_POST['attribute_id'] == 0);
        $this->assertTrue($_POST['attribute_page'] == 0);
    }

    public function testFileDirRegex(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'img_dir_safe',
            'img_dir_not_safe',
            'img_dir_windows',
            'img_dir_linux',
            'img_dir_linux_space',
        ];
        $adminSanitizerTypes = ['FILE_DIR_REGEX' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('FILE_DIR_REGEX', $group);
        $_POST = [
            'img_dir_safe' => '100',
            'img_dir_not_safe' => 'alert();',
            'img_dir_windows' => 'matrox\matrox.gif',
            'img_dir_linux' => 'matrox/matrox.gif',
            'img_dir_linux_space' => 'mat rox/matrox.gif',
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 5);
        $this->assertTrue($_POST['img_dir_safe'] == 100);
        $this->assertTrue($_POST['img_dir_not_safe'] === 'alert()');
        $this->assertTrue($_POST['img_dir_windows'] == 'matrox\matrox.gif');
        $this->assertTrue($_POST['img_dir_linux'] === 'matrox/matrox.gif');
        $this->assertTrue($_POST['img_dir_linux_space'] === 'mat rox/matrox.gif');
    }

    public function testAlphaNumDashUnderScore(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'action_safe_post',
            'action_not_safe_post',
            'action_safe_get',
            'action_not_safe_get',
        ];
        $adminSanitizerTypes = ['ALPHANUM_DASH_UNDERSCORE' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('ALPHANUM_DASH_UNDERSCORE', $group);

        $_POST = ['action_safe_post' => '100xyz_-', 'action_not_safe_post' => '100xyz_</script>();'];
        $_GET = ['action_safe_get' => '100xyz_-', 'action_not_safe_get' => '100xyz_</script>();'];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['action_safe_post'] == '100xyz_-');
        $this->assertTrue($_POST['action_not_safe_post'] === '100xyz_script');
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 2);
        $this->assertTrue($_GET['action_safe_get'] == '100xyz_-');
        $this->assertTrue($_GET['action_not_safe_get'] === '100xyz_script');
    }

    public function testMetaTags(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'metatags_title_safe',
            'metatags_title_not_safe',
        ];
        $adminSanitizerTypes = ['META_TAGS' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('META_TAGS', $group);

        $_POST = ['metatags_title_safe' => ['100xyz_-'], 'metatags_title_not_safe' => ['100xyz_</script>();']];

        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['metatags_title_safe'][0] == '100xyz_-');
        $this->assertTrue($_POST['metatags_title_not_safe'][0] == '100xyz_&lt;/script&gt;();');
    }

    public function testSanitizeEmail(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'customers_email_address_safe_post',
            'customers_email_address_not_safe_post',
            'customers_email_address_safe_get',
            'customers_email_address_not_safe_get',
        ];
        $adminSanitizerTypes = ['SANITIZE_EMAIL' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('SANITIZE_EMAIL', $group);

        $_POST = [
            'customers_email_address_safe_post' => 'xyz@domain.com',
            'customers_email_address_not_safe_post' => '100xyz_</script>();',
        ];
        $_GET = [
            'customers_email_address_safe_get' => 'xyz@domain.com',
            'customers_email_address_not_safe_get' => '100xyz_</script>();',
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['customers_email_address_safe_post'] == 'xyz@domain.com');
        $this->assertTrue($_POST['customers_email_address_not_safe_post'] === '100xyz_script');
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 2);
        $this->assertTrue($_GET['customers_email_address_safe_get'] == 'xyz@domain.com');
        $this->assertTrue($_GET['customers_email_address_not_safe_get'] === '100xyz_script');
    }

    public function testProductDescRegex(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'products_description_safe_deep',
            'products_description_not_safe_deep',
            'products_description_safe',
            'products_description_not_safe',
        ];
        $adminSanitizerTypes = ['PRODUCT_DESC_REGEX' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('PRODUCT_DESC_REGEX', $group);

        $_POST = [
            'products_description_safe' => 'xyz@domain.com',
            'products_description_not_safe' => '100xyz_</script>();',
            'products_description_safe_deep' => ['xyz@domain.com'],
            'products_description_not_safe_deep' => ['100xyz_</script>();'],
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 4);
        $this->assertTrue($_POST['products_description_safe_deep'][0] == 'xyz@domain.com');
        $this->assertTrue($_POST['products_description_not_safe_deep'][0] === '100xyz_</script>();');
        $this->assertTrue($_POST['products_description_safe'] == 'xyz@domain.com');
        $this->assertTrue($_POST['products_description_not_safe'] === '100xyz_</script>();');
    }

    public function testProductUrlRegex(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'products_url_safe',
            'products_url_not_safe',
        ];
        $adminSanitizerTypes = ['PRODUCT_URL_REGEX' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('PRODUCT_URL_REGEX', $group);
        $_POST = [
            'products_url_safe' => ['100xyz_</script>();'],
            'products_url_not_safe' => ['100xyz_</script>();££'],
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['products_url_safe'][0] == '100xyz_</script>();');
        $this->assertTrue($_POST['products_url_not_safe'][0] === '100xyz_</script>();');
    }

    public function testCurrencyValueRegex(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'currency_value_safe',
            'currency_value_not_safe',
        ];
        $adminSanitizerTypes = ['CURRENCY_VALUE_REGEX' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('CURRENCY_VALUE_REGEX', $group);

        $_POST = ['currency_value_safe' => '-10,000.00', 'currency_value_not_safe' => '-10000.00alert();'];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['currency_value_safe'] == '-10,000.00');
        $this->assertTrue($_POST['currency_value_not_safe'] == '-10000.00alert');
    }

    public function testFloatValueRegex(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'float_value_safe',
            'float_value_not_safe',
        ];
        $adminSanitizerTypes = ['FLOAT_VALUE_REGEX' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('FLOAT_VALUE_REGEX', $group);

        $_POST = ['float_value_safe' => '-10,000.00', 'float_value_not_safe' => '+10.000,00alert();'];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['float_value_safe'] == '-10,000.00');
        $this->assertTrue($_POST['float_value_not_safe'] == '+10.000,00');
    }

    public function testProductNameDeepRegex(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'products_name_safe',
            'products_name_not_safe',
        ];
        $adminSanitizerTypes = ['PRODUCT_NAME_DEEP_REGEX' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('PRODUCT_NAME_DEEP_REGEX', $group);

        $_POST = [
            'products_name_safe' => ['<strong>Name</strong>'],
            'products_name_not_safe' => ['100xyz_</script>();'],
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['products_name_safe'][0] == '<strong>Name</strong>');
        $this->assertTrue($_POST['products_name_not_safe'][0] === '100xyz_pt>();');
    }

    public function testWordsAndSymbolsRegex(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'products_name_safe_post',
            'products_name_not_safe_post',
            'products_name_safe_get',
            'products_name_not_safe_get',
        ];
        $adminSanitizerTypes = ['WORDS_AND_SYMBOLS_REGEX' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('WORDS_AND_SYMBOLS_REGEX', $group);

        $_GET = ['products_name_safe_get' => '<strong>Name</strong>', 'products_name_not_safe_get' => '100xyz_</script>();'];
        $_POST = [
            'products_name_safe_post' => '<strong>Name</strong>',
            'products_name_not_safe_post' => '100xyz_</script>();',
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['products_name_safe_post'] == '<strong>Name</strong>');
        $this->assertTrue($_POST['products_name_not_safe_post'] === '100xyz_pt>();');
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 2);
        $this->assertTrue($_GET['products_name_safe_get'] == '<strong>Name</strong>');
        $this->assertTrue($_GET['products_name_not_safe_get'] === '100xyz_pt>();');
    }

    public function testStrictSanitizeKeys(): void
    {
        $arq = new AdminRequestSanitizer();
        $_POST = ['some_post_OK' => '<strong>Name</strong>', 'some_pst_NOTOK<>' => '100xyz_</script>();'];
        $_GET = ['some_get_OK' => '<strong>Name</strong>', 'some_get_NOTOK<>' => '100xyz_</script>();'];
        $arq->setDoStrictSanitization(true);
        $arq->runSanitizers();
        $this->assertTrue(isset($_POST['some_post_OK']));
        $this->assertTrue(isset($_GET['some_get_OK']));
        $this->assertTrue(!isset($_POST['some_pst_NOTOK<>']));
        $this->assertTrue(!isset($_GET['some_get_NOTOK<>']));
    }

    public function testStrictSanitizeValues(): void
    {
        $arq = new AdminRequestSanitizer();
        $adminSanitizerTypes = ['STRICT_SANITIZE_VALUES' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $group = ['some_param_ignore'];
        $arq->addSimpleSanitization('STRICT_SANITIZE_VALUES', $group);

        $_POST = [
            'some_param_ignore' => '<strong>Name</strong>',
            'some_param_simple' => '100xyz_</script>();',
            'some_param_array' => ['100xyz_</script>();'],
            'some_param_deep_array' => [['100xyz_</script>();']],
        ];

        $arq->setDoStrictSanitization(false);
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 6);
        $this->assertTrue($_POST['some_param_ignore'] == '<strong>Name</strong>');
        $this->assertTrue($_POST['some_param_simple'] == '100xyz_&lt;/script&gt;();');
        $this->assertTrue($_POST['some_param_array'][0] == '100xyz_&lt;/script&gt;();');
        $this->assertTrue($_POST['some_param_deep_array'][0][0] == '100xyz_&lt;/script&gt;();');
    }

    public function testMultiDimensional(): void
    {
        global $PHP_SELF;
        $PHP_SELF = 'edit_orders.php';
        $arq = new AdminRequestSanitizer();
        $group = [
            'update_products' => [
                'sanitizerType' => 'MULTI_DIMENSIONAL',
                'method' => 'post',
                'pages' => ['edit_orders'],
                'params' => [
                    'update_products' => ['sanitizerType' => 'CONVERT_INT'],
                    'qty' => ['sanitizerType' => 'CONVERT_INT'],
                    'name' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                    'onetime_charges' => ['sanitizerType' => 'CURRENCY_VALUE_REGEX'],
                    'attr' => [
                        'sanitizerType' => 'MULTI_DIMENSIONAL',
                        'params' => [
                            'attr' => ['sanitizerType' => 'CONVERT_INT'],
                            'value' => ['sanitizerType' => 'CONVERT_INT'],
                            'type' => ['sanitizerType' => 'CONVERT_INT'],
                        ],
                    ],
                    'model' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                    'tax' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                    'final_price' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                ],
            ],
        ];
        $adminSanitizerTypes = [
            'MULTI_DIMENSIONAL' => ['type' => 'builtin'],
            'CONVERT_INT' => ['type' => 'builtin'],
            'WORDS_AND_SYMBOLS_REGEX' => ['type' => 'builtin'],
            'ALPHANUM_DASH_UNDERSCORE' => ['type' => 'builtin'],
            'CURRENCY_VALUE_REGEX' => ['type' => 'builtin'],
        ];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addComplexSanitization($group);

        $_POST = [
            'update_products' => [
                [
                    'name' => 'product_name1<script>',
                    'qty' => '5x',
                    'onetime_charges' => '1.00WZR',
                    'model' => 'model1',
                    'tax' => '1.00',
                    'final_price' => '1.00',
                    'attr' => [['value' => '1value1', 'type' => 1], ['value' => '2value2', 'type' => 2]],
                ],
                [
                    'name' => 'product_name2',
                    'qty' => '6',
                    'onetime_charges' => '2.00',
                    'model' => 'model2',
                    'tax' => '2.00',
                    'final_price' => '2.00',
                ],
            ],
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 20);
        $this->assertTrue($_POST['update_products'][0]['name'] == 'product_name1pt>');
        $this->assertTrue($_POST['update_products'][0]['qty'] == '5');
        $this->assertTrue($_POST['update_products'][0]['attr'][0]['value'] == '1');
    }

    public function testMultiDimensionalLogError(): void
    {
        global $PHP_SELF;
        $PHP_SELF = 'edit_orders.php';
        $arq = new AdminRequestSanitizer();
        $group = [
            'update_products' => [
                'sanitizerType' => 'MULTI_DIMENSIONAL',
                'method' => 'post',
                'pages' => ['edit_orders'],
                'params' => [
                    'update_products' => ['sanitizerType' => 'CONVERT_INT'],
                    'qty' => ['sanitizerType' => 'CONVERT_INT'],
                    'name' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                    'onetime_charges' => ['sanitizerType' => 'CURRENCY_VALUE_REGEX'],
                    'attr' => [
                        'sanitizerType' => 'MULTI_DIMENSIONAL',
                        'params' => [
                            'attr' => ['sanitizerType' => 'CONVERT_INT'],
                            'value' => ['sanitizerType' => 'CONVERT_INT'],
                            'type' => ['sanitizerType' => 'CONVERT_INT'],
                        ],
                    ],
                    'model' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                    'tax' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                    'final_price' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX'],
                ],
            ],
        ];
        $adminSanitizerTypes = [
            'MULTI_DIMENSIONAL' => ['type' => 'builtin'],
            'CONVERT_INT' => ['type' => 'builtin'],
            'WORDS_AND_SYMBOLS_REGEX' => ['type' => 'builtin'],
            'ALPHANUM_DASH_UNDERSCORE' => ['type' => 'builtin'],
            'CURRENCY_VALUE_REGEX' => ['type' => 'builtin'],
        ];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addComplexSanitization($group);

        $_POST = [];
        $arq->runSanitizers();
    }

    public function testHasGetHasPost(): void
    {
        $arq = new AdminRequestSanitizer();
        $adminSanitizerTypes = ['CONVERT_INT' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $group = [
            'idg' => ['sanitizerType' => 'CONVERT_INT', 'method' => 'get', 'pages' => null],
        ];
        $arq->addComplexSanitization($group);
        $group = [
            'idp' => ['sanitizerType' => 'CONVERT_INT', 'method' => 'post', 'pages' => null],
        ];
        $arq->addComplexSanitization($group);

        $_GET = [
            'idg' => '1k',
        ];
        $_POST = [
            'idp' => '1k',
        ];
        $arq->runSanitizers();

        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 1);
        $this->assertTrue($_GET['idg'] == 1);
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 1);
        $this->assertTrue($_POST['idp'] == 1);

    }

    public function testNullAction(): void
    {
        $arq = new AdminRequestSanitizer();
        $group = [
            'products_name_safe',
            'products_name_not_safe',
        ];
        $adminSanitizerTypes = ['NULL_ACTION' => ['type' => 'builtin']];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $arq->addSimpleSanitization('NULL_ACTION', $group);
        $_GET = [
            'products_name_safe' => '<strong>Name</strong>',
            'products_name_not_safe' => '100xyz_</script>();',
        ];

        $_POST = [
            'products_name_safe' => '<strong>Name</strong>',
            'products_name_not_safe' => '100xyz_</script>();',
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 2);
        $this->assertTrue($_POST['products_name_safe'] == '<strong>Name</strong>');
        $this->assertTrue($_POST['products_name_not_safe'] === '100xyz_</script>();');
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($getAlreadySanitized) == 2);
        $this->assertTrue($_GET['products_name_safe'] == '<strong>Name</strong>');
        $this->assertTrue($_GET['products_name_not_safe'] === '100xyz_</script>();');
    }

    public function testCustomFilter(): void
    {
        $arq = new AdminRequestSanitizer();
        $adminSanitizerTypes = [
            'CUSTOM_TEST' => [
                'type' => 'custom',
                'function' => function ($arq, $parameterName): void {
                    if (isset($_POST[$parameterName])) {
                        $arq->setPostKeyAlreadySanitized($parameterName);
                        $_POST[$parameterName] = preg_replace('/[^\/ 0-9a-zA-Z_:@.-]/', '', (string) $_POST[$parameterName]);
                    }
                    if (isset($_GET[$parameterName])) {
                        $arq->setGetKeyAlreadySanitized($parameterName);
                        $_GET[$parameterName] = preg_replace('/[^\/ 0-9a-zA-Z_:@.-]/', '', (string) $_GET[$parameterName]);
                    }

                },
            ],
        ];
        $arq->addSanitizerTypes($adminSanitizerTypes);
        $group = [
            'products_name_post',
            'products_name_get',
        ];
        $arq->addSimpleSanitization('CUSTOM_TEST', $group);
        $_POST = [
            'products_name_post' => '<strong>Name</strong>',
        ];
        $_GET = [
            'products_name_get' => '<strong>Name</strong>',
        ];
        $arq->runSanitizers();
        $postAlreadySanitized = $arq->getPostKeysAlreadySanitized();
        $getAlreadySanitized = $arq->getGetKeysAlreadySanitized();
        $this->assertTrue(count($postAlreadySanitized) == 1);
        $this->assertTrue(count($getAlreadySanitized) == 1);
        $this->assertTrue($_POST['products_name_post'] == 'strongName/strong');
    }

}
