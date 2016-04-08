<?php

const SITE_URL = 'http://repo.cloudlinux.com/kuberdock/';
const WHMCS_BILLING = 'whmcs';

if (PHP_SAPI !== 'cli') {
    die('Command line execution only');
}

$help = <<<HELP

Using deploy script:

Put this file to WHMCS web docroot directory and type in command line:
    php deploy.php

The script will download last KuberDock plugin to current directory and upgrade db if needed.

Possible keys:

--help, -h - print this help
    php deploy.php --help

--user, -u - change owner of downloaded files (both commands beneath change owner to whmcs:whmcs)
    php deploy.php --user=whmcs
    php deploy.php --user=whmcs:whmcs

    Use this key only if you have write permissions!


HELP;

$options = getopt('h::u::', array('help::', 'user::'));

if (isset($options['help'])) {
    die($help);
}

if (!command_exist('unzip')) {
    die("Unzip command not found.\nPlease install unzip\n\n");
};

$billing = WHMCS_BILLING;

list($link, $versionTo) = getLastLink($billing);

// null if this is first installation
$versionFrom = getCurrentVersion($billing);

$tmpName = '/tmp/' . $link;
downloadFile(SITE_URL . $link, $tmpName);
$result = unZip($tmpName);
changeOwner($result, $options['user']);
unlink($tmpName);

if (!is_null($versionFrom)) {
    migrate();
}

if (is_null($versionFrom)) {
    echo "Installed version $versionTo of plugin.\n\n";
} else {
    echo "Plugin upgraded from version $versionFrom to version $versionTo\n\n";
}

/*
 * end script
 */

function migrate()
{
    // todo: remove deprecated
    $simpleMigration = new \components\KuberDock_Migration();
    $simpleMigration->migrate();

    \migrations\Migration::up();
}

function getCurrentVersion($billing)
{
    if ($billing === WHMCS_BILLING) {
        require 'init.php';

        // try to load init, if failure - plugin not installed
        set_error_handler("warningHandler", E_WARNING);
        if ((include 'modules/servers/KuberDock/init.php') === false) {
            return null;
        }
        restore_error_handler();

        $addonModulesClass = '\base\models\CL_AddonModules';

        // 1.0.6 - last version without CL_AddonModules
        return class_exists($addonModulesClass)
            ? $addonModulesClass::getSetting('version')
            : '1.0.6';
    }
}

function warningHandler($errno, $errstr)
{
    // do nothing
}

function unZip($file)
{
    return shell_exec('unzip -o ' . $file);
}

function changeOwner($string, $user = null)
{
    if (is_null($user)) {
        return;
    }

    if (!stripos($user, ':')) {
        $user = $user . ':' . $user;
    }

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

function getLastLink($billing)
{
    // get site
    $site = file_get_contents(SITE_URL);
    $regexp = "href=[\'\"](" . $billing . "\-kuberdock\-plugin\-([\d\.]*)\.zip)[\'\"]";

    // get last link
    if(preg_match_all("/$regexp/siU", $site, $matches)) {
        $version = null;
        $url = '';
        foreach ($matches[1] as $index => $currentUrl) {
            $version = getMax($version, $matches[2][$index]);
            if ($version==$matches[2][$index]) {
                $url = $currentUrl;
            }
        }
    }

    return array($url, $version);
}

function getMax($a, $b)
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

function downloadFile($url, $path)
{
    if ($from = fopen ($url, 'rb')) {
        if ($to = fopen ($path, 'wb')) {
            while(!feof($from)) {
                fwrite($to, fread($from, 1024 * 8), 1024 * 8);
            }
        } else {
            die("Can not write file: $path");
        }
    } else {
        die("Can not open url: $url");
    }

    if ($from) {
        fclose($from);
    }

    if ($to) {
        fclose($to);
    }
}

function command_exist($cmd)
{
    return (bool) shell_exec("which $cmd 2>/dev/null");
}
