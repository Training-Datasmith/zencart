<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Breadcrumb;
use Tests\Support\zcUnitTestCase;

if (!defined('HEADER_TITLE_CATALOG')) {
    define('HEADER_TITLE_CATALOG', __DIR__);
}

if (!defined('HTTP_SERVER')) {
    define('HTTP_SERVER', __FILE__);
}

if (!defined('DIR_WS_CATALOG')) {
    define('DIR_WS_CATALOG', __LINE__);
}

/**
 * @see includes/classes/breadcrumb.php
 */
class BreadcrumbTest extends zcUnitTestCase
{
    private ?\Breadcrumb $breadcrumb = null;

    private array $links = [
        'Zen Cart' => 'https://zen-cart.com',
    ];

    public function setup(): void
    {
        parent::setup();
        require_once DIR_FS_CATALOG . 'includes/classes/breadcrumb.php';
    }

    public function testAddThrowsExceptionIfEmptyDataPassed(): void
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet. Relates to future feature.'
        );

        $this->breadcrumb = new Breadcrumb();
        $this->setExpectedException(
            'InvalidArgumentException',
            'Both title and link must not be empty.'
        );
        $this->breadcrumb->add('');
    }

    public function testTrailGeneratesHtml(): void
    {
        $this->markTestSkipped();
        $expected = '<nav class="breadcrumb">';
        $expected .= '<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="crumb">';
        $expected .= '<link itemprop="url" href="https://zen-cart.com" class="link" />';
        $expected .= '<span itemprop="title" class="title">Zen Cart</span>';
        $expected .= "</span></nav>\n";

        $this->breadcrumb = new Breadcrumb();
        foreach ($this->links as $title => $link) {
            $this->breadcrumb->add($title, $link);
        }
        $this->assertEquals($expected, $this->breadcrumb->trail());
    }

    public function testCatalogTitleReplaced(): void
    {
        $this->breadcrumb = new Breadcrumb();
        foreach ($this->links as $title => $link) {
            $this->breadcrumb->add($title, $link);
        }
        $this->breadcrumb->add(HEADER_TITLE_CATALOG, 'foo');
        $this->assertStringNotContainsString('foo', $this->breadcrumb->trail());
    }

    public function testStringCastEqualsTrail(): void
    {
        $this->markTestSkipped();

        $this->breadcrumb = new Breadcrumb();
        foreach ($this->links as $title => $link) {
            $this->breadcrumb->add($title, $link);
        }
        $this->assertSame((string)$this->breadcrumb, $this->breadcrumb->trail());
    }

    public function testLastReturnsString(): void
    {
        $this->breadcrumb = new Breadcrumb();
        foreach ($this->links as $title => $link) {
            $this->breadcrumb->add($title, $link);
        }
        $this->assertSame('Zen Cart', $this->breadcrumb->last());
    }
}
