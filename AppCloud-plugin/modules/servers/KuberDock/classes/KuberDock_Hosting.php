<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Query;
use base\CL_Tools;
use base\models\CL_MailTemplate;
use base\models\CL_Client;
use base\models\CL_Hosting;
use components\KuberDock_Units;
use components\KuberDock_InvoiceItem;

class KuberDock_Hosting extends CL_Hosting
{
    /**
     * Delete pod sign
     */
    const DELETED_POD_SIGN = '__';

    /**
     * Get all user active hosting services
     *
     * @param string $status CL_User::STATUS
     * @return array
     */
    public function getByUserStatus($status = '')
    {
        $db = CL_Query::model();

        $values = array($status, KUBERDOCK_MODULE_NAME, 'Active');

        $sql = "SELECT hosting.*, client.id AS client_id, product.id AS product_id
            FROM `".$this->tableName."` hosting
                LEFT JOIN `".KuberDock_Product::model()->tableName."` product ON hosting.packageid = product.id
                LEFT JOIN `".CL_Client::model()->tableName."` client ON hosting.userid = client.id";
        $sql .= $status ? " WHERE client.status = ? AND product.serverType = ? AND hosting.domainstatus = ?" : '';

        $rows = $db->query($sql, $values)->getRows();

        return $rows;
    }

    /**
     * Get user KuberDock services
     *
     * @param int $userId
     * @param string|null $serverUrl
     * @return array
     */
    public function getByUser($userId, $serverUrl = null)
    {
        $db = CL_Query::model();

        $values = array($userId, KUBERDOCK_MODULE_NAME);

        $sql = "SELECT hosting.*, client.id AS client_id, product.id AS product_id
            FROM `".$this->tableName."` hosting
                LEFT JOIN `".KuberDock_Product::model()->tableName."` product ON hosting.packageid = product.id
                LEFT JOIN `".CL_Client::model()->tableName."` client ON hosting.userid = client.id";

        if($serverUrl) {
            $url = parse_url($serverUrl);
            $host = $url['host'];
            $host .= $url['port'] ? ':'.$url['port'] : '';
            $sql .= " LEFT JOIN tblservers s ON hosting.server=s.id
                WHERE client.id = ? AND product.serverType = ? AND (s.ipaddress = ? OR s.hostname = ?)";
            array_push($values, $host, $host);
        } else {
            $sql .= " WHERE client.id = ? AND product.serverType = ?";
        }

        $rows = $db->query($sql, $values)->getRows();

        return $rows;
    }

    /**
     * @return bool
     */
    public function calculate()
    {
        $date = new DateTime();
        $date->setTime(0, 0, 0);
        $product = \KuberDock_Product::model()->loadById($this->product_id);
        $clientDetails = CL_Client::model()->getClientDetails($this->userid);

        // trial time
        $enableTrial = $product->getConfigOption('enableTrial');
        $trialTime = $product->getConfigOption('trialTime');
        $regDate = CL_Tools::sqlDateToDateTime($this->regdate);

        if($enableTrial) {
            if(!$this->isTrialExpired($regDate, $trialTime)) {
                CL_MailTemplate::model()->sendPreDefinedEmail($this->id, CL_MailTemplate::TRIAL_NOTICE_NAME, array(
                    'trial_end_date' => $regDate->modify('+'.$trialTime.' day')->format('Y-m-d'),
                ));
            } else {
                CL_MailTemplate::model()->sendPreDefinedEmail($this->id, CL_MailTemplate::TRIAL_EXPIRED_NAME, array(
                    'trial_end_date' => $regDate->modify('+'.$trialTime.' day')->format('Y-m-d'),
                ));
                $api = $this->getAdminApi();
                $api->updateUser(array('suspended' => true), $this->username);
            }

            return true;
        }

        // override auto suspend
        if($this->overideautosuspend && $date < $this->overidesuspenduntil) {
            return true;
        }

        if($product->isFixedPrice()) {
            $this->calculateFixed();
        } else {
            $paymentType = $product->getConfigOption('paymentType');

            $kubes = KuberDock_Product::model()->loadById($this->packageid)->getKubes();
            $kubes = CL_Tools::getKeyAsField($kubes, 'kuber_kube_id');
            $items = $this->calculateUsageByDate($date, $kubes, $paymentType);

            $totalPrice = $this->getItemsTotalPrice($items);
            if($totalPrice) {
                if($clientDetails['client']['credit'] < $totalPrice) {
                    $this->addInvoice($this->userid, $date, $items, false);
                    $this->suspendModule('Not enough funds');
                    return false;
                }
                $this->addInvoice($this->userid, $date, $items);
            }
        }

        return true;
    }

