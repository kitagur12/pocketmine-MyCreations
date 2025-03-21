<?php

namespace hentai\modules;

use hentai\modules\autoclick;
use hentai\modules\chat;
use hentai\modules\cheststealer;
use hentai\modules\crasher;
use hentai\modules\fasteat;
use hentai\modules\fastthrow;
use hentai\modules\fly;
use hentai\modules\invalidpackets;
use hentai\modules\invmanager;
use hentai\modules\invmove;
use hentai\modules\killaura;
use hentai\modules\movement;
use hentai\modules\reach;
use hentai\modules\regen;
use hentai\modules\suicude;
use hentai\modules\timer;
use hentai\modules\inspection;

class loader
{
    public function load($packet, $player = null): void
    {
        if (!isset($player)) {
            return;
        }

        if (!$player->isOnline()) {
            return;
        }

        $autoclick = new autoclick();
        $autoclick->check($packet, $player);

        $chat = new chat();
        $chat->check($packet, $player);

        $cheststealer = new cheststealer();
        $cheststealer->check($packet, $player);

        $crasher = new crasher();
        $crasher->check($packet, $player);

        $fasteat = new fasteat();
        $fasteat->check($packet, $player);

        $fastthrow = new fastthrow();
        $fastthrow->check($packet, $player);

        $fly = new fly();
        $fly->check($packet, $player);

        $invalidpackets = new invalidpackets();
        $invalidpackets->check($packet, $player);

        $invmanager = new invmanager();
        $invmanager->check($packet, $player);

        $invmove = new invmove();
        $invmove->check($packet, $player);

        $killaura = new killaura();
        $killaura->check($packet, $player);

        $movement = new movement();
        $movement->check($packet, $player);

        $reach = new reach();
        $reach->check($packet, $player);

        $regen = new regen();
        $regen->check($packet, $player);

        $suicude = new suicude();
        $suicude->check($packet, $player);

        $timer = new timer();
        $timer->check($packet, $player);

        $inspection = new inspection();
        $inspection->check($packet, $player);
    }
}
