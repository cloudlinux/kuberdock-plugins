<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace models\addon;

use models\billing\Server;
use models\billing\Config;
use models\billing\PackageGroup;
use models\billing\EmailTemplate;
use models\billing\Package;
use models\billing\Currency;
use models\billing\Pricing;
use models\billing\CustomField;
use exceptions\CException;
use models\billing\ServerGroup;

class Addon extends \components\Component {
    /**
     *
     */
    const REQUIRED_PHP_VERSION = '5.4';

    /**
     *
     */
    public function activate()
    {
        if (version_compare(phpversion(), self::REQUIRED_PHP_VERSION) < 0) {
            throw new CException('KuberDock plugin require PHP version ' . self::REQUIRED_PHP_VERSION . ' or greater.');
        }

        if (!class_exists('PDO')) {
            throw new CException('KuberDock plugin require PHP (PDO).');
        }

        $server = Server::typeKuberDock()->active()->first();

        if (!$server) {
            throw new CException('Add KuberDock server and server group before activating addon.');
        }

        try {
            $server->getApi()->getPackages();
        } catch (\Exception $e) {
            throw new CException('Cannot connect to KuberDock server. Please check server credentials.');
        }

        $config = Config::get();
        $part = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
        $url = $config->SystemURL . $part . '/configservers.php?action=manage&id=' . $server->id;

        $ipAddress = current(explode(':', $server->ipaddress));
        if ($ipAddress && !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new CException('KuberDock server IP address is wrong. Please edit it on ' . $url);
        }

        $hostname = current(explode(':', $server->hostname));
        if ($hostname && !filter_var(gethostbyname($hostname), FILTER_VALIDATE_IP)) {
            throw new CException('KuberDock server hostname is wrong. Please edit it on ' . $url);
        }

        $group = PackageGroup::firstOrCreate(array(
            'name' => PackageGroup::DEFAULT_NAME,
        ));

        $this->dropTables();

        try {
            $this->createTables();

            // TODO: use \Illuminate migrations
            \migrations\Migration::fillByActivation();

            // Create email templates
            EmailTemplate::createTemplates();

            // Sync packages & kubes
            $this->syncData($group);
        } catch(\Exception $e) {
            $this->dropTables();
            CException::log($e);
            throw $e;
        }
    }

    /**
     *
     */
    public function deactivate()
    {
        // Clear database
        $this->dropTables();

        // Delete email templates
        EmailTemplate::deleteTemplates();

        $servers = Server::typeKuberDock()->active()->get();
        foreach ($servers as $server) {
            Config::removeAllowedApiIP($server->ipaddress);
        }

        Config::removeSetting('ModuleHooks', 'KuberDock');
    }

    /**
     * Create KuberDock tables
     */
    private function createTables() {
        \models\addon\PackageRelation::createTable();
        \models\addon\KubeTemplate::createTable();
        \models\addon\KubePrice::createTable();
        \models\addon\Trial::createTable();
        \models\addon\State::createTable();
        \models\addon\App::createTable();
        \models\addon\KubePriceChange::createTable();
        \models\addon\Item::createTable();
        \models\addon\ItemInvoice::createTable();
        \models\addon\Migration::createTable();
        \models\addon\Resources::createTable();
        \models\addon\ResourcePods::createTable();
        \models\addon\ResourceItems::createTable();
    }

    /**
     * Drop KuberDock tables
     */
    private function dropTables()
    {
        // Depending to the foreign keys order is important
        \models\addon\ResourceItems::dropTable();
        \models\addon\ResourcePods::dropTable();
        \models\addon\Resources::dropTable();
        \models\addon\ItemInvoice::dropTable();
        \models\addon\Item::dropTable();
        \models\addon\KubePriceChange::dropTable();
        \models\addon\KubePrice::dropTable();
        \models\addon\KubeTemplate::dropTable();
        \models\addon\App::dropTable();
        \models\addon\State::dropTable();
        \models\addon\PackageRelation::dropTable();
        \models\addon\Trial::dropTable();
        \models\addon\Migration::dropTable();
    }

