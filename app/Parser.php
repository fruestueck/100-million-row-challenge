<?php

namespace App;

final class Parser
{
    public function parse(string $inputPath, string $outputPath): void
    {
        $startTime = microtime(true);
        //$baseMemory = memory_get_usage();
        //gc_disable();
        $handle = fopen($inputPath, "r");

        // $sitePrefix = 'https://stitcher.io/blog/';
        // $prefixLen = strlen($sitePrefix);
        $prefixLen = 25;
        $blog = '\/blog\/';

        $map = [];
        while (($line = fgets($handle)) !== false) {
            $pos = strpos($line, ',', $prefixLen);
            $path = substr($line, $prefixLen, $pos-$prefixLen);
            $date = (int) str_replace('-', '', substr($line, $pos + 1, 10));

            if(isset($map[$path][$date])) {
                $map[$path][$date]++;
            } else {
                $map[$path][$date] = 1;
            }
//            ld($line, $pos, $path, $date);
//            ld(array_first($map));
        }

        fclose($handle);
        $handle = null;
        unset($handle);

        $midTime = microtime(true);

        // write
        $out = fopen($outputPath, 'w');
        stream_set_write_buffer($out, 0);
        $buffer = '{'.PHP_EOL;
        $lastPath = array_key_last($map);
        foreach($map as $path => $dates) {
            $buffer .= "    \"$blog$path\": {".PHP_EOL;

            ksort($dates);

            $lastDate = array_key_last($dates);
            foreach($dates as $date => $visits) {
                $buffer .= "        \"".
                    substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                    ."\": $visits".($date === $lastDate ? PHP_EOL : ','.PHP_EOL);
            }

            $buffer.= $path === $lastPath
                ? '    }' . PHP_EOL
                : '    },' . PHP_EOL;

            if(strlen($buffer) > 1_000) {
                fwrite($out, $buffer);
                $buffer = '';
            }
        }
        fwrite($out, $buffer.'}');
        fclose($out);

        // echo memory_get_usage() - $baseMemory, "\n";
        $endTime = microtime(true);
        $time    = $endTime - $startTime;
        $pT = $midTime - $startTime;
        $oT = $endTime - $midTime;
        $pP = round($pT * 100 / $time, 1);
        $oP = round($oT * 100 / $time, 1);
        echo '     ' . $pT . " $pP% process\n";
        echo '     ' . $oT . " $oP% out\n";
    }
}