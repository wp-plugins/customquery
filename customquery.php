<?php
/*
Plugin Name: CustomQuery
Plugin URI: http://meandmymac.net/plugins/
Description: MySQL for the rest of us...
Author: Arnan de Gans
Version: 0.4
Author URI: http://meandmymac.net
*/ 

#---------------------------------------------------
# Load plugin and values
#---------------------------------------------------
add_action('admin_menu', 'cq_menu_pages');

if (isset($_POST['submit_action'])) 	add_action('init', 'cq_actions');
if (isset($_POST['submit_query'])) 		add_action('init', 'cq_run_queries');

/*-------------------------------------------------------------
 Name:      cq_menu_pages

 Purpose:   Dashboard pages
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_menu_pages() {
	add_object_page('CustomQuery', 'CustomQuery', 'manage_options', 'customquery', 'cq_tables');
		add_submenu_page('customquery', 'View Tables', 'View Tables', 'edit_plugins', 'customquery', 'cq_tables');
		add_submenu_page('customquery', 'Run A Query', 'Run A Query', 'edit_plugins', 'customquery2', 'cq_query');
}

/*-------------------------------------------------------------
 Name:      cq_run_queries

 Purpose:   Run manual queries entered by user
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_run_queries() {
	global $wpdb;
	
	$querystring = $_POST['querystring'];
	
	if(!empty($querystring)) {	
		$querystring = stripslashes(trim($querystring, "\t\n "));
		$queries = explode(';', $querystring, -1);
		$allowed = array("INSERT", "UPDATE", "DELETE", "ALTER", "DROP", "CREATE");
		$i = 1;
		foreach($queries as $query) {
			$check = explode(' ', $query, 2);
			
			if(in_array(strtoupper($check[0]), $allowed)) {
				if(!empty($query)) {
					$wpdb->query($wpdb->prepare($query)) or die(mysql_error());
				}
			} else {
				wp_redirect('admin.php?page=customquery2&action=illegal&where='.$i);
				exit;
			}
			$i++;
		}
		wp_redirect('admin.php?page=customquery2&action=success');
		exit;
	} else {
		wp_redirect('admin.php?page=customquery2&action=empty');
		exit;
	}
}

/*-------------------------------------------------------------
 Name:      cq_query

 Purpose:   Admin options page
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_query() {

	$action 	= $_GET['action'];
	$where	 	= $_GET['where'];
?>
	<div class="wrap">
	  	<h2>Run Query</h2>
	  	
		<?php 
		if($action == "illegal") {
			echo "<div id=\"message\" class=\"error\"><p>Query <strong>illegal syntax</strong>. There is something wrong with query <strong>".$where."</strong>.</p></div>";
		}			
		if($action == "empty") {
			echo "<div id=\"message\" class=\"error\"><p>Query <strong>cannot be empty</strong></p></div>";
		}			
		if($action == "success") {
			echo "<div id=\"message\" class=\"updated fade\"><p>Query <strong>successfull</strong></p></div>";
		}			
		?>
	  	<form method="post" action="admin.php?page=customquery2">
	    	<input type="hidden" name="submit_query" value="true" />

			<table class="widefat" style="margin-top: .5em">
	  			<thead>
	  				<tr valign="top" id="quicktags">
						<td colspan="2"><span style="color:#f00; font-weight:bold;">WARNING: Use this page with caution! If you do not know what to do, do not use it!<br />
						THIS CAN AND POSSIBLY WILL BREAK YOUR WEBSITE IF YOU DO IT WRONG! <u>ALWAYS MAKE A BACKUP!</u></div></td>
					</tr>
	  			</thead>
	  			<tbody>
					<tr>
						<th scope="row">Query:</th>
				        <td><textarea name="querystring" type="text" cols="70" rows="12"></textarea><br /><em>Use this field for insert/update/delete/alter/drop queries only!<br />You can insert multiple queries ere as long as you close each one with a semicolon.</em></td>
					</tr>
	 			</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="Submit" value="Run Query"/> <em>Click only ONCE, some queries might take a few moments to process!</em>
			</p>
		</form>
	</div>
<?php
}

/*-------------------------------------------------------------
 Name:      cq_actions

 Purpose:   Selected options from dashboard
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_actions() {
	
	$tablename = $_POST['table'];

	$reservedtables = array(
		$wpdb->prefix.'commentmeta', // Added in 2.9
		$wpdb->prefix.'comments', 
		$wpdb->prefix.'links', 
		$wpdb->prefix.'options', 
		$wpdb->prefix.'postmeta', 
		$wpdb->prefix.'posts', 
		$wpdb->prefix.'term_relationships', 
		$wpdb->prefix.'term_taxonomy', 
		$wpdb->prefix.'terms', 
		$wpdb->prefix.'usermeta', 
		$wpdb->prefix.'users'
	);

	if(strlen($tablename) > 0) {
		if(isset($_POST['submit_optimize'])) {
			mysql_query("OPTIMIZE TABLE `$tablename`") or die('Cannot optimize <strong>Error:</strong> '.str_replace('You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near', 'Check your syntax near:', mysql_error()));
			wp_redirect('admin.php?page=customquery&action=optimize&status=ok&table='.$tablename);
			exit;
		}
			
		if(isset($_POST['submit_drop'])) {
			if(!in_array(strtolower($tablename), $reservedtables)) {
				mysql_query("DROP TABLE `$tablename`") or die('Cannot drop table <strong>Error:</strong> '.str_replace('You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near', 'Check your syntax near:', mysql_error()));
				wp_redirect('admin.php?page=customquery&action=drop&status=ok');
				exit;
			} else {
				wp_redirect('admin.php?page=customquery&action=drop&status=wpdenied');
				exit;
			}
		}
			
		if(isset($_POST['submit_truncate'])) {
			if(!in_array(strtolower($tablename), $reservedtables)) {
				mysql_query("TRUNCATE `$tablename`") or die('Cannot truncate table <strong>Error:</strong> '.str_replace('You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near', 'Check your syntax near:', mysql_error()));
				wp_redirect('admin.php?page=customquery&action=truncate&status=ok&table='.$tablename);
				exit;
			} else {
				wp_redirect('admin.php?page=customquery&action=truncate&status=wpdenied&table='.$tablename);
				exit;
			}
		}
	} else {
		if(isset($_POST['submit_optimizeall'])) {
			$result = mysql_query("SHOW TABLES FROM ".DB_NAME) or die('Cannot get tables');  
			while($table = mysql_fetch_row($result)) {  
				mysql_query('OPTIMIZE TABLE '.$table[0]) or die('Cannot optimize '.$table[0].' <strong>Error:</strong> '.str_replace('You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near', 'Check your syntax near:', mysql_error()));  
			}  
			wp_redirect('admin.php?page=customquery&action=optimizeall&status=ok&table='.$tablename);
			exit;
		} else {
			wp_redirect('admin.php?page=customquery&action=unknown&status=fail');
			exit;
		}
	}
}

/*-------------------------------------------------------------
 Name:      cq_database

 Purpose:   Review the database
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function cq_tables() {
	global $wpdb;
	
	$action 	= $_GET['action'];
	$status 	= $_GET['status'];
	$tablename 	= $_GET['table'];
?>
	<div class="wrap">
	  	<h2>View Tables</h2>
<?php
	
	switch($action) {
		case "optimize" :
			echo "<div id=\"message\" class=\"updated fade\"><p>Table <strong>optimized</strong></p></div>";
		break;
		
		case "optimizeall" :
			echo "<div id=\"message\" class=\"updated fade\"><p>Database <strong>optimized</strong></p></div>";		
		break;
		
		case "drop" :
			if($status == 'ok') {
				echo "<div id=\"message\" class=\"updated fade\"><p>Table <strong>deleted</strong></p></div>";
			} 
			if($status == 'wpdenied') {
				echo "<div id=\"error\" class=\"error\"><p>This table belongs to WordPress and cannot be removed</p></div>";
			}
		break;
		
		case "truncate" :
			if($status == 'ok') {
				echo "<div id=\"message\" class=\"updated fade\"><p>Table <strong>emptied</strong></p></div>";
			} 
			if($status == 'wpdenied') {
				echo "<div id=\"error\" class=\"error\"><p>This table belongs to WordPress and cannot be removed</p></div>";
			}
		break;

		case "unknown" :
			if($status == 'fail') {
				echo "<div id=\"message\" class=\"error\"><p>Unknown <strong>error</strong>. A malformed URL or timeout is the likely cause.</p></div>";
			} 
		break;
		
		default :
			if(strlen($tablename) > 1 AND strlen($action) > 1) {
				echo "<div id=\"error\" class=\"error\"><p>Action failed</p></div>";
			}
		break;
	}
	?>
	  	<p>Here you can perform some simple tasks on your database and review the table scructures</p>

  		<form method="post" action="admin.php?page=customquery">
		  	<table class="widefat">
		  		<thead>
					<tr valign="top" id="quicktags">
						<td><?php if(!empty($tablename)) { ?>Currently viewing: <?php } else { ?>Select a table!<?php } ?></td>
						<td><?php if(!empty($tablename)) { echo $tablename; } ?></td>
						<td colspan="5"><?php if(!empty($tablename)) { echo "<a href=\"admin.php?page=customquery\">Back</a>"; } ?></td>
					</tr>
				</thead>
	 	  		<tbody>
	<?php
	$tables = mysql_query("SHOW TABLE STATUS FROM ".DB_NAME);
	
	if (!$tables) {
		echo "<tr><td colspan=\"7\"><span style=\"color:#f00; font-weight:bold;\">Could not list tables:<br />" . mysql_error() . "</span></td></tr>";
	}
	
	if(empty($tablename)) { 
	?>
					<tr>
						<th>Table name</th>
						<th width="5%">Records</th>
						<th width="10%">Size</th>
						<th width="5%">Overhead</th>
						<th width="10%">Type</th>
						<th width="15%">Created on</th>
						<th width="10%">Collation</th>
					</tr>
	<?php
		while ($table = mysql_fetch_array($tables)) {
		$tableData_length = round($table[Data_length]/1024, 2);
		$tableData_free = round($table[Data_free]/1024, 2);
		echo "<tr>";
	    	echo "<td><a href=\"admin.php?page=customquery&table=$table[Name]\">$table[Name]</a></td>";
	    	echo "<td>$table[Rows]</td>";
	    	echo "<td>$tableData_length Kb</td>";
	    	echo "<td>$tableData_free Kb</td>";
	    	echo "<td>$table[Engine]</td>";
	    	echo "<td>$table[Create_time]</td>";
	    	echo "<td>$table[Collation]</td>";
		echo "</tr>";
		}
		
?>
					<tr>
						<th>Table name</th>
						<th width="5%">Records</th>
						<th width="10%">Size</th>
						<th width="5%">Overhead</th>
						<th width="10%">Type</th>
						<th width="15%">Created on</th>
						<th width="10%">Collation</th>
					</tr>
		<tr><td colspan=\"7\"><strong>Database options:</strong>
		<form id="post" method="post" action="admin.php?page=customquery">
	  	   	<input type="hidden" name="submit_action" value="true" />
			<input type="submit" name="submit_optimizeall" class="button-secondary" value="Optimize database" onclick="return confirm('Are you sure you want to OPTIMIZE this database?')" />
		
		</form>
		</td></tr>
<?php	
	}
	
	if(!empty($tablename)) { 
	?>
					<tr>
						<th>Field name</th>
						<th>Data type</th>
						<th>Null</th>
						<th>Key</th>
						<th>Default</th>
						<th>Extra</th>
					</tr>

	<?php
		$fields = mysql_query("SHOW COLUMNS FROM `$tablename`");
		if (!$fields) {
		    echo "<tr><td colspan=\"6\"><span style=\"color:#f00; font-weight:bold;\">Could not run query:<br />" . mysql_error() . "</span></td></tr>";
		} else {
			if (mysql_num_rows($fields) > 0) {
			    while ($field = mysql_fetch_assoc($fields)) {
			        echo "<tr>";
			        echo "<td>$field[Field]</td>";
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
					<th>Field name</th>
					<th>Data type</th>
					<th>Null</th>
					<th>Key</th>
					<th>Default</th>
					<th>Extra</th>
				</tr>
			    <tr>
			        <td colspan="6"><strong>Table options:</strong> 
			        	<form id="post" method="post" action="admin.php?page=customquery">
					  	   	<input type="hidden" name="submit_action" value="true" />
					  	   	<input type="hidden" name="table" value="<?php echo $tablename;?>" />
							<input type="submit" name="submit_drop" class="button-secondary" value="Drop" onclick="return confirm('Are you sure you want to DELETE this table?')" />
							<input type="submit" name="submit_optimize" class="button-secondary" value="Optimize" onclick="return confirm('Are you sure you want to OPTIMIZE this table?')" />
							<input type="submit" name="submit_truncate" class="button-secondary" value="Truncate" onclick="return confirm('Are you sure you want to EMPTY this table?')" />
						
						</form>
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