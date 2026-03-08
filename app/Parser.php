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

            $date = substr($date, 0, 10);

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
        fwrite($fo, '{'.PHP_EOL);
        foreach($map as $path => $dates) {
            fwrite($fo, "    \"$blog$path\": {".PHP_EOL);

//            usort($dates, fn($a, $b) => strcmp($a[0], $b[0]));
            ksort($dates);

            foreach($dates as $date => $visits) {
                fwrite($fo, "        \"$date\": $visits".(next($dates) !== false ? ',' : '').PHP_EOL);
            }
            fwrite($fo, "    }".(next($map) !== false ? ',' : '').PHP_EOL);
        }
        fwrite($fo, '}');
    }
}