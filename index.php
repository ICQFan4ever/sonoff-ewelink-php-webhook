﻿<?php

include_once 'core.php';

autOnly();

define('TITLE', 'Все устройства');
getHeader();


$date = @preg_match('#^(?:[0-9]{4})\-(?:[0-9]{2})\-(?:[0-9]{2})$#', $_GET['date']) ? $_GET['date'] : date('Y-m-d');
$view_date = explode('-', $date);
$view_date = array_reverse($view_date);
$view_date = implode('.', $view_date);

$q_devices = mysql_query("SELECT * FROM `ewelink_devices`");

?>

<div class="alert alert-primary" role="alert">
  Статистика устройств за <b><?=$view_date?></b>
</div>

<div class="row">
	<form action="index.php" method="get">
		<div class="col-sm">
			<input type="date" value="<?=$date?>" id="date" name="date" class="form-control" style="margin-bottom: 5px;" />
		</div>
		
		
		<div class="col-sm">
			<input type="submit" value="Посмотреть" class="btn btn-primary" />
			<a href="index.php" class="btn btn-default" href="Сегодня">Сегодня</a>
		</div>
	</form>
</div>

<div class="row">

<?php

while($_DEVICE = mysql_fetch_assoc($q_devices))
	{
		
		// делаем запрос на определение последнего события
		$q_last = mysql_query("SELECT * FROM `ewelink_events` WHERE `id_device` = ".$_DEVICE['id']." ORDER BY `id` DESC LIMIT 1");
		
		if(mysql_num_rows($q_last) == 0)
			{
				$status = 0;
			}
		else
			{
				$event_last = mysql_fetch_assoc($q_last);
				
				$status = $event_last['action'];
			}
		
		echo '<div class="col-sm-4 device_card">';
		echo '<div style="text-align: center"><b>'.$_DEVICE['full_name'].'</b> ';
		
		if($status == 0)
			{
				echo '<a class="badge badge-pill badge-secondary" href="ifttt_link.php?id_device='.$_DEVICE['id'].'&action=on&date='.$date.'">off</a>';
			}
		else
			{
				echo '<a class="badge badge-pill badge-primary" href="ifttt_link.php?id_device='.$_DEVICE['id'].'&action=off&date='.$date.'">on</a>';
			}
		
		echo '</div>';
		$day_start = strtotime($date." 00:00:00");
		$day_end = strtotime($date." 23:59:59");
		$unixtime = time();

		// фикс если запускается раньше конца текущего дня
		if($day_end > $unixtime)
			{
				$day_end = $unixtime;
			}

		$q = mysql_query("SELECT * FROM `ewelink_events` WHERE `id_device` = ".$_DEVICE['id']." AND (`time` BETWEEN ".$day_start." AND ".$day_end.")");
		
		if(mysql_num_rows($q) > 0)
			{
				// Собираем список событий
				$events = array();

				while($event = mysql_fetch_assoc($q))
					{
						$events[] = array($event['action'], $event['time']);
					}
				
				// Определяем последнее событие
				$c_events = count($events);
				$last_event = $events[$c_events - 1][0];
				
				
				// определяем первое событие
				$first_event = $events[0][0];

				// Если первое событие - включение, то ничего не делаем, если выключение - фиксируем включение в 00:00:00. Третий аргумент для пометки фейкового включения 
				if($first_event == 0)
					{
						// die('СВЕТ БЫЛ ВКЛЮЧЕН!');
						$events[] = array(1, $day_start, true);
					}
				

				// Если последнее событие - выключение, то ничего не делаем, если включение - фиксируем выключение в 23:59:59. Третий аргумент для пометки фейкового выключения
				if($last_event == 1)
					{
						$events[] = array(0, $day_end, true);
					}

				// считаем сумму и включения-выключения
				$sum_off = 0;
				$c_off = 0;
				$sum_on = 0;
				$c_on = 0;
				
				
				foreach($events as $event)
					{
						if($event[0] == 0)
							{
								$sum_off += $event[1];
								// фикс того, будто мы считаем включение в 00:00:00, чтобы не было расхождения total счетчика
								if(!isset($event[2]))
									{
										$c_off++;
									}
							}
						else
							{
								$sum_on += $event[1];
								// фикс того, будто мы считаем выключение в 23:59:59, чтобы не было расхождения total счетчика
								if(!isset($event[2]))
									{
										$c_on++;
									}
							}
					}

				$uptime = $sum_off - $sum_on;
			}
		else
			{
				// фикс: если свет включен, но событий за сегодня нет, для подсчета аптайма ищем последнее событиебез указания BETWEEN 
				if($status == 1)
					{
						$q_last_on = mysql_query("SELECT * FROM `ewelink_events` WHERE `id_device` = ".$_DEVICE['id']." AND `action` = 1 ORDER BY `time` DESC LIMIT 1");
						$last_on_event = mysql_fetch_assoc($q_last_on);
						$uptime = $unixtime - $last_on_event['time'];
					}
				else
					{
						$uptime = 0;
					}
				$c_on = 0;
				$c_off = 0;
			}

		# высчитываем аптайм
		$string = showTimeInterval($uptime);
		
		echo '<span class="small">За день:</span> ';
		echo !empty($string) ? '<span class="badge badge-info">'.$string : '<span class="badge badge-warning">не работало';
		echo '</span><br />
		
		<!--<span class="badge badge-pill badge-light">'.$c_on.' вкл.</span> / <span class="badge badge-pill badge-dark">'.$c_off.' выкл</span>
		
		<br />-->';
		
		// Подсчет общей статистики
		
		$q = mysql_query("SELECT * FROM `ewelink_events` WHERE `id_device` = ".$_DEVICE['id']);
		
		if(mysql_num_rows($q) > 0)
			{
				$events = array();

				while($event = mysql_fetch_assoc($q))
					{
						$events[] = array($event['action'], $event['time']);
					}


				// определяем первое событие
				$first_event = $events[0][0];

				// Если первое событие - включение, то ничего не делаем, если выключение - фиксируем включение в 00:00:00 1 января 1970 года. Третий аргумент для пометки фейкового включения 
				if($first_event == 0)
					{
						$events[] = array(1, 0, true);
					}


				// Определяем последнее событие
				$c_events = count($events);
				$last_event = $events[$c_events - 1][0];

				// Если последнее событие - выключение, то ничего не делаем, если включение - фиксируем выключение сегодня в 23:59:59. Третий аргумент для пометки фейкового выключения
				if($last_event == 1)
					{
						$events[] = array(0, $day_end, true);
					}

				// считаем сумму и включения-выключения
				$sum_off = 0;
				$c_off = 0;
				$sum_on = 0;
				$c_on = 0;
				
				foreach($events as $event)
					{
						if($event[0] == 0)
							{
								$sum_off += $event[1];
								// фикс того, будто мы считаем включение в 00:00:00, чтобы не было расхождения total счетчика
								if(!isset($event[2]))
									{
										$c_off++;
									}
							}
						else
							{
								$sum_on += $event[1];
								// фикс того, будто мы считаем выключение в 23:59:59, чтобы не было расхождения total счетчика
								if(!isset($event[2]))
									{
										$c_on++;
									}
							}
					}

				$uptime = $sum_off - $sum_on;
			}
		else
			{
				$uptime = 0;
			}
		
		# высчитываем аптайм
		
		$string = showTimeInterval($uptime);
		
		echo '<a data-toggle="collapse" href="#collapse_'.$_DEVICE['id'].'" role="button" aria-expanded="false" aria-controls="collapseExample" class="badge badge-light">За всё время</a>
		
		<div class="collapse" id="collapse_'.$_DEVICE['id'].'" style="margin-top: 5px;">
			<div class="alert alert-light" style="font-size: small;">';
				
				echo !empty($string) ? '<span class="badge badge-info">'.$string : '<span class="badge badge-warning">Не использовалось';
				echo '</span><br />

				<span class="badge badge-pill badge-light">'.$c_on.' вкл.</span> / <span class="badge badge-pill badge-dark">'.$c_off.' выкл</span>
			</div>
		</div>
		
		<a href="events.php?id_device='.$_DEVICE['id'].'" class="badge badge-light">События</a>
		<a href="device.php?id_device='.$_DEVICE['id'].'&action=view" class="badge badge-light">Подробно</a>
		
		</div>
		';
	}
echo '</div>';

getFooter();