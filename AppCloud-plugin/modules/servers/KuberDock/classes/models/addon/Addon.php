<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace models\addon;

use \models\billing\Server;
use \models\billing\Config;
use \models\billing\PackageGroup;
use \models\billing\EmailTemplate;
use \models\billing\Package;
use \models\billing\Currency;
use \models\billing\Pricing;
use \models\billing\CustomField;
use exceptions\CException;

class Addon extends \components\Component {
    /**
     *
     */
    const REQUIRED_PHP_VERSION = '5.4';
    /**
     *
     */
    const STANDARD_PRODUCT = 'Standard package';
    /**
     *
     */
    const KD_PACKAGE_ID = 0;

    /**
     * Can not be deleted
     */
    const STANDARD_KUBE_TYPE = 0;

    /**
     *
     */
    public function activate()
    {
        if (version_compare(phpversion(), self::REQUIRED_PHP_VERSION) < 0) {
            throw new CException('KuberDock plugin require PHP version' . self::REQUIRED_PHP_VERSION . ' or greater.');
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
        } catch (Exception $e) {
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

        try {
            $this->createTables();

            // TODO: use Illuminate migrations
            $migrations = \migrations\Migration::getAvailable('');
            foreach ($migrations as $version) {
                \models\Model::getConnectionResolver()->connection()->table('KuberDock_migrations')
                    ->insert(array('version' => $version));
            }

            // Create email templates
            $emailTemplate = new EmailTemplate();
            $emailTemplate->createFromView($emailTemplate::TRIAL_NOTICE_NAME,
                'KuberDock Trial Notice', 'trial_notice');
            $emailTemplate->createFromView($emailTemplate::TRIAL_EXPIRED_NAME,
                'KuberDock Trial Expired', 'trial_expired');
            $emailTemplate->createFromView($emailTemplate::MODULE_CREATE_NAME,
                'KuberDock Module Created','module_create');

            // Sync packages & kubes
            $this->syncData($group);
        } catch(Exception $e) {
            $this->dropTables();
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
        EmailTemplate::where('name', EmailTemplate::TRIAL_NOTICE_NAME)->where('type', EmailTemplate::TYPE_PRODUCT)
            ->delete();
        EmailTemplate::where('name', EmailTemplate::TRIAL_EXPIRED_NAME)->where('type', EmailTemplate::TYPE_PRODUCT)
            ->delete();
        EmailTemplate::where('name', EmailTemplate::MODULE_CREATE_NAME)->where('type', EmailTemplate::TYPE_PRODUCT)
            ->delete();

        $servers = Server::typeKuberDock()->active()->get();
        foreach ($servers as $server) {
            Config::removeAllowedApiIP($server->ipaddress);
        }
    }

    /**
     * Create KuberDock tables
     */
    private function createTables() {
        $scheme = \models\Model::getConnectionResolver()->connection()->getSchemaBuilder();

        $scheme->create('KuberDock_products', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->integer('product_id')->unique();
            $table->integer('kuber_product_id')->unique();
        });

        $scheme->create('KuberDock_kubes_templates', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('kuber_kube_id');
            $table->string('kube_name');
            $table->tinyInteger('kube_type')->default(0);
            $table->decimal('cpu_limit', 10, 4);
            $table->integer('memory_limit');
            $table->integer('hdd_limit');
            $table->decimal('traffic_limit', 10, 2);
            $table->integer('server_id');

            $table->index('id');
            $table->index('kuber_kube_id');
        });

        $scheme->create('KuberDock_kubes_links', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('template_id', false, true);
            $table->integer('product_id');
            $table->integer('kuber_product_id');
            $table->decimal('kube_price', 10, 2);

            $table->index('template_id');

            $table->foreign('template_id')->references('id')->on('KuberDock_kubes_templates')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('KuberDock_products')->onDelete('cascade');
        });

