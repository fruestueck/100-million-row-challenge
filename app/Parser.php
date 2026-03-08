<?php

namespace App;

use Exception;

final class Parser
{
    public function parse(string $inputPath, string $outputPath): void
    {
        $fi = fopen($inputPath, "r");

        $sitePrefix = 'https://stitcher.io/blog/';
        $prefixLen = strlen($sitePrefix);
        $blog = "\/blog\/";

        $map = [];

        while (($line = fgets($fi)) !== false) {
            $substr = substr($line, $prefixLen);
            [$path, $date] = explode(',', $substr, limit: 2);

            $date = (int) str_replace('-','', substr($date, 0, 10));

            if(! array_key_exists($path, $map)) {
                $map[$path][$date] = 1;
            } else {
                if(! array_key_exists($date, $map[$path])) {
                    $map[$path][$date] = 1;
                } else {
                    $map[$path][$date]++;
                }
            }
        }

        fclose($fi);

        // write
        $fo = fopen($outputPath, "w");
        $buffer = '{'.PHP_EOL;
        $lastPath = array_key_last($map);
        foreach($map as $path => $dates) {
            $buffer .= "    \"$blog$path\": {".PHP_EOL;

            ksort($dates);

            $lastDate = array_key_last($dates);
            foreach($dates as $date => $visits) {
                $buffer .= "        \"".
                    substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                    ."\": $visits".($date === $lastDate ? '' : ',').PHP_EOL;
            }

            $buffer.= "    }".($path === $lastPath ? '' : ',').PHP_EOL;

            if(strlen($buffer) > 1_000) {
                fwrite($fo, $buffer);
                $buffer = '';
            }
        }
        fwrite($fo, $buffer.'}');
    }
}