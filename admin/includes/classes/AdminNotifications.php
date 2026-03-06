<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 * @since ZC v1.5.6
 */

class AdminNotifications
{
    protected $enabled = true;
    private ?string $projectNotificationServer = null;

    public function __construct()
    {
        if (defined('PROJECT_NOTIFICATIONSERVER_URL')) {
            $this->projectNotificationServer = PROJECT_NOTIFICATIONSERVER_URL;
        }

        if (defined('DISABLE_ADMIN_NOTIFICATIONS_CHECKING') && DISABLE_ADMIN_NOTIFICATIONS_CHECKING === true) {
            $this->enabled = false;
        }

        global $sniffer;
        if (!$sniffer->table_exists(TABLE_ADMIN_NOTIFICATIONS)) {
            $this->enabled = false;
        }
    }

    /**
     * @since ZC v1.5.6
     * @return mixed[]
     */
    public function getNotifications($target, $adminId): array
    {
        if ($this->enabled === false) {
            return [];
        }

        $notificationList = $this->getNotificationInfo();
        if (empty($notificationList)) {
            return [];
        }

        $this->pruneSavedState($notificationList);
        $savedState = $this->getSavedState($adminId);
        $result = [];
        foreach ($notificationList as $name => $notification) {
            if ($this->isNotificationAvailable($name, $target, $notification, $savedState)) {
                $result[$name] = $notification;
            }
        }
        return $result;
    }

    /**
     * @since ZC v1.5.6
     */
    protected function getNotificationInfo()
    {
        if (empty($this->projectNotificationServer)) {
            return [];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->projectNotificationServer);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 9);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Notification Messages Check');
        $response = curl_exec($ch);
        curl_error($ch);
        $errno = curl_errno($ch);
        if ($errno > 0) {
            return [];
        }
        return json_decode($response, true);
    }

    /**
     * @since ZC v1.5.6
     */
    protected function isNotificationAvailable($name, $target, array $notification, $savedState): bool
    {
        if ($notification['target'] !== $target) {
            return false;
        }
        if ($this->isNotificationDismissed($name, $savedState)) {
            return false;
        }
        if (!$this->isNotificationInDate($notification, $this->getCurrentDate())) {
            return false;
        }
        if (!$this->isNotificationInCountry($notification)) {
            return false;
        }
        return true;
    }

    /**
     * @since ZC v1.5.6
     */
    protected function isNotificationDismissed($name, array $savedState)
    {
        if (!isset($savedState[$name])) {
            return false;
        }
        return $savedState[$name]['dismissed'];
    }

    /**
     * @since ZC v1.5.6
     */
    protected function isNotificationInDate(array $notification, $currentDatetime): bool
    {
        if (!isset($notification['start-date']) && !isset($notification['end-date'])) {
            return true;
        }
        if (isset($notification['start-date']) && $currentDatetime > $notification['start-date']) {
            return false;
        }
        if (isset($notification['end-date']) && $currentDatetime < $notification['end-date']) {
            return false;
        }
        return true;
    }

    /**
     * @since ZC v1.5.6
     */
    protected function isNotificationInCountry(array $notification): bool
    {
        if (!isset($notification['countries'])) {
            return true;
        }
        $iso3 = $this->getStoreCountryIso3();
        if (!in_array($iso3, $notification['countries'])) {
            return false;
        }
        return true;
    }

    /**
     * @since ZC v1.5.6
     */
    protected function getStoreCountryIso3()
    {
        global $db;

        $sql = 'SELECT countries_iso_code_3 from ' . TABLE_COUNTRIES . ' WHERE countries_id = ' . STORE_COUNTRY;
        $r = $db->execute($sql);
        return $r->fields['countries_iso_code_3'];
    }

    /**
     * @since ZC v1.5.6
     * @return array{dismissed: mixed}[]
     */
    protected function getSavedState($adminId): array
    {
        global $db;

        $savedState = [];
        $sql = 'SELECT * FROM ' . TABLE_ADMIN_NOTIFICATIONS . ' WHERE admin_id = :adminId:';
        $sql = $db->bindVars($sql, ':adminId:', $adminId, 'integer');
        $results = $db->execute($sql);
        foreach ($results as $result) {
            $savedState[$result['notification_key']]['dismissed'] = $result['dismissed'];
        }
        return $savedState;
    }

    /**
     * @since ZC v1.5.6
     */
    protected function getCurrentDate(): \DateTime
    {
        return new DateTime('now');
    }

    /**
     * @since ZC v1.5.6
     */
    protected function pruneSavedState($notificationList)
    {
        global $db;

        $keys = array_keys($notificationList);
        $keys = implode(',', $keys);
        $sql = 'DELETE FROM ' . TABLE_ADMIN_NOTIFICATIONS . ' WHERE notification_key NOT IN (:keys:)';
        $sql = $db->bindVars($sql, ':keys:', $keys, 'inConstructString');
        $db->execute($sql);
    }
}
