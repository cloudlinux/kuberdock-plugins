<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Hosting extends CL_Hosting {
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
     * @return array
     */
    public function getByUser($userId)
    {
        $db = CL_Query::model();

        $values = array($userId, KUBERDOCK_MODULE_NAME);

        $sql = "SELECT hosting.*, client.id AS client_id, product.id AS product_id
            FROM `".$this->tableName."` hosting
                LEFT JOIN `".KuberDock_Product::model()->tableName."` product ON hosting.packageid = product.id
                LEFT JOIN `".CL_Client::model()->tableName."` client ON hosting.userid = client.id
            WHERE client.id = ? AND product.serverType = ?";

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
        $product = KuberDock_Product::model()->loadById($this->product_id);
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

        $paymentType = $product->getConfigOption('paymentType');

        $kubes = KuberDock_Product::model()->loadById($this->packageid)->getKubes();
        $kubes = CL_Tools::getKeyAsField($kubes, 'kuber_kube_id');
        $factPrice = $this->calculateUsageByDate($date, $kubes, $paymentType);

        if($factPrice) {
            if($clientDetails['client']['credit'] < $factPrice) {
                $this->addInvoice($this->userid, $date, $factPrice, false);
                $this->suspendModule('Not enough funds');
                return false;
            }
            $this->addInvoice($this->userid, $date, $factPrice);
        }

        return true;
    }

    /**
     * @param DateTime $date
     * @param string $paymentType
     * @param array $kubes
     * @return float
     * @throws Exception
     */
    public function calculateUsageByDate(DateTime $date, $kubes, $paymentType = 'hourly')
    {
        switch($paymentType) {
            case 'hourly':
                $nextDueDate = CL_Tools::sqlDateToDateTime($this->nextduedate);
                if($date == $nextDueDate || is_null($nextDueDate)) {
                    return $this->getHourlyUsage($date, $kubes);
                }
            default:
                return $this->getPeriodicUsage($date, $kubes, $paymentType);
        }

        return 0;
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

        $api = $this->getAdminApi();
        // TODO: api must return usage for period
        $response = $api->getUsage($this->username);
        $usage = $response->getData();

        $dateStart = clone($date);
        $dateEnd = clone($date);
        $dateStart->setTime(0, 0, 0);
        $dateEnd->setTime(23, 59, 59);
        $timeStart = $dateStart->getTimestamp();
        $timeEnd = $dateEnd->getTimestamp();

        $podStat = array();
        $timeSegment = 60*60;

        // TODO: replace method when api can return actual usage
        foreach($usage as $pod) {
            if($this->isPodDeleted($pod['name']) || empty($pod['time'])) {
                continue;
            }

            $usageHours = array();
            if($pod['kube_id'] && is_array($pod['kube_id'])) {
                $kubeId = $pod['kube_id'][0];
            } else {
                $kubeId = $pod['kube_id'];
            }

            foreach($pod['time'] as $cName => $container) {
                $kubeCount = 0;
                foreach($container as $period) {
                    $kubeCount = $period['kubes'];
                    if($period['start'] <= $timeStart) {
                        $period['start'] = $timeStart;
                    }

                    if($period['end'] >= $timeEnd) {
                        $period['end'] = $timeEnd;
                    }

                    if($period['start'] > $timeStart && $timeEnd < $period['end']) {
                        continue;
                    }

                    for($i = $period['start']; $i <= $period['end']; $i += $timeSegment) {
                        $hour = date('H', $i);
                        if(!in_array($hour, $usageHours)) {
                            $usageHours[] = date('H', $i);
                        }
                    }
                }

                $podStat[] = count($usageHours) * $kubes[$kubeId]['kube_price'] * $kubeCount;
            }
        }

        $factPrice = array_sum($podStat);

        if($factPrice) {
            $this->updateByApi($this->id, array('nextduedate' => $currentDate->modify('+1 day')->format('Y-m-d')));
        }

        return $factPrice;
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
        // TODO: api must return usage for period
        $response = $api->getUsage($this->username);
        $usage = $response->getData();
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

        if($product->proratabilling) {
            if($product->proratadate >= 1 && $product->proratadate <= 31) {
                $dateStart->setDate($dateStart->format('Y'), $dateStart->format('m'), $product->proratadate);
                $dateEnd = clone($dateStart);
                $dateEnd->modify('+'.$offset);
            }
        }

        $timeStart = $dateStart->getTimestamp();
        $timeEnd = $dateEnd->getTimestamp();

        $podStat = array();
        $factPrice = 0;
        $totalKubeCount = 0;
        $totalSum = 0;

        // TODO: replace method when api can return actual usage
        // TODO: kube price depends from package. waiting api
        foreach($usage as $pod) {
            if(empty($pod['time'])) {
                continue;
            }

            $kubeId = $pod['kube_id'];
            $kubeCount = 0;

            foreach($pod['time'] as $cName => $container) {
                foreach($container as $period) {
                    if($period['start'] >= $timeStart || $period['end'] <= $timeEnd) {
                        $kubeCount = max($period['kubes'], $kubeCount);
                    }
                }
            }

            $totalKubeCount += $kubeCount;
            if(!isset($podStat[$kubeId]['sum'])) {
                $podStat[$kubeId]['count'] = $kubeCount;
                $podStat[$kubeId]['sum'] = $kubes[$kubeId]['kube_price'] * $kubeCount;
                $totalSum = $kubes[$kubeId]['kube_price'] * $kubeCount;
            } else {
                $podStat[$kubeId]['count'] += $kubeCount;
                $podStat[$kubeId]['sum'] += $kubes[$kubeId]['kube_price'] * $kubeCount;
                $totalSum += $kubes[$kubeId]['kube_price'] * $kubeCount;
            }
        }

        $lastState = KuberDock_Addon_States::model()->getLastState($this->id, $dateStart, $dateEnd);

        if(!$lastState || $totalKubeCount > $lastState->kube_count) {
            $factPrice = !$lastState ? $totalSum : $totalSum - $lastState->total_sum;

            if($lastState && !is_null($nextDueDate) && $date < $nextDueDate) {
                $checkInDate = CL_Tools::sqlDateToDateTime($lastState->checkin_date);
                if($checkInDate == $date) return 0;
                $period = CL_Tools::model()->getIntervalDiff($dateStart, $dateEnd);
                $periodRemained = CL_Tools::model()->getIntervalDiff($checkInDate, $nextDueDate);
                $factPrice = round($factPrice / $period * $periodRemained, 2);
            } elseif(is_null($nextDueDate) || $date == $nextDueDate) {
                $currentDate = clone($date);
                $this->updateByApi($this->id, array('nextduedate' => $currentDate->modify('+'.$offset)->format('Y-m-d')));
            }

            $states = new KuberDock_Addon_States();
            $states->setAttributes(array(
                'hosting_id' => $this->id,
                'product_id' => $this->packageid,
                'checkin_date' => CL_Tools::getMySQLFormattedDate($date),
                'kube_count' => $totalKubeCount,
                'total_sum' => $totalSum,
                'details' => json_encode($podStat),
            ));
            $states->save();
        }

        return $factPrice;
    }

    /**
     * @return KuberDock_Api
     */
    public function getApi()
    {
        if($token = $this->getToken()) {
            $api = KuberDock_Server::model()->getApiByToken($token, $this->server);
        } else {
            $api = KuberDock_Server::model()
                ->getApiByUser($this->username, $this->decryptPassword($this->password), $this->server);
        }

        return $api;
    }

    /**
     * @return KuberDock_Api
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
     * @return string
     */
    public function getToken()
    {
        $customField = KuberDock_Product::model()->getCustomField($this->packageid, 'Token');
        $sql = 'SELECT * FROM `tblcustomfieldsvalues` WHERE relid=? AND fieldid';
        $row = $this->_db->query($sql, array($this->id, $customField['id']))->getRow();

        return $row['value'];
    }

    /**
     * @param string $token
     * @return $this
     */
    public function updateToken($token)
    {
        KuberDock_Product::model()->updateCustomField($this->packageid, $this->id, 'admin', 'Token', $token);
        return $this;
    }

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
     * @param $name
     * @return bool
     */
    private function isPodDeleted($name)
    {
        return strpos($name, self::DELETED_POD_SIGN) !== false;
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return KuberDock_Hosting
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 