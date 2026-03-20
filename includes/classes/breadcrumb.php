<?php

declare (strict_types=1);
/**
 * breadcrumb Class.
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
 * The following switch simply checks to see if the setting is already defined, and if not, sets it to true
 * If you desire to have the older behaviour of having all product and category items in the breadcrumb be shown as links
 * then you should add a define() for this item in the extra_datafiles folder and set it to 'false' instead of 'true':
 */
if (!defined('DISABLE_BREADCRUMB_LINKS_ON_LAST_ITEM')) {
    define('DISABLE_BREADCRUMB_LINKS_ON_LAST_ITEM', 'true');
}
/**
 * Handle page breadcrumbs
 * @since ZC v1.0.3
 */
class breadcrumb extends base
{
    protected $_trail = [];
    public function __construct()
    {
        $this->reset();
    }
    /**
     * Clears all entries from the breadcrumb trail.
     *
     * @return void
     * @since  ZC v1.0.3
     */
    public function reset(): void
    {
        $this->_trail = [];
    }

    /**
     * Appends an entry to the breadcrumb trail.
     *
     * @param  string  $title  Human-readable label for the breadcrumb item.
     * @param  string  $link   URL for the breadcrumb link; empty string for plain-text items.
     * @return void
     * @since  ZC v1.0.3
     */
    public function add(string $title, string $link = ''): void
    {
        $this->_trail[] = ['title' => $title, 'link' => $link];
    }

    /**
     * Renders the breadcrumb trail as an HTML string.
     *
     * Each item is wrapped in optional prefix/suffix markup. Linked items
     * produce an <a> tag; the last item uses a plain text span when
     * DISABLE_BREADCRUMB_LINKS_ON_LAST_ITEM is 'true'. The home entry uses
     * a clean domain URL (not the main_page=index form).
     *
     * @param  string  $separator  HTML placed between breadcrumb items. Default: non-breaking space.
     * @param  string  $prefix     HTML inserted before each item label/link.
     * @param  string  $suffix     HTML inserted after each item label/link.
     * @return string              The rendered breadcrumb HTML string.
     * @since  ZC v1.0.3
     */
    public function trail(string $separator = '&nbsp;&nbsp;', string $prefix = '', string $suffix = ''): string
    {
        $trail_string = '';
        for ($i = 0, $n = count($this->_trail); $i < $n; $i++) {
            // echo 'breadcrumb ' . $i . ' of ' . $n . ': ' . $this->_trail[$i]['title'] . '<br>';
            $skip_link = false;
            if ($i == $n - 1 && DISABLE_BREADCRUMB_LINKS_ON_LAST_ITEM == 'true') {
                $skip_link = true;
            }
            if (!empty($this->_trail[$i]['link']) && !$skip_link) {
                // this line simply sets the "Home" link to be the domain/url, not main_page=index?blahblah:
                if ($this->_trail[$i]['title'] == HEADER_TITLE_CATALOG) {
                    $trail_string .= '  ' . $prefix . '<a href="' . zen_href_link('/', '', 'SSL', false, true, true) . '">' . $this->_trail[$i]['title'] . '</a>' . $suffix;
                } else {
                    $trail_string .= '  ' . $prefix . '<a href="' . $this->_trail[$i]['link'] . '">' . $this->_trail[$i]['title'] . '</a>' . $suffix;
                }
            } else if (isset($this->_trail[$i]['title'])) {
                $trail_string .= $prefix . $this->_trail[$i]['title'] . $suffix;
            }
            if ($i + 1 < $n) {
                $trail_string .= $separator;
            }
            $trail_string .= "\n";
        }
        return $trail_string;
    }
    /**
     * @since ZC v1.0.3
     */
    /**
     * Returns the title of the last breadcrumb entry.
     *
     * @return string  The title of the final breadcrumb item.
     * @since  ZC v1.0.3
     */
    public function last(): string
    {
        $trail_size = count($this->_trail);
        return $this->_trail[$trail_size - 1]['title'];
    }

    /**
     * Removes the last entry from the breadcrumb trail.
     *
     * @return void
     * @since  ZC v1.5.7c
     */
    public function remove_last(): void
    {
        $trail_size = count($this->_trail);
        unset($this->_trail[$trail_size - 1]);
    }

    /**
     * Replaces the title and/or link of the last breadcrumb entry.
     *
     * If both $title and $link are null, the last entry is removed entirely
     * (delegates to remove_last()). Passing only one updates only that field.
     *
     * @param  string|null  $title  New title for the last item, or null to leave unchanged.
     * @param  string|null  $link   New URL for the last item, or null to leave unchanged.
     * @return void
     * @since  ZC v1.5.7c
     */
    public function replace_last(?string $title = null, ?string $link = null): void
    {
        if ($title === null && $link === null) {
            $this->remove_last();
            return;
        }
        $trail_size = count($this->_trail);
        if ($title !== null) {
            $this->_trail[$trail_size - 1]['title'] = $title;
        }
        if ($link !== null) {
            $this->_trail[$trail_size - 1]['link'] = $link;
        }
    }

    /**
     * Returns whether the breadcrumb trail has no entries.
     *
     * @return bool  True if the trail is empty, false otherwise.
     * @since  ZC v1.5.7
     */
    public function is_empty(): bool
    {
        return empty($this->_trail);
    }

    /**
     * Returns the number of entries in the breadcrumb trail.
     *
     * @return int  Number of breadcrumb items.
     * @since  ZC v1.5.7
     */
    public function count(): int
    {
        return count($this->_trail);
    }

    /**
     * Returns the raw breadcrumb trail array.
     *
     * @return array<int, array{title: string, link: string}>  All breadcrumb entries.
     * @since  ZC v1.5.8a
     */
    public function get_trail(): array
    {
        return $this->_trail;
    }
}