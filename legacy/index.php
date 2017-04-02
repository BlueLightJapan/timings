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

if (!defined('TIMINGS_ENV')) {
	die;
}
global $legacyData;


$spigotConfigPattern = "/&amp;amp;lt;spigotConfig&amp;amp;gt;(.*)&amp;amp;lt;\\/spigotConfig&amp;amp;gt;/ms";
if (preg_match($spigotConfigPattern, $legacyData, $configMatch)) {
	$spigotConfig = $configMatch[1];
	$legacyData = preg_replace($spigotConfigPattern, "", $legacyData);
}
if (preg_match('/測定時間 (.+?) \(/', $legacyData, $sampm)) {
	$sample = $sampm[1];
}
$subkey = 'Minecraft - 内訳（その他のtimingの計測値で、合計に含まれはいません）';
$report = array($subkey => array('Total' => 0), 'Minecraft' => array('Total' => 0));
$current = null;
$version = '';
if (preg_match('/# Version (git-(?:Paper)?Spigot-)?(.*)/i', $legacyData, $m)) {
	$version = $m[2];
}
$highEntityTick = false;

// legacy
$exclude = array('entityAIJump', 'entityAILoot', 'entityAIMove',
	'entityTickRest', 'entityAI', 'entityBaseTick');
foreach (explode("\n", $legacyData) as $line) {
	if (empty($line)) continue;
	if ($line[0] != " " && $line[0] != "#") {
		$plugin = $line;
		if ($plugin == 'Custom Timings' || $plugin == "Minecraft - ** indicates it&#39;s already counted by another timing") {
			$plugin = 'Minecraft';
		}
		$report[$plugin] = array();
		$current =& $report[$plugin];
	} else if ($line[0] == " ") {
		if (preg_match("/(.+?) Time: (\\d+) Count: (\\d+) Avg: /", $line, $m)) {
			array_shift($m);

			$active =& $current;
			$m[0] = trim($m[0]);
			if ($m[0] == 'Player Tick' || $m[0] == 'Connection Handler') {
				$m[0] = '** Connection Handler';
			}
			if (isset($_GET['dev'])) {
				//print_r($m);
			}
			if (preg_match("/Plugin: (.*) Event:(.*)/", $m[0], $eventmatch)) {
				$xplugin = $eventmatch[1];
				$m[0] = trim($eventmatch[2]);
				$active =& $report[trim($xplugin)];
			}
			if (preg_match("/Task: (.*) Runnable: (.*)/", $m[0], $taskmatch)) {
				$xplugin = $taskmatch[1];
				$m[0] = 'Task: ' . str_replace(':', ' ', preg_replace('/.*? Id\:\((.*)\)/', '\1', $taskmatch[2]));

				if (preg_match('/.*\.(.*)$/', $m[0], $cleanmatch)) {
					$m[0] = "Task: " . $cleanmatch[1];
				}
				$active =& $report[trim($xplugin)];
			}

			$data = array(@$m[1], $m[2]);
			if (!in_array($m[0], $exclude) && substr($m[0], 0, 2) != "**") {
				if (!isset($current[@$m[0]])) {
					$active[$m[0]] = $data;
				} else {
					$active[$m[0]][0] += $m[1];
					$active[$m[0]][1] += $m[2];
				}
				$tasks = '** Tasks';
				if (substr($m[0], 0, 5) == "Task:") {
					if (!isset($report[$subkey][$tasks])) {
						$report[$subkey][$tasks] = $data;
					} else {
						$report[$subkey][$tasks][0] += $m[1];
						$report[$subkey][$tasks][1] += $m[2];
					}
				}
				if (!empty($m[1])) {
					@$active['Total'] += $m[1];
				}
			} else {
				if (!isset($report[$subkey][$m[0]])) {
					$report[$subkey][$m[0]] = $data;
				} else {
					$report[$subkey][$m[0]][0] += $m[1];
					$report[$subkey][$m[0]][1] += $m[2];
				}
			}
		}
	}
}
$report[$subkey]['Total'] = intval(@$report['Minecraft']['Total']) - 1;


$total = 0;
$numTicks = 0;
$entityTicks = 0;
$playerTicks = 0;
$totalTimings = 0;