        $scheme->create('KuberDock_trial', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->integer('user_id');
            $table->integer('service_id')->unique();
        });

        $scheme->create('KuberDock_states', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('hosting_id');
            $table->integer('product_id');
            $table->date('checkin_date');
            $table->integer('kube_count');
            $table->integer('ps_size');
            $table->integer('ip_count');
            $table->float('total_sum');
            $table->text('details');

            $table->foreign('product_id')->references('product_id')->on('KuberDock_products')->onDelete('cascade');
        });

        $scheme->create('KuberDock_preapps', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->string('session_id', 64);
            $table->integer('user_id');
            $table->integer('product_id');
            $table->integer('kuber_product_id');
            $table->string('pod_id', 64);
            $table->text('data');
            $table->text('referer');

            $table->index('pod_id');
            $table->index('user_id');

            $table->foreign('product_id')->references('product_id')->on('KuberDock_products')->onDelete('cascade');
        });

        $scheme->create('KuberDock_price_changes', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->string('login');
            $table->dateTime('change_time');
            $table->integer('type_id');
            $table->integer('package_id');
            $table->float('old_value');
            $table->float('new_value');

            $table->index('new_value');

            $table->foreign('package_id')->references('product_id')->on('KuberDock_products')->onDelete('cascade');
        });

        $scheme->create('KuberDock_items', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('app_id', false, true);
            $table->integer('service_id');
            $table->string('pod_id', 64);
            $table->integer('billable_item_id');
            $table->integer('invoice_id');
            $table->string('status', 32);
            $table->string('type', 64)->default(\models\addon\Resources::TYPE_POD);

            $table->index('pod_id');
            $table->index('billable_item_id');

            $table->foreign('app_id')->references('id')->on('KuberDock_preapps')->onDelete('cascade');
        });

        $scheme->create('KuberDock_migrations', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->integer('version');
            $table->timestamp('timestamp');

            $table->primary('version');
        });

        $scheme->create('KuberDock_resources', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('billable_item_id');
            $table->string('name');
            $table->enum('type', array(
                \models\addon\Resources::TYPE_IP,
                \models\addon\Resources::TYPE_PD,

            ));
            $table->string('status', 32)->default(\models\addon\Resources::STATUS_ACTIVE );

            $table->index('name');
            $table->index('billable_item_id');

            $table->foreign('billable_item_id')->references('billable_item_id')
                ->on('KuberDock_items')->onDelete('cascade');
        });

        $scheme->create('KuberDock_resource_pods', function ($table) {
            /* @var Illuminate\Database\Schema\Blueprint $table */
            $table->string('pod_id', 64);
            $table->integer('resource_id', false, true);

            $table->index('pod_id');
            $table->index('resource_id');

            $table->foreign('resource_id')->references('id')->on('KuberDock_resources')->onDelete('cascade');
        });
    }

    /**
     *
     */
    private function dropTables()
    {
        $scheme = \models\Model::getConnectionResolver()->connection()->getSchemaBuilder();

        $scheme->dropIfExists('KuberDock_resource_pods');
        $scheme->dropIfExists('KuberDock_resources');
        $scheme->dropIfExists('KuberDock_items');
        $scheme->dropIfExists('KuberDock_kubes_links');
        $scheme->dropIfExists('KuberDock_kubes_templates');
        $scheme->dropIfExists('KuberDock_preapps');
        $scheme->dropIfExists('KuberDock_states');
        $scheme->dropIfExists('KuberDock_trial');
        $scheme->dropIfExists('KuberDock_price_changes');
        $scheme->dropIfExists('KuberDock_products');
        $scheme->dropIfExists('KuberDock_migrations');
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
                        'servergroup' => $server->groups()->first()->id,
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
                    $kubeTemplate = KubeTemplate::firstOrCreate([
                        'kuber_kube_id' => $kube['id'],
                        'kube_name' => $kube['name'],
                        'kube_type' => (int) !in_array($kube['id'], KubeTemplate::STANDARD_KUBE_IDS),
                        'cpu_limit' => $kube['cpu'],
                        'memory_limit' => $kube['memory'],
                        'hdd_limit' => $kube['disk_space'],
                        'traffic_limit' => $kube['included_traffic'],
                        'server_id' => $server->id,
                    ]);

                    $kubeRelation = KubeRelation::firstOrNew([
                        'product_id' => $package->id,
                        'kuber_product_id' => $kdPackage['id'],
                        'kube_price' => $kube['price'],
                    ]);
                    $kubeTemplate->relatedKubes()->save($kubeRelation);
                }
            }
        }
    }
}