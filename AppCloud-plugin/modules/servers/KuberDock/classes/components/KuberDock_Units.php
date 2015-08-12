<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */


class KuberDock_Units {
    /**
     * Package: persistent storage
     */
    const PS = 'MB';
    /**
     * Kube: CPU
     */
    const CPU = 'Cores';
    /**
     * Kube: memory
     */
    const MEMORY = 'MB';
    /**
     * Kube: HDD
     */
    const HDD = 'MB';
    /**
     * Kube & Package: Traffic
     */
    const TRAFFIC = 'GB';

    /**
     * @return string
     */
    static public function getPSUnits()
    {
        return self::PS;
    }

    /**
     * @return string
     */
    static public function getCPUUnits()
    {
        return self::CPU;
    }

    /**
     * @return string
     */
    static public function getMemoryUnits()
    {
        return self::MEMORY;
    }

    /**
     * @return string
     */
    static public function getHDDUnits()
    {
        return self::HDD;
    }

    /**
     * @return string
     */
    static public function getTrafficUnits()
    {
        return self::TRAFFIC;
    }
} 