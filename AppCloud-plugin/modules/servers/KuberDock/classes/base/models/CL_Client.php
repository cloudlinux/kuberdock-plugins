<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use base\CL_Tools;
use exceptions\CException;
use base\CL_Model;
use models\billing\Admin;

class CL_Client extends CL_Model
{

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblclients';
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getClientsByApi($attributes = array())
    {
        $admin = Admin::getCurrent();

        $results = localAPI('getclients', $attributes, $admin->username);

        return ($results['result'] == 'success') ? $results['clients']['client'] : array();
    }

    /**
     * @param $userId
     * @return array
     */
    public function getClientDetails($userId)
    {
        $admin = Admin::getCurrent();
        $values['clientid'] = $userId;
        $values['stats'] = true;

        $results = localAPI('getclientsdetails', $values, $admin->username);

        return ($results['result'] == 'success') ? $results : array();
    }

    public function filterValues()
    {
        $this->_values['firstname'] = $this->prepareName($this->_values['firstname']);
        $this->_values['lastname'] = $this->prepareName($this->_values['lastname']);
        $this->_values['email'] = $this->prepareEmail($this->_values['email']);
    }

    private function prepareName($name)
    {
        $name = preg_replace ('/[^[:alpha:]?!]/iu', '', $name);
        $name = substr_replace($name, '', 25);

        if ($name == '') {
            $name = CL_Tools::generateRandomString();
        }

        return $name;
    }

    private function prepareEmail($email)
    {
        $email = trim($email, '.');
        $email = preg_replace ('/\.+/i', '.', $email);

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if ($email == false || $email == '') {
            $email = \JTransliteration::transliterate($this->_values['lastname']) . mt_rand(1, 1000) . '@kd.com';

        }
        $email = substr_replace($email, '', 50);

        return $email;
    }

    /**
     * @param string $username
     * @param array $userDomains
     * @return array
     * @throws CException
     */
    public function getClientByCpanelUser($username, $userDomains)
    {
        $admin = Admin::getCurrent();
        $values['username2'] = $username;
        $results = localAPI('getclientsproducts', $values, $admin->username);

        if($results['result'] == 'error') {
            throw new CException($results['message']);
        }

        foreach($results['products']['product'] as $row) {
            if(in_array($row['domain'], $userDomains)) {
                $clientId = $row['clientid'];
                break;
            }
        }

        if(!isset($clientId)) {
            throw new CException('User not found');
        }

        $details = $this->getClientDetails($clientId);

        if($details['result'] != 'success') {
            throw new CException('User not found');
        }

        return $details['client'];
    }
} 