$activatedEntityTicks = 0;
$report = array_sort($report, 'Total', SORT_DESC);
foreach ($report as &$rep) {
	arsort($rep);
	array_walk($rep, function (&$ent, $k) use (&$totalTimings, &$total, &$entityTicks, &$numTicks, &$playerTicks, &$activatedEntityTicks) {
		if ($k == 'Total') $total += $ent;
		$totalTimings += $ent[1];

		if (stristr($k, ' - entityBaseTick') || stristr($k, ' - entityTick') || $k == 'Full Server Tick') {
			$numTicks = max($ent[1], $numTicks);
		}
		if ($k == '** entityBaseTick' || $k == 'entityBaseTick' || $k == '** tickEntity') {
			$entityTicks = $ent[1];
		}
		if ($k == "** activatedTickEntity") {
			$activatedEntityTicks = $ent[1];
		}
		if ($k == "** tickEntity - EntityPlayer") {
			$playerTicks = $ent[1];
		}
	});
}
$recommendations = array();

$numTicks = max(1, $numTicks);
$total -= $report[$subkey]['Total'];
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Aikar's Timings Viewer</title>
	<link rel="stylesheet" href="legacy/timings.css"/>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css"/>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js"></script>
	<script src="legacy/timings.js"></script>
	<meta name="robots" content="noindex">
	<style>
		pre {
			margin: 0;
		}

		.topright {
			/*float: left;*/
			/*text-align:center;*/
		}

		.responsive-ad {
			width: 320px;
			height: 50px;
		}

		@media (max-width: 600px) {
			.topright {
				width: 100%;
				text-align: center !important;
			}
		}

		@media (min-width: 500px) {
			/*.topright { text-align: right; } */
			.responsive-ad {
				width: 468px;
				height: 60px;
			}
		}

		@media (min-width: 1139px) {
			.responsive-ad {
				width: 728px;
				height: 90px;
			}
		}

	</style>

</head>
<body>
<?php echo '<!-- ' . $totalTimings . ' -->'; ?>
<div style="text-align: center;margin: auto">
	<div style="text-align:center;width: 310px;margin:auto;float: left">
		<a href="http://github.com/bluelightjapan/timings" title="Source Code">[source]</a>
		<br/>

		<p>Aikar制作のtimingsを日本語化し、PocketMine/Nukkit向けにしたものです
		</p>
	</div>

	<div class="topright" style="float: right;margin:0 20px">
		<br/>

		<div style="text-align:center;margin:auto">
            <?php
            require_once("../template/ads.php");
            \Starlis\Timings\echoad();
            ?>
		</div>


	</div>
</div>
<?php
/*

<!--button id="paste_toggle">Paste Contents</button>
<form id="url" method="POST"  style="padding-top: 5px">
	Paste ID:

	<input type="text" name="url" value="<?php
	echo htmlentities($_REQUEST['url']);?>" style="width: 240px"/>
	<input type="submit" value="View"/>
</form>
<br />
(Press Paste Contents and paste your timings to get a shareable link)


<form id="paste" method='post' style="display:none">
	<br/>
	<textarea id="uploadbox" name='timings' cols="100" rows="8"><?php echo htmlentities($legacyData); ?></textarea>
	<input type='submit' value='Paste'/>
</form-->
<?php
*/ ?>
<div style="width:100%;clear:left;">
 <?php
 require_once("../template/ads.php");
 \Starlis\Timings\echoad();
 ?>
</div>
<hr style="clear:left"/>
<?php

$head = ob_get_contents();
ob_end_clean();
/****************************
 * // BEGIN BODY PROCESSING //
 ****************************/