    public function calculateFixed()
    {
        $items = KuberDock_Addon_Items::model()->loadByAttributes(array(
            'status' => \base\models\CL_Invoice::STATUS_UNPAID,
            'user_id' => $this->userid,
        ));

        foreach($items as $row) {
            $item = KuberDock_Addon_Items::model()->loadByParams($row);
            $item->checkStatus();
        }
    }

    /**
     * @param DateTime $date
     * @param string $paymentType
     * @param array $kubes
     * @return array
     * @throws Exception
     */
    public function calculateUsageByDate(DateTime $date, $kubes, $paymentType = 'hourly')
    {
        $nextDueDate = CL_Tools::sqlDateToDateTime($this->nextduedate);
        // For some reason this date set for newly created service
        $dumpDate = new DateTime('1999-12-31');

        if(is_null($nextDueDate) || ($nextDueDate == $dumpDate) || $date <= $nextDueDate) {
            switch ($paymentType) {
                case 'hourly':
                    return $this->getHourlyUsage($date, $kubes);
                default:
                    return $this->getPeriodicUsage($date, $kubes, $paymentType);
            }
        }
    }

    /**
     * @param DateTime $date
     * @param array $kubes
     * @return float
     * @throws Exception
     */
    public function getHourlyUsage(DateTime $date, $kubes)
    {
        $currentDate = clone($date);
        $date = $date->modify('-1 day');

        $product = KuberDock_Product::model()->loadById($this->packageid);
        $api = $this->getAdminApi();

        $dateStart = clone($date);
        $dateEnd = clone($currentDate);
        $dateStart->setTime(0, 0, 0);
        $dateEnd->setTime(0, 0, 0);
        $timeStart = $dateStart->getTimestamp();
        $timeEnd = $dateEnd->getTimestamp();

        $response = $api->getUsage($this->username, $dateStart, $dateEnd);
        $usage = $response->getData();

        $items = array();
        foreach($usage['pods_usage'] as $pod) {
            if(empty($pod['time'])) {
                continue;
            }

            // часы, когда хоть один из подов контейнера был запущен
            $usageHours = array();
            foreach($pod['time'] as $container) {
                foreach($container as $period) {
                    $start = $period['start'];
                    $end = $period['end'];

                    if(!$this->getUsageHoursFromPeriod($start, $end, $timeStart, $timeEnd, $usageHours)) {
                        continue;
                    }
                }
            }

            $price = $kubes[$pod['kube_id']]['kube_price'] * $pod['kubes'];
            $items[] = KuberDock_InvoiceItem::create('Pod: ' . $pod['name'], $price, 'hour', count($usageHours));
        }

        foreach($usage['ip_usage'] as $data) {
            $usageHours = array();

            if(!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = (float) $product->getConfigOption('priceIP');
            $items[] = KuberDock_InvoiceItem::create('IP: ' . $data['ip_address'], $price, 'hour', count($usageHours));
        }

        foreach($usage['pd_usage'] as $data) {
            $usageHours = array();

            if(!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = (float) $product->getConfigOption('pricePersistentStorage') * $data['size'];
            $items[] = KuberDock_InvoiceItem::create('Storage: ' . $data['pd_name'], $price, 'hour', count($usageHours));
        }

        $this->updateByApi($this->id, array('nextduedate' => $currentDate->modify('+1 day')->format('Y-m-d')));

        return $items;
    }

    /**
     * @param DateTime $date
     * @param array $kubes
     * @param string $paymentType (monthly, quarterly, annually)
     * @return float
     * @throws Exception
     */
    public function getPeriodicUsage(DateTime $date, $kubes, $paymentType)
    {
        $api = $this->getAdminApi();
        $product = KuberDock_Product::model()->loadById($this->packageid);

        $nextDueDate = CL_Tools::sqlDateToDateTime($this->nextduedate);
        $regDate = CL_Tools::sqlDateToDateTime($this->regdate);

        switch($paymentType) {
            case 'monthly':
                $offset = '1 month';
                break;
            case 'quarterly':
                $offset = '3 month';
                break;
            case 'annually':
                $offset = '1 year';
                break;
        }

        if(is_null($nextDueDate)) {
            $dateStart = clone($regDate);
            $dateEnd = clone($date);
        } else {
            $tmpNextDueDate = clone($nextDueDate);
            $dateStart = $tmpNextDueDate->modify('-'.$offset);
            if($dateStart < $regDate) {
                $dateStart = clone($regDate);
            }
            $dateEnd = clone($nextDueDate);
        }

        if($product->proratabilling && $product->proratadate >= 1 && $product->proratadate <= 31) {
            $dateStart->setDate($dateStart->format('Y'), $dateStart->format('m'), $product->proratadate);
            $dateEnd = clone($dateStart);
            $dateEnd->modify('+'.$offset);
        }

        $response = $api->getUsage($this->username, $dateStart, $dateEnd);
        $usage = $response->getData();

        $items = array();
        $totalKubeCount = 0;

        // TODO: kube price depends from package. waiting api
        $allPods = array();
        foreach($usage['pods_usage'] as $pod) {
            $title = preg_replace('/__[a-z0-9]+/i', '', $pod['name']);
            if (!in_array($title, $allPods)) {
                $allPods[] = $title;
                $totalKubeCount += $pod['kubes'];

                $price = $kubes[$pod['kube_id']]['kube_price'];
                $items[] = KuberDock_InvoiceItem::create('Pod: ' . $title, $price, 'pod', $pod['kubes']);
            }
        }

        $totalIPs = array();
        foreach ($usage['ip_usage'] as $data) {
            if (!in_array($data['ip_address'], $totalIPs)) {
                $totalIPs[] = $data['ip_address'];
                $price = (float) $product->getConfigOption('priceIP');
                $items[] = KuberDock_InvoiceItem::create('IP: ' . $data['ip_address'], 'IP', $price);
            }
        }

        $totalPdSize = 0;
        foreach($usage['pd_usage'] as $data) {
            $totalPdSize += $data['size'];
            $price = (float) $product->getConfigOption('pricePersistentStorage');
            $unit = KuberDock_Units::getPSUnits();
            $items[] = KuberDock_InvoiceItem::create('Storage: ' . $data['pd_name'], $price, $unit, $data['size']);
        }

        // Предыдущая оплата в этом периоде
        $lastState = KuberDock_Addon_States::model()->getLastState($this->id, $dateStart, $dateEnd);

        // еще не оплачивалось или уже оплачивалось, но после этого добавились кубы
        if (!$lastState || $totalKubeCount > $lastState->kube_count) {

            // Если оплачиваем дополнительные кубы, убираем из списка уже оплаченные
            if ($lastState) {
                $paidItems = json_decode($lastState->details, true);
                foreach ($paidItems as $paidItem) {
                    $items = array_filter($items, function($item) use ($paidItem) {
                        return !($item['title']==$paidItem['title'] && $item['type']==$paidItem['type']);
                    });
                }
            }

            // Если оплачиваем дополнительные кубы и дата оплаты еще не наступила
            if ($lastState && !is_null($nextDueDate) && $date < $nextDueDate) {

                $checkInDate = CL_Tools::sqlDateToDateTime($lastState->checkin_date);

                // $checkInDate сегодня, оплачивать ничего не надо
                if ($checkInDate == $date) return array();

                // оплачиваем только остаток периода
                $period = CL_Tools::model()->getIntervalDiff($dateStart, $dateEnd);
                $periodRemained = CL_Tools::model()->getIntervalDiff($checkInDate, $nextDueDate);

                $items = array_map(function ($item) use ($period, $periodRemained) {
                    $item['total'] = round($item['total'] / $period * $periodRemained, 2);
                    return $item;
                }, $items);
            } elseif (is_null($nextDueDate) || $date == $nextDueDate) {
                // устанавливаем новый день оплаты
                $currentDate = clone($date);
                $this->updateByApi($this->id, array('nextduedate' => $currentDate->modify('+' . $offset)->format('Y-m-d')));
            }

            $states = new KuberDock_Addon_States();
            $states->setAttributes(array(
                'hosting_id' => $this->id,
                'product_id' => $this->packageid,
                'checkin_date' => CL_Tools::getMySQLFormattedDate($date),
                'kube_count' => $totalKubeCount,
                'ps_size' => $totalPdSize,
                'ip_count' => count($totalIPs),
                'total_sum' => $this->getItemsTotalPrice($items),
                'details' => json_encode($items),
            ));
            $states->save();
        }

        return $items;
    }

    /**
     * @param bool $plainAuth
     * @return api\KuberDock_Api
     */
    public function getApi($plainAuth = false)
    {
        if(($token = $this->getToken()) && !$plainAuth) {
            $api = KuberDock_Server::model()->getApiByToken($token, $this->server);
        } else {
            $api = KuberDock_Server::model()
                ->getApiByUser($this->username, $this->decryptPassword($this->password), $this->server);
        }

        return $api;
    }

    /**
     * @return api\KuberDock_Api
     */
    public function getAdminApi()
    {
        $api = $this->getServer()->getApi();
        return $api;
    }

    /**
     * @return KuberDock_Server
     */
    public function getServer()
    {
        if(!$this->server) {
            $product = KuberDock_Product::model()->loadById($this->packageid);
            return $product->getServer();
        }

        return KuberDock_Server::model()->loadById($this->server);
    }

    /**
     * @param DateTime $date
     * @param int $trialTime
     * @return bool
     */
    public function isTrialExpired(DateTime $date, $trialTime)
    {
        $now = new DateTime();
        $date = clone($date);
        $trialEndDate = $date->modify('+'.$trialTime.' day')->format('Y-m-d');
        $now = $now->format('Y-m-d');

        return ($now >= $trialEndDate);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->domainstatus == 'Active';
    }

    /**
     * @return bool
     */
    public function isTerminated()
    {
        return $this->domainstatus == 'Terminated';
    }

    /**
     * @return bool
     */
    public function isSuspended()
    {
        return $this->domainstatus == 'Suspended';
    }

    /**
     * @return string
     */
    public function getToken()
    {
        $customField = KuberDock_Product::model()->getCustomField($this->packageid, 'Token');
        $sql = 'SELECT * FROM `tblcustomfieldsvalues` WHERE relid=? AND fieldid=?';
        $row = $this->_db->query($sql, array($this->id, $customField['id']))->getRow();

        return $row['value'];
    }

    /**
     * @param string $token
     * @return $this
     */
    public function updateToken($token)
    {
        KuberDock_Product::model()->updateCustomField($this->packageid, $this->id, 'Token', $token);
        return $this;
    }

    /**
     * @return array
     */
    public function getMainProduct()
    {
        $values = array(KUBERDOCK_MODULE_NAME, 'Active', $this->id, $this->userid);

        $sql = "SELECT hosting.*, product.id AS product_id
            FROM `".$this->tableName."` hosting
                LEFT JOIN `".KuberDock_Product::model()->tableName."` product ON hosting.packageid = product.id
            WHERE product.servertype = ? AND hosting.domainstatus = ? AND hosting.id != ?
                AND hosting.userid = ? ORDER BY hosting.regdate ASC LIMIT 1";

        return $this->_db->query($sql, $values)->getRow();
    }

    /**
     * @param bool|true $login
     * @return string
     */
    public function getLoginByTokenLink($login = true)
    {
        $serverLink = $this->getServer()->getLoginPageLink();

        if($login) {
            return sprintf('%s/login?token=%s', $serverLink, $this->getToken());
        } else {
            return sprintf('%s/?token=%s', $serverLink, $this->getToken());
        }
    }

    /**
     * @param timestamp $timeStart
     * @param timestamp $timeEnd
     * @param timestamp $periodStart
     * @param timestamp $periodEnd
     * @param array $usagePeriod
     * @return array
     */
    private function getUsageHoursFromPeriod($timeStart, $timeEnd, $periodStart, $periodEnd, &$usagePeriod = array())
    {
        if($timeStart <= $periodStart) {
            $timeStart = $periodStart;
        }

        if($timeEnd >= $periodEnd) {
            $timeEnd = $periodEnd;
        }

        if($timeStart > $periodStart && $periodEnd < $timeEnd) {
            return array();
        }

        for($i = $timeStart; $i <= $timeEnd; $i += 3600) {
            $hour = date('H', $i);
            if(!in_array($hour, $usagePeriod)) {
                $usagePeriod[] = date('H', $i);
            }
        }

        return $usagePeriod;
    }

    /**
     * @param $items
     * @return mixed
     */
    private function getItemsTotalPrice($items)
    {
        return array_reduce($items, function ($carry, $item) {
            $carry += $item->getTotal();
            return $carry;
        });
    }

    /**
     * @param $name
     * @return bool
     */
    private function isPodDeleted($name)
    {
        return strpos($name, self::DELETED_POD_SIGN) !== false;
    }
} 