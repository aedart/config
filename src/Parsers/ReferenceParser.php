<?php
namespace Aedart\Config\Parsers;

use Aedart\Config\Contracts\Parser;
use Aedart\Config\Exceptions\ParseException;
use Illuminate\Contracts\Config\Repository;

/**
 * Reference Parser
 *
 * TODO: desc...
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

            // In case that the replacement contains references itself, we need to
            // process that replacement entirely.
            if(is_string($replacement) && preg_match_all($this->pattern, $replacement, $x, PREG_SET_ORDER) !== 0){
                // Parse references in string
                $this->parseReferences($source, $reference, $replacement);

                // Re-fetch the replacement
                $replacement = $source->get($reference);
            }

            // The same is true, if the replacement is an array
            if(is_array($replacement)){
                // Parse the array
                $this->performParsing($source, $replacement, $reference);

                // Re-fetch the replacement
                $replacement = $source->get($reference);

                // However, because the replacement is still an array,
                // we need to set the key here and abort any further
                // processing or we might just re-overwrite it again.
                $source->set($key, $replacement);
                return;
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