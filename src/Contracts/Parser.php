<?php
namespace Aedart\Config\Contracts;

use Aedart\Config\Exceptions\ParseException;
use Illuminate\Contracts\Config\Repository;

/**
 * Configuration Repository Parser
 *
 * @author Alin Eugen Deac <aedart@gmail.com>
 * @package Aedart\Config\Contracts
 */
interface Parser
{
    /**
     * Parse the given configuration
     *
     * @param Repository $config
     *
     * @return Repository
     *
     * @throws ParseException
     */
    public function parse(Repository $config);
}