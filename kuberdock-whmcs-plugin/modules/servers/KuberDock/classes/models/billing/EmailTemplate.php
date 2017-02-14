<?php


namespace models\billing;


use Carbon\Carbon;
use components\BillingApi;
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

    const RESOURCES_NOTICE_NAME = 'KuberDock Resources Notice';
    const RESOURCES_TERMINATION_NAME = 'KuberDock Resources Termination';

    const INVOICE_REMINDER_NAME = 'KuberDock Invoice Reminder';

    /**
     * @var bool
     */
    public $timestamps = true;
    /**
     * @var string
     */
    protected $table = 'tblemailtemplates';
    /**
     * @var array
     */
    protected $fillable = ['name', 'type', 'subject', 'message'];

    public function scopeProduct($query)
    {
        return $query->where('type', EmailTemplate::TYPE_PRODUCT);
    }

    /**
     * @param string $name
     * @param string $subject
     * @param string $template
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function createFromView($name, $subject, $template, $type = self::TYPE_PRODUCT) {
        $view = new View();
        $message = $view->renderPartial('emails/templates/' . $template, [], false);

        return parent::firstOrCreate([
            'name' => $name,
            'subject' => $subject,
            'type' => $type,
            'message' => $message,
        ]);
    }

    public static function createTemplates()
    {
        EmailTemplate::createFromView(EmailTemplate::TRIAL_NOTICE_NAME, 'KuberDock Trial Notice', 'trial_notice');
        EmailTemplate::createFromView(EmailTemplate::TRIAL_EXPIRED_NAME, 'KuberDock Trial Expired', 'trial_expired');
        EmailTemplate::createFromView(EmailTemplate::MODULE_CREATE_NAME, 'KuberDock Module Created','module_create');
        EmailTemplate::createFromView(EmailTemplate::RESOURCES_NOTICE_NAME, 'KuberDock Resources Notice', 'resources_notice');
        EmailTemplate::createFromView(EmailTemplate::RESOURCES_TERMINATION_NAME, 'KuberDock Resources Termination', 'resources_expired');
        EmailTemplate::createFromView(EmailTemplate::INVOICE_REMINDER_NAME, 'KuberDock Invoice reminder', 'invoice_reminder');
    }

    public static function deleteTemplates()
    {
        EmailTemplate::product()->where('name', EmailTemplate::TRIAL_NOTICE_NAME)->delete();
        EmailTemplate::product()->where('name', EmailTemplate::TRIAL_EXPIRED_NAME)->delete();
        EmailTemplate::product()->where('name', EmailTemplate::MODULE_CREATE_NAME)->delete();
        EmailTemplate::product()->where('name', EmailTemplate::RESOURCES_NOTICE_NAME)->delete();
        EmailTemplate::product()->where('name', EmailTemplate::RESOURCES_TERMINATION_NAME)->delete();
        EmailTemplate::product()->where('name', EmailTemplate::INVOICE_REMINDER_NAME)->delete();
    }
}