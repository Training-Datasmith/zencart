<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 29 Modified in v2.2.0 $
 */
namespace Zencart\View_Builders;

use Zencart\Db_Repositories\Plugin_Control_Repository;
use Zencart\Plugin_Support\Plugin_Status;
/**
 * @since ZC v1.5.8
 */
class Plugin_Manager_Data_Source extends Data_Table_Data_Source
{
    /**
     * @since ZC v1.5.8
     */
    protected function build_initial_query(): array
    {
        global $db;
        $status_sort = [
            Plugin_Status::ENABLED,
            // enabled
            Plugin_Status::DISABLED,
            // disabled
            Plugin_Status::NOT_INSTALLED,
        ];
        $rows = (new Plugin_Control_Repository($db))->get_all();
        $status_order = array_flip($status_sort);
        usort($rows, function (array $a, array $b) use ($status_order): int {
            $status_a = $status_order[(int) ($a['status'] ?? Plugin_Status::NOT_INSTALLED)] ?? 999;
            $status_b = $status_order[(int) ($b['status'] ?? Plugin_Status::NOT_INSTALLED)] ?? 999;
            if ($status_a !== $status_b) {
                return $status_a <=> $status_b;
            }
            $name_cmp = strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            if ($name_cmp !== 0) {
                return $name_cmp;
            }
            return strcasecmp((string) ($a['unique_key'] ?? ''), (string) ($b['unique_key'] ?? ''));
        });
        return $rows;
    }
}