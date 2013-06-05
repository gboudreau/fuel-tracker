#!/usr/bin/php
<?php
include('config.inc.php');

$sender = trim($argv[1]);
$recipient = trim($argv[2]);

$raw_message = get_body();

// If you only have one car, you can skip all this.
// But if you have multiple cars, you need some way to identify which cars the incoming email refers to.
if (message_contains('corolla')) {
	$owner = 'wife@something.com'; // This email address needs to be the same as the email address found in your database, in the cars table.
} else 	if (message_contains('highlander')) {
	$owner = 'guillaume@pommepause.com';
}
if (!isset($owner)) {
	die("Unknown sender: $sender");
}

if (!eregi("[ \n\r\t]*([^-][0-9\\,\\.]+)[ \n\r\t]*k?m?[ \n\r\t]+\\\$?[ \n\r\t]*([0-9\\,\\.]+)[ \n\r\t]*\\\$?[ \n\r\t]+([0-9\\,\\.]+[^:])[ \n\r\t]*l?", $raw_message, $regs)
	&& !eregi("[ \n\r\t]*([^-][0-9\\,\\.]+)[ \n\r\t]*k?m?[ \n\r\t]*<[/div]+>[ \n\r\t]*\\\$?[ \n\r\t]*([0-9\\,\\.]+)[ \n\r\t]*\\\$?[ \n\r\t]*<[/div]+>[ \n\r\t]*([0-9\\,\\.]+[^:])[ \n\r\t]*l?", $raw_message, $regs)) {
	file_put_contents('/tmp/temp.txt', "Sender: $sender, Recipient: $recipient, Can't find KPL in message body: " . $raw_message);
	die("Can't find KPL in message body.");
} else {
	$km = str_replace(',', '.', $regs[1]);
	$price = str_replace(',', '.', $regs[2]);
	$liters = str_replace(',', '.', $regs[3]);
	file_put_contents('/tmp/fuel_temp.txt', var_export($regs, TRUE) . "found in message body: " . $raw_message . ", with subject: " . $subject);

	$db = mysql_connect($config->db_host, $config->db_username, $config->db_password) or die ("Can't connect to DB.");
	mysql_select_db($config->db_name) or die ("Can't select DB.");

	$query = sprintf("SELECT * FROM cars WHERE owner = '%s'",
		mysql_escape_string($owner)
	);
	$results = mysql_query($query) or die("Can't select car.");
	if ($car = mysql_fetch_object($results)) {
		$query = sprintf("INSERT INTO data (car_id, distance_since_last_entry, price_per_liter, liters, date) VALUES ('%s', '%f', '%f', '%f', '%s')",
			mysql_escape_string($car->id),
			mysql_escape_string($km),
			mysql_escape_string($price),
			mysql_escape_string($liters),
			mysql_escape_string(date('Y-m-d', time()))
		);
		mysql_query($query) or die("Can't insert new data.");
	} else {
		die("No car found for owner '$owner'.");
	}
}

function get_body() {
	$msg = '';
	$fd = fopen('php://stdin', 'r');
	while (!feof($fd)) {
		$text = fread($fd, 1024);
		$msg .= $text;
	}
	fclose($fd);
	
	$clean_msg = '';
	$is_header = FALSE;
	$headers = array("Date", "DomainKey-Signature", "Content-Transfer-Encoding", "Subject", "X-Mailer", "Content-Type", "To", "From", "Message-Id", "Received", "DKIM-Signature", "References", "In-Reply-To", "MIME-Version");
	foreach (explode("\n", $msg) as $line) {
		if ($is_header && eregi("^ [ ]+[a-z0-9]+", $line)) {
			continue;
		}
		$is_header = FALSE;
		foreach ($headers as $header) {
			if (eregi("^[ <\t]*".$header.": ", $line)) {
				$is_header = TRUE;
				#$clean_msg .= "[Removing $line]\n";
				if (stripos($line, 'Subject:') !== FALSE) {
					global $subject;
					$subject = $line;
				}
				break;
			}
		}
		if (!$is_header && !eregi('^--[0-9a-f]+-?-?$', $line)) {
			$clean_msg .= $line . "\n";
		}
	}
	return $clean_msg;
}

function sender_contains($what) {
	global $sender;
	return stripos($sender, $what) !== FALSE;
}

function message_contains($what) {
	global $raw_message, $subject;
	return stripos($raw_message, $what) !== FALSE || stripos($subject, $what) !== FALSE;
}
