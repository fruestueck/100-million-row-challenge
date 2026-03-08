<?php

namespace App;

use Exception;

final class Parser
{
    public function parse(string $inputPath, string $outputPath): void
    {
        //$baseMemory = memory_get_usage();
        //gc_disable();
        $handle = fopen($inputPath, "r");

        // $sitePrefix = 'https://stitcher.io/blog/';
        // $prefixLen = strlen($sitePrefix);
        $prefixLen = 25;
        $blog = '\/blog\/';

        $map = [];

        while (($line = fgets($handle)) !== false) {
            $substr = substr($line, $prefixLen);
            [$path, $date] = explode(',', $substr, limit: 2);

            $date = (int) str_replace('-','', substr($date, 0, 10));

            if(isset($map[$path][$date])) {
                $map[$path][$date]++;
            } else {
                $map[$path][$date] = 1;
            }
        }

        fclose($handle);
        $handle = null;
        unset($handle);

        // write
        $handle = fopen($outputPath, 'w');
        $buffer = '{'.PHP_EOL;
        $lastPath = array_key_last($map);
        foreach($map as $path => $dates) {
            $buffer .= "    \"$blog$path\": {".PHP_EOL;

            ksort($dates);

            $lastDate = array_key_last($dates);
            $countDates = count($dates);

            foreach($dates as $date => $visits) {
                $buffer .= "        \"".
                    substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                    ."\": $visits".($date === $lastDate ? PHP_EOL : ','.PHP_EOL);
            }

            $buffer.= $path === $lastPath
                ? '    }' . PHP_EOL
                : '    },' . PHP_EOL;

            if(strlen($buffer) > 1_000) {
                fwrite($handle, $buffer);
                $buffer = '';
            }
        }
        fwrite($handle, $buffer.'}');
        // ld(array_first($map));
        // echo memory_get_usage() - $baseMemory, "\n";
    }
}