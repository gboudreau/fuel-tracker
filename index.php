<?php
include('config.inc.php');

$db = mysql_connect($config->db_host, $config->db_username, $config->db_password) or die ("Can't connect to DB.");
mysql_select_db($config->db_name) or die ("Can't select DB.");

if (isset($_GET['cron'])) {
	$results = mysql_query("SELECT data.car_id, SUM(distance_since_last_entry) AS km, next_service.km AS next_service_km, cars.owner, CONCAT_WS(' ', cars.make, cars.model, cars.year) car FROM data JOIN next_service ON (data.car_id = next_service.car_id) JOIN cars ON (data.car_id = cars.id) GROUP BY data.car_id") or die("Can't select data for cron.");
	while($row = mysql_fetch_object($results)) {
		if ($row->km > $row->next_service_km) {
			mail("$row->owner", "Service is due on $row->car", "This is an automated message from $config->url\n\nService is due on your $row->car.\n\nNext Service KM: $row->next_service_km\nCurrent mileage: $row->km\n");
		}
	}
	exit(0);
}

if (isset($_POST['submit'])) {
	$query = sprintf("INSERT INTO data (car_id, distance_since_last_entry, price_per_liter, liters, date) VALUES ('%s', '%f', '%f', '%f', '%s')",
		mysql_escape_string($_POST['car_id']),
		mysql_escape_string($_POST['km']),
		mysql_escape_string($_POST['price']),
		mysql_escape_string($_POST['liters']),
		mysql_escape_string($_POST['date'])
	);
	error_log($query);
	mysql_query($query);
}

$cars = array(1, 2);
$datas = array();
foreach ($cars as $car_id) {
	$results = mysql_query("SELECT date, fuel_consumption, distance_since_last_entry, liters, price_per_liter FROM data WHERE car_id = '$car_id' ORDER BY date") or die("Can't select data.");

	while($row = mysql_fetch_object($results)) {
		$datas[$car_id][] = (object) array('date' => $row->date, 'fuel_consumption' => (float) $row->fuel_consumption, 'km' => (float) $row->distance_since_last_entry, 'liters' => (float) $row->liters, 'price' => (float) $row->price_per_liter);
	}
}


$one_year_ago = 'new Date('.date("Y", strtotime("-1 year")).', '.(date("m", strtotime("-1 year"))-1).', '.date("d", strtotime("-1 year")).')';
?>
<html>
  <head>
	<title>Fuel consumption tracker</title>
    <script type='text/javascript' src='http://www.google.com/jsapi'></script>
    <script type='text/javascript'>
    google.load('visualization', '1', {'packages':['annotatedtimeline']});
    google.setOnLoadCallback(drawChart);
    function drawChart() {
		<?php foreach ($cars as $car_id): ?>
	        var data = new google.visualization.DataTable();
	        data.addColumn('date', 'Date');
	        data.addColumn('number', 'Fuel Consumption');
	        data.addColumn('string', 'title1');
	        data.addColumn('string', 'text1');
	        data.addRows(<?php echo count($datas[$car_id]) ?>);
			<?php
			$i = 0;
			foreach ($datas[$car_id] as $data) {
				if ($data->liters == 0 || $data->fuel_consumption == 0 || $data->price == 0) { continue; }
				echo "data.setValue($i, 0, new Date(" . substr($data->date, 0, 4) . "," . ((int) substr($data->date, 5, 2)-1) . "," . (int) substr($data->date, 8, 2) . "));\n";
		        echo "data.setValue($i, 1, $data->fuel_consumption);\n";
		        echo "data.setValue($i, 2, '$data->fuel_consumption l/100km');\n";
		        echo "data.setValue($i, 3, '$data->km km with $data->liters L paid ".($data->price*100.0)." &#162;/L');\n";
				$i++;
			}
			?>
	        var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div_<?php echo $car_id ?>'));
	        chart.draw(data, {displayAnnotations: true, allowHtml:true, annotationsWidth: 35, thickness: 2, zoomStartTime: <?php echo $one_year_ago ?>});
		<?php endforeach; ?>
    }
    </script>
  </head>

  <body>
	<?php foreach ($cars as $car_id): ?>
		<h2><?php
		$query = "SELECT * from cars WHERE id = $car_id";
		$result = mysql_query($query);
		$result = mysql_fetch_object($result);
		echo "$result->make $result->model $result->year";
		?></h2>
		<form method="post">
			New entry
			<table>
			<tr><td>Date</td><td><input name="date" type="text" value="<?php echo date("Y-m-d", time()) ?>" size="10" /></td></tr>
			<tr><td>km</td><td><input name="km" type="text" value="distance" onfocus="if(this.value=='distance'){this.value='';}" size="8" /></td></tr>
			<tr><td>$/L</td><td><input name="price" type="text" value="price" onfocus="if(this.value=='price'){this.value='';}" size="5" /></td></tr>
			<tr><td>L</td><td><input name="liters" type="text" value="quantity" onfocus="if(this.value=='quantity'){this.value='';}" size="8" /></td></tr>
			<tr><td>&nbsp;</td><td><input name="car_id" type="hidden" value="<?php echo $car_id ?>" />
			<input name="submit" type="submit" value="Save" /></td></tr>
			</table>
		</form>
    	<div id="chart_div_<?php echo $car_id ?>" style="width: 710px; height: 240px;">Loading...</div>
		<div id="stats_<?php echo $car_id ?>">
			<br/>
			Average fuel consumption: <strong>
			<?php
			$total = 0;
			$km = 0;
			$paid_total = 0;
			$liters_total = 0;
			foreach ($datas[$car_id] as $data) {
				$km += $data->km;
				if ($data->liters == 0 || $data->fuel_consumption == 0 || $data->price == 0) { continue; }
				$total += $data->fuel_consumption;
				$paid_total += $data->price * $data->liters;
				$liters_total += $data->liters;
			}
			echo round($total/count($datas[$car_id]), 2);
			?> L/100km</strong>

			<br/>
			Mileage: <strong><?php echo round($km) ?> km</strong>

			<br/>
			Gaz: <strong>$<?php echo round($paid_total) ?></strong> for 
			<strong><?php echo round($liters_total) ?> L</strong> (<?php echo round(($paid_total*100/$liters_total), 1) ?> &#162;/L avg)
		</div>
		<hr/>
	<?php endforeach; ?>
  </body>
</html>
