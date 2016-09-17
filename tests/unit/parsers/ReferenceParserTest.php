<?php

use Aedart\Config\Parsers\ReferenceParser;
use Codeception\Util\Debug;

/**
 * ReferenceParserTest
 *
 * @group parsers
 * @group reference-parser
 *
 * @author Alin Eugen Deac <aedart@gmail.com>
 */
class ReferenceParserTest extends ConfigUnitTestCase
{

    /***************************************************
     * Helpers
     ***************************************************/

    /**
     * Make a new instance of a Reference Parser
     *
     * @return ReferenceParser
     */
    public function makeParser()
    {
        return new ReferenceParser();
    }

    /***************************************************
     * Actual tests
     ***************************************************/

    /**
     * @test
     */
    public function canObtainInstance()
    {
        $parser = $this->makeParser();

        $this->assertNotNull($parser);
    }

    /**
     * @test
     */
    public function canParseReferences()
    {
        $items = [
            'mqtt' => [
                'driver'            => $this->faker->word,
                'haltOnFailure'     => '{{resources.halt}}',
                'db'                => [
                    'ip'      => '{{db.host}}::{{db.port}}',
                    'port'    => '{{db.port}}',
                    'profile' => '{{profiles.driver}}'
                ],
                'extra' => '{{db.desc}}',
                'available' => [
                    'profiles' => '{{profiles}}',
                    'db'       => '{{db}}'
                ],
                'other' => [
                    'method' => '{{resources.method}}',
                    'object' => '{{resources.object}}'
                ]
            ],
            'profiles' => [
                'driver' => '{{mqtt.driver}}'
            ],
            'resources' => [
                'halt'   => $this->faker->boolean(),
                'method' => function(){},
                'object' => new stdClass()
            ],
            'db' => [
                'host'      => $this->faker->ipv4,
                'port'      => $this->faker->randomNumber(4, true),
                'desc'    => '{{mqtt.driver}} running on {{mqtt.db.ip}}'
            ]
        ];

        $parser = $this->makeParser();
        $config = $parser->parse($this->makeRepository($items));

        // Debug
        //dd($config->all());

        // Original
        //Debug::debug($items); // WARNING - Debug::debug does NOT handle recursive references (objects)

        // Processed
        //Debug::debug($config->all()); // WARNING - Debug::debug does NOT handle recursive references (objects)

        // Singular reference
        $expectedMqttHaltOnFailure = $items['resources']['halt'];
        $this->assertSame($expectedMqttHaltOnFailure, $config->get('mqtt.haltOnFailure'), 'Singular references in value not handled');
        $this->assertInternalType(gettype($expectedMqttHaltOnFailure), $config->get('mqtt.haltOnFailure'), 'Incorrect type');

        // Multiple references in same value
        $expectedMqttDbIp = $items['db']['host'] . '::' . $items['db']['port'];
        $this->assertSame($expectedMqttDbIp, $config->get('mqtt.db.ip'), 'Multiple references in same value not handled');

        // Nested references
        $expectedMqttDbProfile = $items['mqtt']['driver'];
        $this->assertSame($expectedMqttDbProfile, $config->get('mqtt.db.profile'), 'Nested not handled');

        // Nested arrays
        $expectedMqttAvailableProfiles = json_encode([
            'driver' => $items['mqtt']['driver']
        ]);
        $this->assertSame($expectedMqttAvailableProfiles, json_encode($config->get('mqtt.available.profiles')), 'Nested arrays not handled');

        // Objects and methods
        $expectedObject = $items['resources']['object'];
        $expectedMethod = $items['resources']['method'];
        $this->assertSame($expectedObject, $config->get('mqtt.other.object'), 'Object reference not handled');
        $this->assertSame($expectedMethod, $config->get('mqtt.other.method'), 'Method reference not handled');
    }
}