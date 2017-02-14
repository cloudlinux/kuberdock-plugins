<?php


namespace models\billing;


use Carbon\Carbon;
use components\BillingApi;
use components\InvoiceItemCollection;
use exceptions\CException;
use exceptions\NotEnoughFundsException;
use models\addon\State;
use models\Model;

class PackageUpgrade extends Model
{
    /**
     *
     */
    const STATUS_COMPLETE = 'Completed';
    /**
     *
     */
    const STATUS_PENDING = 'Pending';

    /**
     * @var string
     */
    protected $table = 'tblupgrades';
    /**
     * @var array
     */
    protected $dates = ['date'];

    /**
     * @return Service
     */
    public function service()
    {
        return $this->belongsTo('models\billing\Service', 'relid');
    }

    /**
     * @throws \Exception
     */
    public function upgrade()
    {
        /* @var Package $oldPackage
         * @var Package $newPackage
         * @var Service $service
         */
        $oldPackage = Package::find($this->originalvalue);
        $newPackage = $this->getNewPackage();
        $service = $this->service;
        $deposit = $newPackage->getFirstDeposit();

        if ($deposit) {
            $items = new InvoiceItemCollection();
            $items->add(
                $newPackage->createInvoiceItem(Invoice::CUSTOM_INVOICE_DESCRIPTION, $deposit)->setTaxed(false)
            );

            try {
                $invoice = BillingApi::model()->createInvoice($service->client, $items, false);
                BillingApi::model()->applyCredit($invoice);
            } catch (NotEnoughFundsException $e) {
                BillingApi::suspendModule($service);
            }
        }

        $service->getAdminApi()->updateUser([
            'package' => $newPackage->name,
            'rolename' => $newPackage->getRole(),
        ], $service->username);

        if ($oldPackage->getEnableTrial() && !$oldPackage->isBillingFixed()) {
            BillingApi::unSuspendModule($service);

            $service->nextduedate = (new Carbon())->addDay(1);
            $service->save();
        }

        // upgrade from PAYG to fixedPrice
        if (!$oldPackage->isBillingFixed() && $newPackage->isBillingFixed()) {
            // get all client's pods
            $pods = $service->getApi()->getPods()->getData();

            // order them all (create billable items and invoices)
            foreach ($pods as $pod) {
                try {
                    BillingApi::request('orderkuberdockpod', [
                        'client_id' => $service->userid,
                        'pod' => json_encode($pod),
                    ]);
                } catch (\Exception $e) {
                    CException::log($e);
                }
            }
        } elseif ($oldPackage->isBillingFixed() && !$newPackage->isBillingFixed()) {
            $newPackage->getBilling()->afterModuleCreate($service);
            $service->nextduedate = (new Carbon())->addDay(1);
            $service->save();
        }
    }

    /**
     * @return Package
     */
    public function getNewPackage()
    {
        list($newPackageId, $payment) = explode(',', $this->newvalue);

        return Package::find($newPackageId);
    }
}