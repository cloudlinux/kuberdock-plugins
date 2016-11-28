<?php

const SITE_URL = 'http://repo.cloudlinux.com/kuberdock/';
const WHMCS_BILLING = 'whmcs';

if (PHP_SAPI !== 'cli') {
    die('Command line execution only' . PHP_EOL);
}

chdir(__DIR__);

// By adding another billing system: this must be called only in whmcs
require __DIR__ . '/init.php';

BillingPlugin::deploy(WHMCS_BILLING);

/*
 * end script
 */

class BillingPlugin
{
    protected $options;
    protected $billingName;
    protected $link;

    protected function __construct($billingName)
    {
        $opts_short = 'h::u::f::m::';
        $opts_long = array(
            'help::',
            'user::',
            'forced::',
            'local::',
            'migrate::',
            'kd_hostname::',
            'kd_ip::',
            'kd_login::',
            'kd_password::',
        );

        $this->options = getopt($opts_short, $opts_long);
        $this->billingName = $billingName;

        if ($this->issetOption('help')) {
            $this->showHelp();
        }
    }

    public static function deploy($billingName)
    {
        if ($billingName === WHMCS_BILLING) {
            $billing = new WhmcsPlugin($billingName);
        } else {
            die('Wrong billing' . PHP_EOL);
        }

        if (!$billing->command_exist('unzip')) {
            die("Unzip command not found." . PHP_EOL . "Please install unzip" . PHP_EOL . PHP_EOL);
        };

        $versionFrom = $billing->getCurrentVersion();

        $versionTo = $billing->issetOption('local')
            ? $billing->getLocalVersionAndLink($billing->getOption('local'))
            : $billing->getLastVersionAndLink();

        if ($billing->issetOption('migrate')) {
            $billing->migrate();
            die("Db migration performed. Files not copied" . PHP_EOL . PHP_EOL);
        }

        if ($versionFrom==$versionTo && !$billing->issetOption('forced')) {
            die("KuberDock plugin is already up-to-date." . PHP_EOL . PHP_EOL);
        }

        $tmpName = '/tmp/kuberdock-pligin.zip';

        if ($billing->issetOption('local')) {
            $billing->copyFile($billing->link, $tmpName);
        } else {
            $billing->downloadFile($billing->link, $tmpName);
        }

        $result = $billing->unZip($tmpName);

        $billing->changeOwner($result);
        unlink($tmpName);

        $billing->setServer();

        if (!is_null($versionFrom)) {
            $billing->migrate();
        }

        if (is_null($versionFrom)) {
            $billing->say("Installed version $versionTo of KuberDock plugin.\n");
        } else {
            $billing->say("KuberDock plugin upgraded from version $versionFrom to version $versionTo\n");
        }

    }

    protected function say($msg)
    {
        echo $msg . PHP_EOL;
    }

    protected function showHelp()
    {
        $help = <<<HELP

Using deploy script:

Put this file to WHMCS web docroot directory and type in command line:
    php deploy.php

The script will download last KuberDock plugin to current directory and upgrade db if needed.

Possible keys:

--help, -h - print this help
    php deploy.php --help

--kd_hostname,
--kd_ip,
--kd_login,
--kd_password - use these keys to add KuberDock server to WHMCS

--forced -f - Execute script even if current version is last.
    By default script stops if user has last version.

--user, -u - change owner of downloaded files (both commands beneath change owner to whmcs:whmcs)
    php deploy.php --user=whmcs
    php deploy.php --user=whmcs:whmcs

    Use this key only if you have write permissions!


HELP;

        die($help);
    }

    protected function getOption($option, $default = null)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        $short = $option[0];
        if (isset($this->options[$short])) {
            return $this->options[$short];
        }

        return $default;
    }

    protected function issetOption($option)
    {
        $short = $option[0];
        return isset($this->options[$option]) || isset($this->options[$short]);
    }

    protected function getLocalVersionAndLink($local)
    {
        $this->link = $local;
        $regexp = '/' . $this->billingName . "\-kuberdock\-plugin\-([\d\.\-]*)\.zip/";
        preg_match($regexp, $local, $match);
        $versionTo = $match ? $match[1] : 'local';
        $this->say('Will be installed local version: ' . $versionTo);

        return $versionTo;
    }

    protected function getLastVersionAndLink()
    {
        // get site
        $ch = curl_init(SITE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $site = curl_exec($ch);
        $status = curl_getinfo($ch);
        if ($status['http_code'] != 200) {
            die('Cannot download file ' . SITE_URL . PHP_EOL);
        }
        curl_close ($ch);

        $regexp = "href=[\'\"](" . $this->billingName . "\-kuberdock\-plugin\-([\d\.]*)\.zip)[\'\"]";

        // get last link
        $versionTo = null;
        if(preg_match_all("/$regexp/siU", $site, $matches)) {
            foreach ($matches[1] as $index => $currentUrl) {
                $versionTo = $this->getMax($versionTo, $matches[2][$index]);
                if ($versionTo==$matches[2][$index]) {
                    $this->link = SITE_URL . $currentUrl;
                }
            }
        }

        $this->say('Will be installed last version: ' . $versionTo);

        return $versionTo;
    }

    public function warningHandler($errno, $errstr)
    {
        // do nothing
    }

    protected function unZip($file)
    {
        $this->say('Unzip plugin');
        return shell_exec('unzip -o ' . $file);
    }

    protected function changeOwner($string)
    {
        if (!$this->issetOption('user')) {
            return;
        }

        $user = $this->getOption('user');

        if (!stripos($user, ':')) {
            $user = $user . ':' . $user;
        }

        $this->say('Change group:user to ' . $user);

        $lines = explode("\n", $string);
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'Archive:') === 0) {
                continue;
            }

            $line = preg_replace('/(inflating\:\s|extracting\:\s)/', '', $line);
            if (!$line) {
                continue;
            }

            shell_exec("chown $user $line");
        }
    }

    protected function getMax($a, $b)
    {
        $aArr = explode('.', $a);
        $bArr = explode('.', $b);

        for ($i=0; $i<=min(count($aArr), count($bArr)); $i++) {
            $aChunk = each($aArr);
            $bChunk = each($bArr);

            if ($bChunk === $aChunk) {
                continue;
            }

            return $bChunk > $aChunk
                ? $b
                : $a;
        }
    }

    protected function copyFile($url, $path)
    {
        $file = file_get_contents($url);
        file_put_contents($path, $file);
    }

    protected function downloadFile($url, $path)
    {
        $this->say('Download plugin from ' . $url);

        $ch = curl_init($url);
        $fp = fopen($path, 'wb');
        if (!$fp) {
            die("Cannot write file: $path" . PHP_EOL);
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);

        if(curl_errno($ch)){
            die('Cannot download file: ' . curl_error($ch) . PHP_EOL);
        }

        curl_close($ch);

        fclose($fp);
    }

    protected function command_exist($cmd)
    {
        return (bool) shell_exec("which $cmd 2>/dev/null");
    }
}

