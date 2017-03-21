<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System;
use System\Cache\CacheItem;

/**
 * The AspResponse class is used to properly format
 * the official Gamespy ASP Header and Data output for
 * Awards and player stats.
 *
 * @package System
 *
 * @author Steven Wilson
 */
class AspResponse
{
    /**
     * Indicated whether this response starts with an E, or an O
     * @var bool
     */
    protected $error = false;

    /**
     * For bf2142, this contains the error code
     * @var int
     */
    protected $errorCode = 0;

    /**
     * Contains the output lines
     * @var string[]
     */
    protected $lines = array();

    /**
     * Clears all current output lines
     *
     * @return void
     */
    public function clear()
    {
        $this->lines = array();
    }

    /**
     * Takes an indefinite amount of parameters. Each param passed
     * is a new header variable, appended to the head tag.
     *
     * Each time this method is called, a new Head tag is created
     */
    public function writeHeaderLine()
    {
        $this->lines[] = "H\t" . implode("\t", func_get_args());
    }

    /**
     * Takes an indefinite amount of parameters. Each param passed
     * is a new data variable, appended to the data tag.
     *
     * Each time this method is called, a new data line is created
     */
    public function writeDataLine()
    {
        $this->lines[] = "D\t" . implode("\t", func_get_args());
    }

    /**
     * This method adds an un-formatted line to the output
     *
     * @param string $line
     */
    public function writeLine($line)
    {
        $this->lines[] = $line;
    }

    /**
     * This method takes an array of Header => Value, and creates
     * a new Header and Data line for it
     *
     * @param string[] $data
     *
     * @return void
     */
    public function writeHeaderDataArray($data)
    {
        $this->lines[] = "H\t" . implode("\t", array_keys($data));
        $this->lines[] = "D\t" . implode("\t", array_values($data));
    }

    /**
     * This method takes an array. Each array value is a header variable
     *
     * @param string[] $headers
     *
     * @return void
     */
    public function writeHeaderLineArray($headers)
    {
        $this->lines[] = "H\t" . implode("\t", $headers);
    }

    /**
     * This method takes an array. Each array value is a data variable
     *
     * @param string[] $data
     *
     * @return void
     */
    public function writeDataLineArray($data)
    {
        $this->lines[] = "D\t" . implode("\t", $data);
    }

    /**
     * This method is uesd to define whether this response is to be
     * formmated with the "E" error header
     *
     * @param $bool
     * @param int $code For bf2142 only... specifies the error code
     */
    public function responseError($bool, $code = 0)
    {
        $this->error = $bool;
        $this->errorCode = $code;
    }

    /**
     * Sends the formatted response to the browser
     *
     * @param bool $transpose Enabling transpose will print the data
     *  to the browser in a different format. All headers will be a new line,
     *  with the following data to the left of the header, separated by tabs
     * @param CacheItem $item If this response is to be cached, provide the CacheItem
     *  object. otherwise, leave null
     * @param bool $killScript If set to true, the script
     *  will die when this method is called.
     *
     * @return void
     */
    public function send($transpose = false, CacheItem $item = null, $killScript = true)
    {
        // Do we cache this response?
        if (is_null($item))
        {
            // output the response
            echo $this->getString($transpose);
        }
        else
        {
            $contents = $this->getString($transpose);
            $item->set($contents);
            $item->save();
            echo $contents;
        }

        // Kill the script
        if ($killScript) die;
    }

    /**
     * Gets the current response as a string
     *
     * @param bool $transpose Enabling transpose will print the data
     *  to the browser in a different format. All headers will be a new line,
     *  with the following data to the left of the header, separated by tabs
     *
     * @return string
     */
    public function getString($transpose = false)
    {
        // Initial line
        $line = (($this->error) ? "E" : "O");
        if ($this->errorCode != 0)
            $line .= "\t" . $this->errorCode;

        // Transpose output?
        if ($transpose)
        {
            // Add response type
            $lines = array($line);

            // Line counter
            $i = 0;
            foreach ($this->lines as $line)
            {
                // D's to print
                $ds = 0;

                // Only process new lines (headers)
                if ($line[0] == "H")
                {
                    // next line
                    $j = $i + 1;

                    // Convert all headers to separate lines
                    $hLines = explode("\t", $line);

                    // Foreach following data line, add to the current header line
                    while (isset($this->lines[$j]) && $this->lines[$j][0] == "D")
                    {
                        // Add each data value to the corresponding header line
                        $data = explode("\t", $this->lines[$j]);
                        for ($n = 0; $n < sizeof($hLines); $n++)
                            $hLines[$n] .= "\t" . ((isset($data[$n])) ? $data[$n] : "");

                        // Increment line counter, and Increment D's to print
                        $j++;
                        $ds++;
                    }

                    // Add correct number of data columns
                    $hLines[0] = "H" . str_repeat("\tD", $ds);

                    // Add each header line to the lines array
                    $lines = array_merge($lines, $hLines);
                }

                $i++;
            }

            // Output the data, adding the number of characters
            $output = implode("\n", $lines);
            $num = strlen(preg_replace('/[\t\n]/', '', $output));

            return $output . "\n" . "$\t$num\t$";
        }
        else
        {
            // Create a new reference
            $lines = $this->lines;

            // Prepend the response type
            array_unshift($lines, $line);

            // Output the data, adding the number of characters
            $output = implode("\n", $lines);
            $num = strlen(preg_replace('/[\t\n]/', '', $output));

            return $output . "\n" . "$\t$num\t$";
        }
    }
}