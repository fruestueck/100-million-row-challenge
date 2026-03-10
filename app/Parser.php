<?php

namespace App;

final class Parser
{
    const int PARSE_URL_START = 25; // prefix 'https://stitcher.io/blog/'
    const int PARSE_URL_LEN = -27;
    const int PARSE_DATE_START = -26;
    const int PARSE_DATE_LEN = 10;
    const string BLOG = '\/blog\/';

    const int CHUNK_SIZE = 4096;

    public function parse(string $inputPath, string $outputPath): void
    {
        // $startTime = \microtime(true);
        // $baseMemory = memory_get_usage();
        // gc_disable();
        $handle = \fopen($inputPath, "r");

        $map = [];
        $buffer = '';
        while (! \feof($handle)) {
            $buffer .= \fread($handle, self::CHUNK_SIZE);
            $lines = \explode("\n", $buffer);

            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                $path = \substr($line, self::PARSE_URL_START, self::PARSE_URL_LEN);
                $date = (int) \str_replace('-', '', \substr($line, self::PARSE_DATE_START, self::PARSE_DATE_LEN));

                if(isset($map[$path][$date])) {
                    $map[$path][$date]++;
                } else {
                    $map[$path][$date] = 1;
                }
                // ld($line, $path, $date, array_first($map));
            }

        }

        \fclose($handle);
        $handle = null;
        unset($handle);

        // $midTime = \microtime(true);

        // write
        $out = \fopen($outputPath, 'w');
        $buffer = '{'.PHP_EOL;
        $lastPath = \array_key_last($map);
        foreach($map as $path => $dates) {
            $buffer .= "    \"".self::BLOG.$path."\": {".PHP_EOL;

            \ksort($dates);

            $lastDate = \array_key_last($dates);
            foreach($dates as $date => $visits) {
                $buffer .= "        \"".
                    \substr($date, 0, 4) . '-' . \substr($date, 4, 2) . '-' . \substr($date, 6, 2)
                    ."\": $visits".($date === $lastDate ? PHP_EOL : ','.PHP_EOL);
            }

            $buffer.= $path === $lastPath
                ? '    }' . PHP_EOL
                : '    },' . PHP_EOL;

            if(\strlen($buffer) > 1_000) {
                \fwrite($out, $buffer);
                $buffer = '';
            }
        }
        \fwrite($out, $buffer.'}');
        \fclose($out);

        // echo memory_get_usage() - $baseMemory, "\n";
        // $endTime = \microtime(true);
        // $time    = $endTime - $startTime;
        // $pT = $midTime - $startTime;
        // $oT = $endTime - $midTime;
        // $pP = \round($pT * 100 / $time, 1);
        // $oP = \round($oT * 100 / $time, 1);
        // echo '     ' . $pT . " $pP% process\n";
        // echo '     ' . $oT . " $oP% out\n";
    }
}