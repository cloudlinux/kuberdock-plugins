<?php


namespace models\billing;


use components\View;
use models\Model;

class EmailTemplate extends Model
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
     * @var bool
     */
    public $timestamps = true;
    /**
     * @var string
     */
    protected $table = 'tblemailtemplates';

    protected $fillable = ['name', 'type', 'subject', 'message'];

    /**
     * @param string $name
     * @param string $subject
     * @param string $template
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createFromView($name, $subject, $template, $type = self::TYPE_PRODUCT) {
        $view = new View();
        $message = $view->renderPartial('emails/templates/' . $template, array(), false);

        return parent::firstOrCreate([
            'name' => $name,
            'subject' => $subject,
            'type' => $type,
            'message' => $message,
        ]);
    }
}