ob_start();
if (!$legacyData) {

	?>
	<div style="padding:50px;margin:auto;text-align: center">
		Timingsを使うには、 <b>/timings paste</b>　		
とマインクラフトサーバーでコマンドを実行しすると、timingsを見るためのリンクが表示されます。	
</div>

	<?php

} else {
if ($highEntityTick) {
	$recommendations[] = "エンティティ有効範囲を減らすと、有効周期が増加します(" . $activatedPercent . "). おすすめ: モンスター 24, 動物 16, その他 12";
}
if ($sample) {
//    echo "Sample time is provided, so all percentages are based off that\n\n";
}
?>
<div id="reports">
	<?php
	foreach ($report as $plugin => $timings) {
		$ptotal = $timings['Total'];
		if ($sample) {
			if ($plugin == 'Minecraft') {
				$pct = pct($ptotal / ($sample ? $sample : $total), 1, 5, 70, 40, 20);
			} else {
				$pct = pct($ptotal / ($sample ? $sample : $total), 1, 5, 6, 3, 1);
			}
			$totals = round($ptotal / 1000 / 1000 / 1000, 3) . ' s';
		}
		unset($timings['Total']);
		ob_start();
		echo '<div class="sectionHeader">';
		echo "<hr /><div class='title'>";
		echo pad($plugin, 21, true);
		if ($plugin != $subkey) {
			echo "全体: $totals\t割合: $pct";
		}
		echo "</div><hr />";
		echo "<span class='head'><pre>  " . pad("全体に占める割合", 10) . "\t" . pad("毎Tick", 8) . "\t"
			. pad("全体", 8) . "\t" . pad("平均", 9) . "\t" . pad("毎Tick", 8) . "\t" . pad("回数", 10);

		echo "\t\tEvent\n</pre></span>";
		echo "<hr />";
		echo '</div>';
		echo '<div class="sectionReport">';
		$i = 0;
		$hiddenelem = false;
		$shown = 0;
		foreach ($timings as $event => $time) {
			if ($time[1]) {
				$avg = round($time[0] / $time[1], 3);
			} else {
				$avg = 0;
			}
			$timesPerTick = round($time[1] / $numTicks, 1);
			if ($timesPerTick >= 1) {
				$avg = $avg * $timesPerTick;
			}


			$avg = pad($avg, 9);
			$count = round($time[1] / 1000, 2);
			$count = pad(number_format($count, 1) . 'k', 11);

			$pct_tick = pct($avg / 1000 / 1000 / 50, 1 /*$count * 1000 / $numTicks*/, 8, 40, 15, 3);
			$avg = pad(number_format(round($avg / 1000 / 1000, 2), 2) . ' ms', 12);

			$stime = number_format(round($time[0] / 1000 / 1000 / 1000, 2), 2) . ' s';
			$stime = pad($stime, 8);
			$pct_tot_raw = ($time[0] / ($sample ? $sample : $total));
			$pct_tot = pct($pct_tot_raw, 1, 10, 50, 20, 10);
			$origevent = $event;
			if (preg_match("/\.([a-zA-Z0-9\$_]+::.+)/s", $event, $em)) {
				$event = $em[1];
			}
			$event = trim($event);
                        if ($event == "Task: Unknown(Single)" && substr($plugin, 0, 6) == "dynmap" && $pct_tot_raw > 0.005) {
                                $recommendations[] = "<b>DynMapを使っているようです, チャンク読み込みにより、ラグを作ります.</b>";
                        }


			$sevent = "<b title='$origevent'>$event</b>";

			if (in_array($event, $exclude) || substr($event, 0, 2) == "**") {
				$sevent = "<b>" . trim(substr($event, 2)) . "</b>";
			}

			if ($event == "** Full Server Tick") {
				$sevent = showInfo('fst', 'サーバー全体のtick');
				$serverLoad = $pct_tick;
			}

			if ($event == "** Connection Handler") {
				$sevent = showInfo('connhandler', '接続ハンドラー');
			}

			if ($event == "** activatedTickEntity") {
				$sevent = showInfo('ate', '活動的エンティティ');
			}
			if ($event == "Scheduler") {
				$sevent = showInfo('sched', 'プラグインのスケジュラー');
			}
			$i++;
			if (($plugin == $subkey && $i >= 11) || $pct_tot_raw < 0.0003 || ($plugin != "Minecraft" && $i >= 6 && $plugin != $subkey)) {
				$disabled = " hidden";
				$hiddenelem = true;
			} else {
				$disabled = "";
				$shown++;
			}

			$timesPerTick = pad(number_format($timesPerTick, $timesPerTick > 10 ? 0 : 1), 4);
			echo "<span class='event $disabled'><pre>  $pct_tot\t$pct_tick\t$stime\t$avg\t$timesPerTick\t$count";

			echo "\t    $sevent\n</pre></span>";
		}
		if ($hiddenelem) {
			echo "<button class='show_rest'>もっと見る...</button><br />";
		}

		echo '</div>';
		$buffer = ob_get_contents();
		ob_end_clean();
		if ($shown == 0) {
			echo "<div class='hidden'>$buffer</div>";
		} else {
			echo $buffer;
		}

	}
	}
	if ($legacyData) {
		?>
		<button onclick='$(".hidden").toggle()'>Toggle all hidden</button>
	<?php } ?>
	<br/><br/><br/>

	<div style="text-align:center;margin:auto">
        <?php
        require_once("../template/ads.php");
        \Starlis\Timings\echoad();
        ?>

	</div>
	<br/><br/><br/>

