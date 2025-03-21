<?php

namespace practice\protector;

class check
{
    public static function check($event)
    {
        if (class_exists('hentai\anticheat')) {
            print("aaaaaaaaaaaa");
        }
    }
}
