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
        $product = KuberDock_Product::model()->loadById($this->product_id);
        $clientDetails = CL_Client::model()->getClientDetails($this->userid);

        // trial time
        $trialTime = $product->getConfigOption('trialTime');
        $regDate = new DateTime($this->regdate);

        if($trialTime) {
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
        if(0 >= $date->format('H') and $date->format('H') <= 6) {
            $date = $date->modify('-1 day');
        }

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
            $this->updateByApi($this->id, array('nextduedate' => date('Y-m-d', time())));
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
        $api = $this->getAdminApi();
        // TODO: api must return usage for period
        $response = $api->getUsage($this->username);
        $usage = $response->getData();

        $method = 'get'.ucfirst($paymentType).'Usage';
        if(method_exists($this, $method)) {
            $usage = $this->{$method}($date, $kubes, $usage);
            return $usage;
        } else {
            throw new Exception('Undefined payment type: '.$paymentType);
        }
    }

    /**
     * @param DateTime $date
     * @param float $price
     * @param array $usage
     * @return float
     */
    public function getDailyUsage(DateTime $date, $price, $usage)
    {
        // Currently not used
        return 0;
        $dateStart = clone($date);
        $dateEnd = clone($date);
        $dateStart->setTime(0, 0, 0);
        $dateEnd->setTime(23, 59, 59);
        $timeStart = $dateStart->getTimestamp();
        $timeEnd = $dateEnd->getTimestamp();

        $podStat = array();
        $timeSegment = 60*60*24;

        foreach($usage as $pod) {
            if($this->isPodDeleted($pod['name']) || empty($pod['time'])) {
                continue;
            }

            $usageTime = 0;

            foreach($pod['time'] as $k=>$period) {
                if($period['start'] <= $timeStart && $timeEnd <= $period['end']) {
                    $usageTime += $timeEnd - $timeStart;
                } elseif($period['start'] >= $timeStart && $period['start'] <= $timeEnd) {
                    $usageTime += $timeEnd - $period['start'];
                } elseif($period['end'] >= $timeStart && $period['end'] <= $timeEnd) {
                    $usageTime += $period['end'] - $timeStart;
                }
            }

            $podStat[] = $usageTime;
        }

        $usagePercent = array_sum($podStat) / count($podStat) / $timeSegment * 100;
        $factPrice = $price / 100 * $usagePercent;

        return $factPrice;
    }

    /**
     * @param DateTime $date
     * @param array $kubes
     * @param array $usage
     * @return float
     * @throws Exception
     */
    public function getHourlyUsage(DateTime $date, $kubes, $usage)
    {
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