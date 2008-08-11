<?php
/*
Plugin Name: CustomQuery
Plugin URI: http://meandmymac.net/plugins/
Description: MySQL for the rest of us...
Author: Arnan de Gans
Version: 0.2
Author URI: http://meandmymac.net
*/ 

#---------------------------------------------------
# Load plugin and values
#---------------------------------------------------
add_action('admin_menu', 'cq_menu_pages');

/*-------------------------------------------------------------
 Name:      cq_menu_pages

 Purpose:   Dashboard pages
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_menu_pages() {
	add_submenu_page('plugins.php', 'Query - CustomQuery', 'Query', 10, 'customquery', 'cq_query');
	add_submenu_page('plugins.php', 'Tables - CustomQuery', 'Tables', 10, 'customquery2', 'cq_tables');
}

/*-------------------------------------------------------------
 Name:      cq_query

 Purpose:   Admin options page
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_query() {
	global $wpdb;
	
	if(isset($_POST['cq_submit_query'])) { // query submitted
		$cq_query = stripslashes(trim($_POST['cq_query'], "\t\n "));
		$buffer = explode(' ', $cq_query, 2);
		
		$allowed = array("INSERT", "UPDATE", "DELETE", "ALTER", "DROP", "CREATE");
		if(empty($cq_query)) { // empty query 
			echo "<div id=\"message\" class=\"updated fade\"><p>Query cannot be <strong>Empty</strong></p></div>";			
		} else if(in_array(strtoupper($buffer[0]), $allowed)) { // proceed
			if($wpdb->query($cq_query) !== FALSE) { // success
				echo "<div id=\"message\" class=\"updated fade\"><p>Query <strong>Successful</strong><br/><br/><strong>Query:</strong> $cq_query</p></div>";
			} else { // failure
				echo "<div id=\"message\" class=\"updated fade\"><p>Query <strong>Failed</strong><br /><br /><strong>Error:</strong> ".str_replace("You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near", "Check your syntax near:", mysql_error())."<br /><br /><strong>Query:</strong> $cq_query</p></div>";
			}
		} else { // illegal query
			echo "<div id=\"message\" class=\"updated fade\"><p>Query <strong>Failed</strong><br /><br/><strong>'$buffer[0]'</strong> is not allowed in this plugin!<br /><br/><strong>Query:</strong> $cq_query</p></div>";
		}
	}
?>
	<div class="wrap">
	  	<h2>CustomQuery - Query</h2>
	  	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'];?>">
	    	<input type="hidden" name="cq_submit_query" value="true" />

	    	<table class="form-table">
			<tr valign="top">
				<td colspan="4" bgcolor="#DDD"><span style="color:#f00; font-weight:bold;">WARNING: Use this page with caution! If you do not know what to do, don't use it!<br />
				THIS CAN AND POSSIBLY WILL BREAK YOUR WEBSITE IF YOU DO IT WRONG! <u>ALWAYS MAKE A BACKUP!</u></span></td>
			</tr>
			<tr valign="top">
				<th scope="row">Query:</th>
		        <td><textarea name="cq_query" type="text" cols="70" rows="12"></textarea><br /><em>Use this field for insert/update/delete/alter/drop queries only!</em></td>
			</tr>
			</table>
			<p class="submit">
				<input type="submit" name="Submit" value="Run Query" onclick="return confirm('You are about to run a query on your database!\nMake absolutely sure the query is correct!\n')"/> <em>Click only ONCE, some queries might take a few moments or so to process!</em>
			</p>
		</form>
	</div>
<?php
}

/*-------------------------------------------------------------
 Name:      cq_database

 Purpose:   Review the database
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_tables() {
	global $wpdb;
	
	$tablename = $_GET['cq_table'];
	$optimize = $_GET['cq_optimize'];
	$optimize_all = $_GET['cq_optimize_all'];
	$drop = $_GET['cq_drop'];
	$truncate = $_GET['cq_truncate'];
	$pluginurl = get_option('home')."/wp-admin/plugins.php?page";
	
	if(strlen($optimize) != 0) {  
		if($wpdb->query("OPTIMIZE TABLE `$optimize`") !== FALSE) { // success
			echo "<div id=\"message\" class=\"updated fade\"><p>Table <strong>optimized</strong></p></div>";
		} else { // failure
			echo "<div id=\"message\" class=\"updated fade\"><p>Table optimization <strong>Failed</strong><br /><br /><strong>Error:</strong> ".str_replace("You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near", "Check your syntax near:", mysql_error())."</p></div>";
		}
	}
	if(strlen($optimize_all) != 0) {
		$result = mysql_query("SHOW TABLES FROM ".DB_NAME) or die('Cannot get tables');  
		while($table = mysql_fetch_row($result)) {  
			mysql_query('OPTIMIZE TABLE '.$table[0]) or die('Cannot optimize '.$table[0]);  
		}  
		echo "<div id=\"message\" class=\"updated fade\"><p>Database <strong>optimized</strong></p></div>";
	}  
	if(strlen($drop) != 0) {  
		if($wpdb->query("DROP TABLE `$drop`") !== FALSE) { // success
			echo "<div id=\"message\" class=\"updated fade\"><p>Table <strong>deleted</strong></p></div>";
		} else { // failure
			echo "<div id=\"message\" class=\"updated fade\"><p>Table deletion <strong>Failed</strong><br /><br /><strong>Error:</strong> ".str_replace("You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near", "Check your syntax near:", mysql_error())."</p></div>";
		}
	}
	if(strlen($truncate) != 0) {  
		if($wpdb->query("TRUNCATE `$truncate`") !== FALSE) { // success
			echo "<div id=\"message\" class=\"updated fade\"><p>Table <strong>emptied</strong></p></div>";
		} else { // failure
			echo "<div id=\"message\" class=\"updated fade\"><p>Emptying table <strong>Failed</strong><br /><br /><strong>Error:</strong> ".str_replace("You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near", "Check your syntax near:", mysql_error())."</p></div>";
		}
	}
	?>
	<div class="wrap">
	  	<h2>CustomQuery - Tables</h2>
	  	
	  	<p>Here you can perform some simple tasks on your database and review the table scructures</p>

  		<form method="post" action="<?php echo $_SERVER['REQUEST_URI'];?>">
		  	<table class="widefat">
		  		<thead>
					<tr valign="top">
						<td><?php if(!empty($tablename)) { ?>Currently browsing: <?php } else { ?>Select a table!<?php } ?></td>
						<td><?php if(!empty($tablename)) { echo $tablename; } ?></td>
						<td width="7%">&nbsp;</td>
						<td width="7%">&nbsp;</td>
						<td width="15%">&nbsp;</td>
						<td width="15%">&nbsp;</td>
					</tr>
				</thead>
	 	  		<tbody>
	<?php
	$tables = mysql_query("SHOW TABLES FROM ".DB_NAME);
	
	if (!$tables) {
		echo "<tr><td colspan=\"6\"><span style=\"color:#f00; font-weight:bold;\">Could not list tables:<br />" . mysql_error() . "</span></td></tr>";
	}
	
	echo "<tr><td colspan=\"6\">";	
	while ($table = mysql_fetch_row($tables)) {
		if($tablename != $table[0]) {
	    	echo "<a href=\"$pluginurl=customquery2&cq_table=$table[0]\">$table[0]</a> ";
		} else {
			echo "<strong>$table[0]</strong> ";
		}
	}
	echo "</td></tr>";
	
	if(empty($tablename)) { 
	echo "<tr><td colspan=\"6\"><strong>Options:</strong> ";
	echo "<a href=\"$pluginurl=customquery2&amp;cq_optimize_all=yes\" style=\"color: #f00;\" onclick=\"return confirm('Are you sure you want to OPTIMIZE ALL tables?')\">Optimize database</a>";
	echo "</td></tr>";
	}
	
	if(!empty($tablename)) { 
	?>
					<tr>
						<td bgcolor="#EEE">Field name</td>
						<td bgcolor="#EEE">Data type</td>
						<td bgcolor="#EEE">Null</td>
						<td bgcolor="#EEE">Key</td>
						<td bgcolor="#EEE">Default</td>
						<td bgcolor="#EEE">Extra</td>
					</tr>

	<?php
		$fields = mysql_query("SHOW COLUMNS FROM `$tablename`");
		if (!$fields) {
		    echo "<tr><td colspan=\"6\"><span style=\"color:#f00; font-weight:bold;\">Could not run query:<br />" . mysql_error() . "</span></td></tr>";
		} else {
			if (mysql_num_rows($fields) > 0) {
			    while ($field = mysql_fetch_assoc($fields)) {
			        echo "<tr>";
			        echo "<td><strong>$field[Field]</strong></td>";
			        echo "<td>$field[Type]</td>";
			        echo "<td>$field[Null]</td>";
			        echo "<td>$field[Key]</td>";
			        echo "<td>$field[Default]</td>";
			        echo "<td>$field[Extra]</td>";
			        echo "</tr>";
			    }
			}
?>
				<tr>
					<td bgcolor="#EEE">Field name</td>
					<td bgcolor="#EEE">Data type</td>
					<td bgcolor="#EEE">Null</td>
					<td bgcolor="#EEE">Key</td>
					<td bgcolor="#EEE">Default</td>
					<td bgcolor="#EEE">Extra</td>
			    </tr>
			    <tr>
			        <td colspan="6"><strong>Options:</strong> 
			        <a href="<?php echo $pluginurl;?>=customquery2&amp;cq_drop=<?php echo $tablename;?>" style="color: #f00;" onclick="return confirm('Are you sure you want to DELETE this table?\nThis action is irreversible!')">Drop</a>, 
					<a href="<?php echo $pluginurl;?>=customquery2&amp;cq_optimize=<?php echo $tablename;?>&cq_table=<?php echo $tablename;?>" style="color: #f00;" onclick="return confirm('Are you sure you want to OPTIMIZE this table?')">Optimize</a>,
					<a href="<?php echo $pluginurl;?>=customquery2&amp;cq_truncate=<?php echo $tablename;?>&cq_table=<?php echo $tablename;?>" style="color: #f00;" onclick="return confirm('Are you sure you want to EMPTY this table?')">Truncate</a>
					</td>
				</tr>
			</tbody>
<?php
		}
	}
	?>
		</table>
	</div>
<?php
}
?>