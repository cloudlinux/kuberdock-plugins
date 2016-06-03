<?php

namespace Kuberdock\classes;

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
     * @param string $user
     * @return array
     */
    public function getUserDomains($user = '')
    {
        $attributes = array(
            $this->returnType,
            'user',
            'domains',
        );

        if ($user) {
            $attributes['--user'] = $user;
        }
        return $this->execute($attributes);
    }

    /**
     * @param string $user
     * @return array
     */
    public function getUserMainDomain($user = '')
    {
        $domains = $this->getUserDomains($user);

        return $domains ? $domains[0] : array();
    }

    /**
     * @param string $domain
     * @param string $user
     * @return array
     */
    public function getUserDomainDocroot($domain, $user = '')
    {
        $attributes = array(
            $this->returnType,
            'user',
            'docroot',
            '--domain' => $domain,
        );

        if ($user) {
            $attributes['--user'] = $user;
        }

        return $this->execute($attributes);
    }

    /**
     * @return string
     */
    public function getPanel()
    {
        return $this->execute(array(
            $this->returnType,
            'panel',
            'detect',
        ));
    }
}