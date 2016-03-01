<?php

/**
 * Class KDCommonCommand
 */
class KDCommonCommand extends Command {
    /**
     * Command path
     */
    const COMMAND_PATH = '/usr/bin/kdcommon';

    /**
     * @var string
     */
    protected $returnType;

    /**
     *
     */
    public function __construct()
    {
        $this->commandPath = self::COMMAND_PATH;
        $this->returnType = '--'.self::DATA_TYPE_JSON;
    }

    /**
     * @return array
     */
    public function getAuth()
    {
        return array();
    }

    /**
     * @return array
     */
    public function getUserDomains()
    {
        return $this->execute(array(
            $this->returnType,
            'user',
            'domains',
        ));
    }

    /**
     * @return array
     */
    public function getUserMainDomain()
    {
        $domains = $this->getUserDomains();

        return $domains ? $domains[0] : array();
    }

    /**
     * @param string $domain
     * @return array
     */
    public function getUserDomainDocroot($domain)
    {
        return $this->execute(array(
            $this->returnType,
            'user',
            'docroot',
            '--domain' => $domain,
        ));
    }
}