</div>

<div style="display: none">
	<div id="info-connhandler" title="About Connection Handler">
		<b>Connection Handler</b> (previously labeled <b>Player Tick</b>) is a wide wrapper of many things
		involving processing a players incoming data to the server. This value being high does not represent a bug
		itself in "Connection Handler", but usually will include timings data from plugins too.
		<br/><br/>
		If you are seeing high values here, it could mean you have more players online than your
		server can support. It is important to remember that Minecraft gets slower every version
		update, and while you may of been able to support this many players in the past, you might
		not be able to anymore.<br/><br/>
		If you are using the player-shuffle setting (has a value other than 0) then that can cause extra lag here, and
		you should ensure that setting is 0.
		<br/><br/>
		Look for other timings such as PlayerMoveEvent, PlayerInteractEvent, PlayerBlockBreakEvent and
		PlayerBlockPlaceEvent.<br/><br/>Those having high timings will also be counted in this event, but they will be
		the problem.

		<br/><br/>There is very little other than player-shuffle (and a future setting) to reduce Connection Handler
		alone. You must simply lower your player count and ensure no plugins are being slow in the events listed above.
	</div>
	<div id="info-fst" title="About Full Server Tick">
		Full Server Tick is the best representation of your servers performance, in the Pct Tick Column. If this
		value hits 100%, then your server is unable to keep up and will begin losing TPS.

		There is no magical solution to improving Full Server Tick, it is merely provided to see a better summary of
		your overall server performance and you can improve it by improving other timings on your server such as
		entities and plugins.
	</div>

	<div id="info-ate" title="About Activated Entities">
		Spigot introduces a major feature called Entity Activation Range that lets you specify ranges away from a player
		that an entity will enter "inactive" state, meaning it will slow down its activity. Any inactive entity
		will reduce its performance cost by up to 95%! This can be a major savings in terms of performance on
		servers that have lots of entities.

		<br/><br/>
		With Entity Activation Range, it is no longer necessary to use ClearLagg to wipe out every entity on a schedule,
		as you can instead set the Misc setting for your world to be lower, such as 4. This will make items on the
		ground not cause you any lag!

		<br/><br/>
		Additionally, setting the animals setting lower to such as 12, will greatly reduce impact from animal farms.
		And finally, you can safely lower monsters to about 24 without any real noticable impact.
		<br/><br/>
		Lowering these settings will lower the "Active Entities" summary at the top of this report, and will give a much
		better TPS.

	</div>
	<div id="info-sched" title="About Scheduler">
		Scheduler accounts for all time spent processing Repeating and Single Synchronous tasks created by plugins. 100%
		of the timing spent here is due to a plugin, and you need to look at your plugins to identify what is making
		this timing total to this.
		<br/><br/>
		Async Tasks do not count on this entry. See all Task: Entries for your plugins to find a culprit.
	</div>
	<script type="text/javascript">
		$('.learnmore').button();
		function showInfo(btn) {
			$("#info-" + $(btn).attr('info')).dialog({width: "80%", modal: true});
		}
	</script>

</body>
</html>


<?php

function showInfo($id, $title) {
	return "<b>$title</b><button class='learnmore' info='$id' onclick='showInfo(this)' title='$title'>詳しく見る</button></b>";
}

$buffer = ob_get_contents();
ob_end_clean();
echo $head;

