<?php
/*
 * Aikar's Minecraft Timings Parser
 *
 * Written by Aikar <aikar@aikar.co>
 * http://aikar.co
 * http://starlis.com
 *
 * @license MIT
 */
namespace Starlis\Timings;

if(!isset($_GET['url'])) {
      $_GET['url'] = "welcome";
}

require_once "init.php";
Timings::bootstrap();
