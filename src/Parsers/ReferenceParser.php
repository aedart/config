<?php
namespace Aedart\Config\Parsers;

use Aedart\Config\Contracts\Parser;
use Aedart\Config\Exceptions\ParseException;
use Illuminate\Contracts\Config\Repository;

/**
 * <h1>Reference Parser</h1>
 *
 * Parse "references" in values.
 *
 * <br />
 *
 * <b>Example</b>:
 * <pre>
 *      // Given the following configuration
 *      $items = [
 *          'db.driver'         => '{{defaults.driver}}',
 *          'defaults.driver'   => 'abc'
 *      ];
 *
 *      // When it is parsed
 *      $repo   = new Repository($items);
 *      $config = (new ReferenceParser())->parse($repo);
 *
 *      // The 'db.driver' key is parsed to the value of 'defaults.driver'
 *      echo $config->get('db.driver'); // output 'abc'
 * </pre>
 *
 * <br />
 *
 * <b>Reference Syntax</b>:
 * <pre>
 *      <option-tag><config-key><close-tag>
 *
 *      <option-tag>    : "{{"
 *      <close-tag>     : "}}"
 *      <config-key>    : A valid configuration key
 * </pre>
 *
 * <br />
 *
 * <b>Warning</b>:
 *
 * Parsing references can cost a lot of processing power. Whenever it is possible,
 * you should cache the result and use the parsed result!
 *
 * @author Alin Eugen Deac <aedart@gmail.com>
 * @package Aedart\Config\Parsers
 */
class ReferenceParser implements Parser
{
    /**
     * Opening tag
     */
    const OPEN_TAG = '{{';

    /**
     * Closing tag
     */
    const CLOSE_TAG = '}}';

    /**
     * Search pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * List of processed keys
     *
     * @var array
     */
    protected $processedKeys = [];

    /**
     * ReferenceParser constructor.
     */
    public function __construct()
    {
        $this->pattern = '/' . self::OPEN_TAG . '(.*?)' . self::CLOSE_TAG . '/';
    }

    /**
     * Parse the given configuration
     *
     * @param Repository $config
     *
     * @return Repository
     *
     * @throws ParseException
     */
    public function parse(Repository $config)
    {
        // Fetch all items
        $items = $config->all();

        // Parse
        $this->performParsing($config, $items);

        // Reset the processed keys
        $this->processedKeys = [];

        // Return the repository
        return $config;
    }

    /**
     * Performs the parsing
     *
     * @param Repository $source
     * @param array $items [optional]
     * @param string $parentKey [optional]
     */
    protected function performParsing(Repository $source, array $items = [], $parentKey = '')
    {
        foreach ($items as $childKey => $value){

            // Compute the key
            $key = !empty($parentKey) ? $parentKey . '.' . $childKey : $childKey;

            // Avoid parsing if key already parsed
            if(isset($this->processedKeys[$key])){
                continue;
            }

            if(is_string($value)){
                $this->parseReferences($source, $key, $value);
            }

            if(is_array($value)){
                $this->performParsing($source, $value, $key);
            }
        }
    }

    /**
     * Parses eventual references in the given value
     *
     * @param Repository $source
     * @param string $key
     * @param mixed $value
     */
    protected function parseReferences(Repository $source, $key, $value)
    {
        // Skip if key has already been processed
        if(isset($this->processedKeys[$key])){
            return;
        }

        // Match all references and abort if none found...
        $amount = preg_match_all($this->pattern, $value, $matches, PREG_SET_ORDER);
        if($amount === 0){
            return;
        }

        $searchList = [];
        $replaceList = [];

        foreach ($matches as $match){
            $token = $match[0];
            $reference = $match[1];

            // Fail if reference does not exist
            if(!$source->has($reference)){
                throw new ParseException(sprintf('Cannot find "%s" in source repository', $token));
            }

            // Obtain the value for the reference
            $replacement = $source->get($reference);

            // If replacement is an array, then that array must be parsed
            if(is_array($replacement)){
                // Parse the array
                $this->performParsing($source, $replacement, $reference);

                // Re-fetch the replacement
                $replacement = $source->get($reference);

                // However, because the replacement is still an array,
                // we need to set the key here and abort any further
                // processing or we might just re-overwrite it again.
                $source->set($key, $replacement);
                $this->processedKeys[$key] = true;
                return;
            }

            // If not a string, then we also just set the value and stop
            // processing - ONLY if the value that we are processing does
            // not contain multiple references
            if(!is_string($replacement) && $amount == 1){

                // However, because the replacement is still an array,
                // we need to set the key here and abort any further
                // processing or we might just re-overwrite it again.
                $source->set($key, $replacement);
                $this->processedKeys[$key] = true;
                return;
            }

            // If, however, the replacement is a string, then that string could
            // contain further references which also need to be processed
            if(preg_match_all($this->pattern, $replacement, $x, PREG_SET_ORDER) !== 0){
                // Parse references in string
                $this->parseReferences($source, $reference, $replacement);

                // Re-fetch the replacement
                $replacement = $source->get($reference);
            }

            // Add token and replacement to list
            $searchList[] = $token;
            $replaceList[] = $replacement;
        }

        // Replace all references in string with found replacements
        $newValue = str_replace($searchList, $replaceList, $value);
        $source->set($key, $newValue);

        // Mark the key as being processed to avoid dual iteration
        $this->processedKeys[$key] = true;
    }
}