<?php


namespace models\addon\billing;


use Carbon\Carbon;
use components\Component;
use models\billing\Config;

class AutomationSettings extends Component
{
    /**
     * AutomationSettings constructor.
     */
    public function __construct()
    {
        $this->setAttributes(Config::getAutomatedSettings());
    }

    /**
     * @param \DateTime $date
     * @return bool
     */
    public function isSuspendNotice(\DateTime $date)
    {
        $overdueDays = (new Carbon())->diffInDays($date);

        return $overdueDays == $this->invoiceNoticeDays;
    }

    /**
     * @return mixed
     */
    public function isSuspendEnabled()
    {
        return $this->suspend;
    }

    /**
     * @param \DateTime $date
     * @return bool
     */
    public function isSuspended(\DateTime $date)
    {
        $overdueDays = (new Carbon())->diffInDays($date);

        return $this->isSuspendEnabled() && $overdueDays >= $this->suspendDays;
    }

    /**
     * @return mixed
     */
    public function isTerminateEnabled()
    {
        return $this->termination;
    }

    /**
     * @param \DateTime $date
     * @return bool
     */
    public function isTerminated(\DateTime $date)
    {
        $overdueDays = (new Carbon())->diffInDays($date);

        return $this->isTerminateEnabled() && $overdueDays >= $this->terminationDays;
    }

    /**
     * @param \DateTime $date
     * @return bool
     */
    public function isTerminateNotice(\DateTime $date)
    {
        $overdueDays = (new Carbon())->diffInDays($date);

        return $this->invoiceReminderDays && $this->isTerminateEnabled()
            && $overdueDays == abs($this->terminationDays - $this->invoiceReminderDays);

    }
}