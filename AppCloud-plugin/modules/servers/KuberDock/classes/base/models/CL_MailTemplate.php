<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use Exception;
use base\CL_Model;
use base\CL_View;
use models\billing\Admin;

class CL_MailTemplate extends CL_Model
{
    const TYPE_PRODUCT = 'product';
    const TYPE_DOMAIN = 'domain';
    const TYPE_GENERAL = 'general';
    const TYPE_ADMIN = 'admin';
    const TYPE_IVOICE = 'invoice';
    const TYPE_AFFILIATE = 'affiliate';

    const TRIAL_NOTICE_NAME = 'KuberDock Trial Notice';
    const TRIAL_EXPIRED_NAME = 'KuberDock Trial Expired';
    const MODULE_CREATE_NAME = 'KuberDock Module Create';

    const PD_NOTICE_NAME = 'KuberDock PD Notice';
    const IP_NOTICE_NAME = 'KuberDock IP Notice';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblemailtemplates';
    }

    /**
     * @param string $name
     * @param string $subject
     * @param string $type self::TYPE_%
     * @param $messageView
     */
    public function createTemplate($name, $subject, $type, $messageView)
    {
        $view = new CL_View();
        $message = $view->renderPartial('emails/templates/' . $messageView, array(), false);

        $data = $this->loadByAttributes(array(
            'name' => $name,
            'type' => $type,
        ));

        if ($data) {
            return;
        }

        $this->insert(array(
            'name' => $name,
            'subject' => $subject,
            'type' => $type,
            'message' => $message,
        ));
    }

    /**
     * @param string $name
     * @param string $type self::TYPE_%
     */
    public function deleteTemplate($name, $type)
    {
        $this->deleteByAttributes(array(
            'name' => $name,
            'type' => $type,
        ));
    }

    /**
     * Related ID
        General Email Type = Client ID (tblclients.id)
        Product Email Type = Service ID (tblhosting.id)
        Domain Email Type = Domain ID (tbldomains.id)
        Invoice Email Type = Invoice ID (tblinvoices.id)
        Support Email Type = Ticket ID (tbltickets.id)
        Affiliate Email Type = Affiliate ID (tblaffiliates.id)
     * @param int $relId
     * @param string $name
     * @param array $params
     *
     * @throws Exception
     */
    public function sendPreDefinedEmail($relId, $name, $params = array())
    {
        $admin = Admin::getCurrent();
        $values['messagename'] = $name;
        $values['customvars'] = base64_encode(serialize($params));
        $values['id'] = $relId;

        $results = localAPI('sendemail', $values, $admin->username);

        if ($results['result'] != 'success') {
            throw new Exception($results['message']);
        }
    }

    /**
     * @param int $relId Related ID same as upper
     * @param string $subject
     * @param string $type
     * @param string $viewName
     * @param array $params
     *
     * @throws Exception
     */
    public function sendCustomEmail($relId, $subject, $type, $viewName, $params = array())
    {
        $view = new CL_View();
        $admin = Admin::getCurrent();
        $values['customtype'] = $type;
        $values['customsubject'] = $subject;
        $values['custommessage'] = $view->renderPartial('emails/'.$viewName, $params, false);
        $values['id'] = $relId;

        $results = localAPI('sendemail', $values, $admin->username);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }
    }
} 