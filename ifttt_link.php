<?php

include_once 'core.php';
autOnly();

if(isset($_GET['short_name']) && isset($_GET['action']))
	{
		$short_name = mysql_real_escape_string($_GET['short_name']);
		$action = $_GET['action'] == 'on' ? 'on' : 'off';
		
		switchRelay($short_name, $action, ' (с сайта)', true);
		header('Location: index.php?'.$short_name.'_turned_'.$action.'=1&');
	}



/* LEGACY 
if(isset($_GET['id_device']) && isset($_GET['action']))
	{
		$unixtime = time();
		$strtime = date('d.m.Y H:i:s', $unixtime);
		switch($_GET['action'])
			{
				case 'on':		$action = 'on';		$act = 1;		$ru_action = 'вкл.';	break;
				case 'off':		$action = 'off';	$act = 0;		$ru_action = 'выкл.';	break;
				
				default: $error = 'wrong_action'; break;
			}
		
		$id_device = (int)$_GET['id_device'];
		
		$q_device = mysql_query("SELECT * FROM `ewelink_devices` WHERE `id` = ".$id_device);
		
		// собираем базу комнат
		$q_rooms = mysql_query("SELECT * FROM `rooms`");
		$_ROOMS = array();

		while($_tmp = mysql_fetch_assoc($q_rooms))
			{
				$_ROOMS[$_tmp['id']] = $_tmp['name'];
			}
		
		if(mysql_num_rows($q_device) != 1)
			{
				$error = 'null_answer';
			}
		
		if(!empty($error))
			{
				header('Location: index.php?error='.$error);
				exit;
			}
		
		$_DEVICE = mysql_fetch_assoc($q_device);
		
		
		
		#1) Делаем запрос к IFTTT
		#2) Записываем событие в свою БД
		#3) Шлем сообщение в Telegram
		#4) Редиректим назад
		
		
		
		
		// запрос к ifttt
		
		$link = 'https://maker.ifttt.com/trigger/ewelink_'.$_DEVICE['short_name'].'_'.$action.'/with/key/'.$_IFTTT['key'];
		
		// die($link);
		
		$ch = curl_init($link);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'PHPscript for Smarthome by D13410N3/1.0');
		
		$a = curl_exec($ch);
		
		// echo $a;
		
		// запись в БД
		
		mysql_query("INSERT INTO `ewelink_events`(`id_device`, `time`, `action`) VALUES (".$_DEVICE['id'].", ".$unixtime.", ".$act.")");
		
		// работа с tg
		
		$message = '<b>'.$_DEVICE['full_name'].'</b> | '.$_ROOMS[$_DEVICE['id_room']].' <i>('.$strtime.')</i>'.PHP_EOL;
		
		
		// определяем свет или просто подача питания
		if($_DEVICE['type'] == 'light')
			{
				$message .= 'свет ';
			}
		else
			{
				$message .= 'устройство ';
			}
		
		$message .= '<i>'.$ru_action.'</i> (с сайта)';
		
		if($act == 0)
			{
				$q_last = mysql_query("SELECT * FROM `ewelink_events` WHERE `id_device` = ".$_DEVICE['id']." AND `action` = 1 ORDER BY `id` DESC LIMIT 1");
				
				// die(mysql_error());
				
				if(mysql_num_rows($q_last) == 0)
					{
						$string_duration = 'Не найдено время включения';
					}
				else
					{
						$last_session = mysql_fetch_assoc($q_last);
						
						$duration = $unixtime - $last_session['time'];
						
						$days = floor($duration / 86400);
						$hours = floor(($duration - $days * 86400) / 3600);
						$minutes = floor(($duration - $days * 86400 - $hours * 3600) / 60);
						$seconds = $duration - $days * 86400 - $hours * 3600 - $minutes * 60;
						
						$string_duration = 'Продолжительность сессии: ';
						
						if($days > 0)
							{
								$string_duration .= $days.' дн. ';
							}
						
						if($hours > 0)
							{
								$string_duration .= $hours.' ч. ';
							}
						
						if($minutes > 0)
							{
								$string_duration .= $minutes.' мин. ';
							}
						
						if($seconds > 0)
							{
								$string_duration .= $seconds.' сек.';
							}
						
					}
				
				// контактен....ция строк 
				
				$message .= PHP_EOL;
				$message .= $string_duration;
				
			}
		sendMessage($_CHAT['id'], $message, 'HTML');
		
		
		// редиректим взад
		
		header('Location: index.php?turned_'.$action.'=1&');
		exit;
	}
*/