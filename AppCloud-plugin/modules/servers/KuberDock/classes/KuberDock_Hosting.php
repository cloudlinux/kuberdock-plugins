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
use components\Units;
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
     * @return KuberDock_Product
     */
    public function getProduct()
    {
        return KuberDock_Product::model()->loadById($this->packageid);
    }

    /**
     * @return bool
     */
    public function calculate()
    {
        $date = new DateTime();
        $date->setTime(0, 0, 0);
        $product = \KuberDock_Product::model()->loadById($this->product_id);

        $trialTime = (int) $product->getConfigOption('trialTime');
        $regDate = CL_Tools::sqlDateToDateTime($this->regdate);

        if($product->isTrial()) {
            if($this->isTrialExpired($regDate, $trialTime)) {
                $sendExpireLetter = $product->getConfigOption('sendTrialExpire');
                $expireDate = $regDate->modify('+'.$trialTime.' day');
                if ($sendExpireLetter && $expireDate->format('Y-m-d') == $date->format('Y-m-d')) {
                    CL_MailTemplate::model()->sendPreDefinedEmail($this->id, CL_MailTemplate::TRIAL_EXPIRED_NAME, array(
                        'trial_end_date' => $expireDate->format('Y-m-d'),
                    ));
                }

                // If suspend module then user can't change product via client area
                $this->getAdminApi()->updateUser(array('suspended' => true), $this->username);
            } else {
                $trialNoticeEvery = $product->getConfigOption('trialNoticeEvery') != ''
                    ? (int) $product->getConfigOption('trialNoticeEvery')
                    : 7;

                if ($trialNoticeEvery!=0 && ($regDate->diff($date)->days % $trialNoticeEvery == 0)) {
                    CL_MailTemplate::model()->sendPreDefinedEmail($this->id, CL_MailTemplate::TRIAL_NOTICE_NAME, array(
                        'trial_end_date' => $regDate->modify('+'.$trialTime.' day')->format('Y-m-d'),
                    ));
                }
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
                $clientDetails = CL_Client::model()->getClientDetails($this->userid);
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

    /**
     * Process unpaid items
     */
    public function calculateFixed()
    {
        $items = KuberDock_Addon_Items::model()->loadByAttributes(array(
            'user_id' => $this->userid,
        ), 'status != \'' . \base\models\CL_Invoice::STATUS_DELETED .'\'');

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
            $items[] = $product->createInvoice('Pod: ' . $pod['name'], $price, 'hour', count($usageHours));
        }

        foreach($usage['ip_usage'] as $data) {
            $usageHours = array();

            if(!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = (float) $product->getConfigOption('priceIP');
            $items[] = $product->createInvoice('IP: ' . $data['ip_address'], $price, 'hour', count($usageHours));
        }

        foreach($usage['pd_usage'] as $data) {
            $usageHours = array();

            if(!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = (float) $product->getConfigOption('pricePersistentStorage') * $data['size'];
            $items[] = $product->createInvoice('Storage: ' . $data['pd_name'], $price, 'hour', count($usageHours));
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

            $dateStart = $tmpNextDueDate->modify('-' . $offset);
            if($dateStart < $regDate) {
                $dateStart = clone($regDate);
            }
            $dateEnd = clone($nextDueDate);
        }

        if($product->proratabilling && $product->proratadate >= 1 && $product->proratadate <= 31) {
            $dateStart->setDate($dateStart->format('Y'), $dateStart->format('m'), $product->proratadate);
            $dateEnd = clone($dateStart);
            $dateEnd->modify('+' . $offset);
        }

        $response = $api->getUsage($this->username, $dateStart, $dateEnd);
        $usage = $response->getData();

        $items = array();

        // Kubes
        // TODO: kube price depends from package. waiting api
        $totalPod = array();
        $totalKubeCount = 0;
        foreach ($usage['pods_usage'] as $pod) {
            $name = preg_replace('/__[a-z0-9]+/i', '', $pod['name']);
            if (!in_array($name, $totalPod)) {
                $totalPod[] = $name;
                $totalKubeCount += $pod['kubes'];
            }
        }

        if ($totalKubeCount) {
            $price = $kubes[$pod['kube_id']]['kube_price'];
            $items[] = $product->createInvoice('Kube', $price, 'pod', $totalKubeCount);
        }

        // Public IPs
        $totalIp = array();
        foreach ($usage['ip_usage'] as $data) {
            if (!in_array($data['ip_address'], $totalIp)) {
                $totalIp[] = $data['ip_address'];
            }
        }
        if ($totalIp) {
            $price = (float) $product->getConfigOption('priceIP');
            $items[] = $product->createInvoice('IP', $price, 'IP', count($totalIp));
        }

        // Persistent storage
        $totalPdSize = 0;
        $totalPd = array();
        foreach ($usage['pd_usage'] as $data) {
            if (!in_array($data['pd_name'], $totalPd)) {
                $totalPd[] = $data['pd_name'];
                $totalPdSize += $data['size'];

            }
        }
        if ($totalPdSize) {
            $price = (float) $product->getConfigOption('pricePersistentStorage');
            $unit = Units::getPSUnits();
            $items[] = $product->createInvoice('Storage', $price, $unit, $totalPdSize);
        }

        // Предыдущая оплата в этом периоде
        $lastState = KuberDock_Addon_States::model()->getLastState($this->id, $dateStart, $dateEnd);
        if ($lastState) {
            $checkInDate = CL_Tools::sqlDateToDateTime($lastState->checkin_date);
            if ($checkInDate == $date)  {
                return array();
            }
        }

        // In KuberDock_states we save all user's resources
        $itemsForState = array_map(function ($e) {
            return clone $e;
        }, $items);

        // Если оплачиваем дополнительные кубы, убираем из списка уже оплаченные
        if ($lastState && !is_null($nextDueDate) && $date < $nextDueDate) {
            $paidItems = json_decode($lastState->details, true);
            foreach ($paidItems as $paidItem) {
                foreach ($items as $i => &$item) {
                    /** @var $item \components\KuberDock_InvoiceItem */
                    $sameDescription = $item->getDescription()==$paidItem['description'];
                    $sameUnits = $item->getUnits()==$paidItem['units'];

                    if ($sameDescription && $sameUnits) {
                        if ($item->getQty() <= $paidItem['qty']) {
                            unset($items[$i]);
                        } else {
                            $item->setQty($item->getQty() - $paidItem['qty']);
                        }
                    }
                }
                unset($item);
            }

            // оплачиваем только остаток периода
            $period = CL_Tools::model()->getIntervalDiff($dateStart, $dateEnd);
            $periodRemained = CL_Tools::model()->getIntervalDiff($date, $nextDueDate);

            $items = array_map(function ($item) use ($period, $periodRemained) {
                /** @var $item \components\KuberDock_InvoiceItem */
                $item->multiplyPrice($periodRemained / $period);
                return $item;
            }, $items);
        }

        if (is_null($nextDueDate) || $date == $nextDueDate) {
            $currentDate = clone($date);
            $this->updateByApi($this->id, array('nextduedate' => $currentDate->modify('+' . $offset)->format('Y-m-d')));
        }

        if ($items) {
            $states = new KuberDock_Addon_States();
            $states->setAttributes(array(
                'hosting_id' => $this->id,
                'product_id' => $this->packageid,
                'checkin_date' => CL_Tools::getMySQLFormattedDate($date),
                'kube_count' => $totalKubeCount,
                'ps_size' => $totalPdSize,
                'ip_count' => count($totalIp),
                'total_sum' => $this->getItemsTotalPrice($itemsForState),
                'details' => json_encode(array_map(function ($item) {
                    /** @var $item \components\KuberDock_InvoiceItem */
                    return $item->jsonSerialize();
                }, $itemsForState)),
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
        $token = $plainAuth ? '' : $this->getToken();

        return KuberDock_Server::model()
            ->getApiByUser($this->username, $this->decryptPassword($this->password), $token, $this->server);
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

        return $row ? $row['value'] : '';
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
     * @return string
     */
    public function getLoginByTokenLink()
    {
        $serverLink = $this->getServer()->getLoginPageLink();
        if (USE_JWT_TOKENS) {
            $tokenField = 'token2';
            $token = $this->getApi()->getJWTToken(array(), true);
        } else {
            $tokenField = 'token';
            $token = $this->getToken();
        }

        return sprintf('%s/?%s=%s', $serverLink, $tokenField, $token);
    }

    public function loadByInvoiceId($invoiceId)
    {
        $sql = "SELECT h.* FROM tblhosting h 
            LEFT JOIN tblinvoiceitems it ON it.relid=h.id 
            LEFT JOIN tblproducts p ON p.id=h.packageid 
            WHERE it.type = 'Hosting' AND p.servertype = 'KuberDock' AND it.invoiceid = :invoice_id";

        $data = $this->_db->query($sql, array(
            ':invoice_id' => $invoiceId,
        ))->getRow();

        if ($data) {
            $this->loadByParams($data);
            return $this;
        } else {
            return null;
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
} 