class WhmcsPlugin extends BillingPlugin
{
    protected function setServer()
    {
        if (
            (!$this->issetOption('kd_hostname') && !$this->issetOption('kd_ip'))
                || !$this->issetOption('kd_login')
                || !$this->issetOption('kd_password')
        ) {
            return;
        }

        $servers = $this->getTable('tblservers')->where('type', '=', 'KuberDock')->get();
        if (count($servers)) {
            return;
        }

        $accessHash = $this->getToken();

        $secret = $this->getSecret($accessHash);
        $this->setSecret($secret);

        $serverId = $this->getTable('tblservers')->insertGetId([
            'name' => 'KuberDock master',
            'ipaddress' => $this->getOption('kd_ip', ''),
            'hostname' => $this->getOption('kd_hostname', ''),
            'maxaccounts' => 200,
            'type' => 'KuberDock',
            'username' => $this->getOption('kd_login'),
            'password' => $this->encryptPassword(),
            'accesshash' => $accessHash,
            'secure' => 'on',
            'active' => 1,
            'disabled' => 0,
        ]);
        $this->say('KuberDock server added');

        $serverGroupId = $this->getTable('tblservergroups')->insertGetId([
            'name' => 'KuberDock group',
            'filltype' => 1,
        ]);
        $this->say('KuberDock group added');

        $this->getTable('tblservergroupsrel')->insert([
            'serverid' => $serverId,
            'groupid' => $serverGroupId,
        ]);
    }

    /**
     * Returns null if this is first installation, otherwise string like '1.0.7.3'
     *
     * @return null|string
     */
    protected function getCurrentVersion()
    {
        // try to load KuberDock.php, if failure - plugin not installed
        set_error_handler(array($this, "warningHandler"), E_WARNING);
        if ((include __DIR__ . '/modules/addons/KuberDock/KuberDock.php') === false) {
            $this->say('KuberDock plugin not installed. Performing installation');
            return null;
        }
        restore_error_handler();

        $config = KuberDock_config();

        $this->say('Current version is ' . $config['version']);
        return $config['version'];
    }

    protected function getToken()
    {
        $user = $this->getOption('kd_login') . ':' . $this->getOption('kd_password');
        $hostname = $this->getOption('kd_hostname', $this->getOption('kd_ip'));

        $result = shell_exec("curl -k --user " . $user . " https://" . $hostname . "/api/auth/token 2>/dev/null");
        $result = json_decode($result, true);

        if ($result['status']!= 'OK') {
            die('Error: ' . $result['data'] . PHP_EOL);
        }

        return $result['token'];
    }

    protected function getSecret($token)
    {
        $hostname = $this->getOption('kd_hostname', $this->getOption('kd_ip'));
        $result = shell_exec("curl -k 'https://" . $hostname . "/api/settings/sysapi?token=" . $token . "' 2>/dev/null");
        $result = json_decode($result, true);

        if ($result['status'] !== 'OK') {
            die('Error: ' . $result['data'] . PHP_EOL);
        }

        foreach ($result['data'] as $item) {
            if ($item['name'] === 'sso_secret_key') {
                return $item['value'];
            }
        }
    }

    public function setSecret($secret)
    {
        $configFile = "configuration.php";

        if (preg_match('/\$autoauthkey = \".+\"/i', file_get_contents($configFile)) == 0) {
            $secretKeyLine = PHP_EOL . '$autoauthkey = "' . $secret . '";' . PHP_EOL;
            file_put_contents($configFile, $secretKeyLine, FILE_APPEND);
            $this->say('Secret key added');
        }
    }

    protected function migrate()
    {
        $this->say('Performing DB migration');
        try {
            \migrations\Migration::up();
        } catch (Exception $e) {
            die('DB migration not possible: ' . $e->getMessage() . PHP_EOL);
        }
    }

    private function encryptPassword()
    {
        $password = $this->getOption('kd_password');
        $data = $this->apiCall('encryptpassword', array('password2' => $password));

        return $data['password'];
    }

    private function apiCall($command, $params = array())
    {
        return localAPI($command, $params, "admin");
    }

    private function getTable($table) {
        return Illuminate\Database\Capsule\Manager::table($table);
    }
}