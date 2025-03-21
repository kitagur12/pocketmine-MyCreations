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

class updatestatus
{
    public function update($packet, $player)
    {
        if (!isset($player)) {
            return;
        }

        if (!$player->isOnline()) {
            return;
        }

        $autoclick = new autoclick();
        $autoclick->update($packet, $player);

        $chat = new chat();
        $chat->update($packet, $player);

        $cheststealer = new cheststealer();
        $cheststealer->update($packet, $player);

        $crasher = new crasher();
        $crasher->update($packet, $player);

        $fasteat = new fasteat();
        $fasteat->update($packet, $player);

        $fastthrow = new fastthrow();
        $fastthrow->update($packet, $player);

        $fly = new fly();
        $fly->update($packet, $player);

        $invalidpackets = new invalidpackets();
        $invalidpackets->update($packet, $player);

        $invmanager = new invmanager();
        $invmanager->update($packet, $player);

        $invmove = new invmove();
        $invmove->update($packet, $player);

        $killaura = new killaura();
        $killaura->update($packet, $player);

        $movement = new movement();
        $movement->update($packet, $player);

        $reach = new reach();
        $reach->update($packet, $player);

        $regen = new regen();
        $regen->update($packet, $player);

        $suicude = new suicude();
        $suicude->update($packet, $player);

        $timer = new timer();
        $timer->update($packet, $player);

        $inspection = new inspection();
        $inspection->update($packet, $player);
    }
}
