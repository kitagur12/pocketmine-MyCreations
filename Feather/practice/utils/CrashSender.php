<?php

namespace practice\utils;

use webhook\Webhook;
use FilesystemIterator;

class CrashSender
{
    public static function check()
    {/**/
        $currentPath = __DIR__;
        $filePath = str_replace("plugins\Feather\src\practice\utils", "crashdumps", $currentPath);
        $iterator = new FilesystemIterator($filePath);
        foreach ($iterator as $file) {
            $content = file_get_contents($file);
            $content = self::parseCrashDump($content);
            $content = yaml_emit($content);
            $content = trim($content);
            $errorLines = explode("\n", $content);
            array_shift($errorLines);
            array_pop($errorLines);
            $content = implode("\n", $errorLines);
            unlink($file);
            Webhook::setWebhookUrl("https://discordapp.com/api/webhooks/1333102288956297246/Wp8VJo3O4HPrJQSiLmTi36WRIrhCfm6dlg5LnCgiPfz_7VviIaaWhELHoOSjZ1HAbliA");
            Webhook::setColor("800000");
            Webhook::setTitle("Server Crashed");
            Webhook::setContents($content);
            Webhook::sendWebhook();
        }/**/
    }

    private static function parseCrashDump($content)
    {
        $patterns = [
            'Message' => '/Error: (.+)/',
            'File' => '/File: (.+)/',
            'Line' => '/Line: (\d+)/',
        ];

        $results = [];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $results[$key] = trim($matches[1]);
            } else {
                $results[$key] = "情報なし";
            }
        }

        if (isset($results['loaded_plugins'])) {
            $pluginLines = explode("\n", trim($results['loaded_plugins']));
            $plugins = [];
            foreach ($pluginLines as $line) {
                if (preg_match('/(.+?)\s(\d+\.\d+\.\d+)\sby\s(.+?)\sfor\sAPI\(s\)\s(.+)/', $line, $pluginMatches)) {
                    $plugins[] = [
                        'name' => trim($pluginMatches[1]),
                        'version' => trim($pluginMatches[2]),
                        'author' => trim($pluginMatches[3]),
                        'api' => trim($pluginMatches[4]),
                    ];
                } else {
                    //$plugins[] = trim($line); //ここのコメント外すと未定義の奴も表示される
                }
            }
            $results['loaded_plugins'] = $plugins;
        }

        return $results;
    }
}
