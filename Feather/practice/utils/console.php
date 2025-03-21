<?php

namespace practice\utils;

use practice\practice;

class console
{
    public function __construct() {}

    public static function info($value): void
    {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§7[§finfo§7] §f" . $value . "\n§r");
    }

    public static function gsql($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§a[§egetsql§a] §e" . $value . "\n§r");
        //var_dump($trace);
    }

    public static function ssql($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§a[§bsendsql§a] §b" . $value . "\n§r");
    }

    public static function splv($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } else {
            $value = $value;
        }
        $parts = explode("\\", debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]["class"]);
        $lastPart = end($parts);
        self::updatetext("§b[§asetplv§b] §a" . $value . " §3file: §b" . $lastPart . " §3line: §b" . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]["line"] . "\n§r");
    }

    public static function gplv($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } else {
            $value = $value;
        }
        $parts = explode("\\", debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]["class"]);
        $lastPart = end($parts);
        self::updatetext("§b[§6getplv§b] §6" . $value . " §3file: §b" . $lastPart . " §3line: §b" . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]["line"] . "\n§r");
    }

    public static function debug($value): void
    {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§7[§8debug§7] §7" . $value . "\n§r");
    }

    public static function warn($value): void
    {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§e[warn] " . $value . "\n§r");
    }

    public static function error($value): void
    {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§c[§4error§c] §4" . $value . "\n§r");
    }

    public static function cls(): void
    {
        $n = 1;
        $result = str_repeat("\n", $n);
        print($result);
    }

    public static function updatetext($value): void
    {
        $colorCodes = [
            '§0' => "\033[38;2;0;0;0m",
            '§1' => "\033[38;2;0;0;170m",
            '§2' => "\033[38;2;0;170;0m",
            '§3' => "\033[38;2;0;170;170m",
            '§4' => "\033[38;2;170;0;0m",
            '§5' => "\033[38;2;170;0;170m",
            '§6' => "\033[38;2;255;170;0m",
            '§7' => "\033[38;2;170;170;170m",
            '§8' => "\033[38;2;85;85;85m",
            '§9' => "\033[38;2;85;85;255m",
            '§a' => "\033[38;2;85;255;85m",
            '§b' => "\033[38;2;85;255;255m",
            '§c' => "\033[38;2;255;85;85m",
            '§d' => "\033[38;2;255;85;255m",
            '§e' => "\033[38;2;255;255;85m",
            '§f' => "\033[0;37m",
            '§r' => "\033[0;37m",
            '§g' => "\033[38;2;221;214;5m",
            '§h' => "\033[38;2;227;213;211m",
            '§i' => "\033[38;2;208;203;203m",
            '§j' => "\033[38;2;75;65;66m",
            '§k' => "\033[3;37m",
            '§l' => "\033[4;37m",
            '§m' => "\033[38;2;158;43;29m",
            '§n' => "\033[38;2;184;112;87m",
            '§p' => "\033[38;2;223;180;56m",
            '§q' => "\033[38;2;40;166;72m",
            '§s' => "\033[38;2;86;191;177m",
            '§t' => "\033[38;2;46;83;130m",
            '§u' => "\033[38;2;160;102;199m"
        ];
        $text = str_replace(array_keys($colorCodes), array_values($colorCodes), $value);
        practice::CreateAsyncTask("practice\utils\\task\console", $text);
    }
}
