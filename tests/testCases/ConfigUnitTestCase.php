<?php

use Aedart\Testing\TestCases\Unit\UnitTestCase;
use Illuminate\Config\Repository;

/**
 * Config Unit Test-Case
 *
 * @author Alin Eugen Deac <aedart@gmail.com>
 */
abstract class ConfigUnitTestCase extends UnitTestCase
{
    /**
     * Returns a new repository instance
     *
     * @param array $items [optional]
     *
     * @return Repository
     */
    public function makeRepository(array $items = [])
    {
        return new Repository($items);
    }

}