    /**
     * Sync packages and kubes with KuberDock servers
     * @param PackageGroup $group
     */
    private function syncData(PackageGroup $group)
    {
        $servers = Server::typeKuberDock()->active()->get();

        foreach ($servers as $server) {
            /* @var Server $server */
            $kdPackages = $server->getApi()->getPackages(true)->getData();

            Config::addAllowedApiIP('KuberDock', $server->ipaddress);

            foreach ($server->getApi()->getKubes()->getData() as $kube) {
                KubeTemplate::firstOrCreate([
                    'kuber_kube_id' => $kube['id'],
                    'kube_name' => $kube['name'],
                    'kube_type' => (int) !in_array($kube['id'], [0, 1, 2]),
                    'cpu_limit' => $kube['cpu'],
                    'memory_limit' => $kube['memory'],
                    'hdd_limit' => $kube['disk_space'],
                    'traffic_limit' => $kube['included_traffic'],
                    'server_id' => $server->id,
                ]);
            }

            foreach ($kdPackages as $kdPackage) {
                $pricing = [];
                $package = Package::typeKuberDock()->where('name', $kdPackage['name'])->first();

                if (!$package) {
                    $package = new Package();
                    $package->setRawAttributes([
                        'gid' => $group->id,
                        'type' => 'other',
                        'name' => $kdPackage['name'],
                        'paytype' => 'onetime',
                        'autosetup' => 'order',
                        'servertype' => KUBERDOCK_MODULE_NAME,
                        'servergroup' => ServerGroup::byServerId($server->id)->id,
                    ]);

                    foreach (Currency::all() as $currency) {
                        $pricing[] = Pricing::firstOrNew([
                            'type' => 'product',
                            'currency' => $currency->id,
                            'msetupfee' => 0,
                            'qsetupfee' => 0,
                            'asetupfee' => 0,
                            'bsetupfee' => 0,
                            'tsetupfee' => 0,
                            'monthly' => 0,
                            'quarterly' => -1,
                            'semiannually' => -1,
                            'annually' => -1,
                            'biennially' => -1,
                            'triennially' => -1,
                        ]);
                    }
                }

                $package->setConfigOptions([
                    'enableTrial' => 0,
                    'firstDeposit' => $kdPackage['first_deposit'],
                    'pricePersistentStorage' => $kdPackage['price_pstorage'],
                    'priceIP' => $kdPackage['price_ip'],
                    'paymentType' => array_flip(Package::getPaymentTypes())[$kdPackage['period']],
                    'billingType' => array_flip(Package::getBillingTypes())[$kdPackage['count_type']],
                    'debug' => 0,
                ]);

                $package->save();

                CustomField::firstOrCreate(array(
                    'type' => 'product',
                    'relid' => $package->id,
                    'fieldname' => 'Token',
                    'adminonly' => 'on',
                ));

                if ($pricing) {
                    $package->pricing()->saveMany($pricing);
                }

                $packageRelation = PackageRelation::firstOrNew([
                    'kuber_product_id' => $kdPackage['id'],
                ]);
                $package->relatedKuberDock()->save($packageRelation);

                foreach ($kdPackage['kubes'] as $kube) {
                    $kubeTemplate = KubeTemplate::where('kuber_kube_id', $kube['id'])->first();
                    $kubePrice = KubePrice::firstOrNew([
                        'template_id' => $kubeTemplate->id,
                        'product_id' => $package->id,
                        'kuber_product_id' => $kdPackage['id'],
                        'kube_price' => $kube['price'],
                    ]);

                    $kubeTemplate->kubePrice()->save($kubePrice);
                }
            }
        }

        Config::appendSetting('ModuleHooks', 'KuberDock');
    }
}