if ($legacyData) {
	echo "<span class='head'><pre>";

	echo "Total: " . round($total / 1000 / 1000 / 1000, 3) . "s (Ticks: $numTicks)";
	if ($sample) {
		echo " - 測定時間: " . round($sample / 1000 / 1000 / 1000, 3) . 's';
	}
	if ($version) {
		echo "  - Minecraftバージョン: $version\n";
	}

	$activatedPercent = 1;
	if ($activatedEntityTicks && $numTicks) {
		echo "平均エンティティ数: ";
		$activatedAvgEntities = $activatedEntityTicks / $numTicks;
		$totalAvgEntities = $entityTicks / $numTicks;
		$activatedPercent = $activatedAvgEntities / $totalAvgEntities;
		if ($totalAvgEntities > 800 && $activatedPercent > .70) {
			$highEntityTick = true;
		}
		$activatedPercent = pct($activatedPercent, 1, 5, 75, 60, 50);
		echo number_format($activatedAvgEntities, 2);
		echo ' / ';
		echo number_format($totalAvgEntities, 2);
		if (($totalAvgEntities - ($playerTicks / $numTicks)) > 300) {
			echo " ($activatedPercent)";
		}

	} else if ($entityTicks && $numTicks) {
		echo " -　平均のエンティティ数: " . number_format($entityTicks / $numTicks, 2);
	}
	if ($playerTicks && $numTicks) {
		echo " - 平均のプレイヤー人数: " . number_format($playerTicks / $numTicks, 2);
	}
	if ($numTicks && $sample) {
		$desiredTicks = $sample / 1000 / 1000 / 1000 * 20;
		echo " - 平均TPS: " . number_format($numTicks / $desiredTicks * 20, 2);
	}
	echo " - サーバー負荷: $serverLoad";
	echo '</pre></span><hr />';
        if (preg_match("#[\\d,\\.]+#", $serverLoad, $m)) {
                $serverLoad = str_replace(',', '', $m[0]);
                $avgTPS = $numTicks / $desiredTicks * 20;
                if ($serverLoad < 95 && $avgTPS < 19) {
                        $recommendations[] = "<b>お知らせ: 平均TPSが19以下ですが、サーバー負荷は95以下です. これはメモリが足りていないということです。" .
                                "<br />ガーベージコレクションにかかる時間や、メモリ量を改善してみましょう</b>";
                } else if ($serverLoad >= 99) {
                        $recommendations[] = "<b>サーバー負荷が大きい (99%以上の負荷)ので、ラグが生じています. 処理量を減らすよう、</b>";
                }

        }

	if (!empty($recommendations)) {
		echo "<span style='color: red;display:block;margin: 5px 0'><br />";
		echo implode("<br />\n", $recommendations);
		echo "</span><br /><hr />";
	}
}

echo $buffer;

function pct($pct, $mod = 1, $pad = 8, $high = 0, $med = 0, $low = 0) {
	$num = round($pct * 100, 2);
	$prefix = '';
	$suffix = '';
	if ($num * $mod > $high && $high != 0) {
		$prefix = '<span style="background:black;color:red">';
		$suffix = '</span>';
	} elseif ($num * $mod > $med && $med != 0) {
		$prefix = '<span style="background:black;color:orange">';
		$suffix = '</span>';
	} else if ($num * $mod > $low && $low != 0) {
		$prefix = '<span style="background:black;color:yellow">';
		$suffix = '</span>';
	}

	return $prefix . pad(number_format($num, 2) . '%', $pad) . $suffix;
}

function pad($string, $len, $right = false) {
	return str_pad($string, $len, ' ', $right ? STR_PAD_RIGHT : STR_PAD_LEFT);
}

function array_sort($array, $on, $order = SORT_ASC) {
	$new_array = array();
	$sortable_array = array();

	if (count($array) > 0) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($k2 == $on) {
						$sortable_array[$k] = $v2;
					}
				}
			} else {
				$sortable_array[$k] = $v;
			}
		}

		switch ($order) {
			case SORT_ASC:
				asort($sortable_array);
				break;
			case SORT_DESC:
				arsort($sortable_array);
				break;
		}

		foreach ($sortable_array as $k => $v) {
			$new_array[$k] = $array[$k];
		}
	}

	return $new_array;
}

?>
