<?php
/*
Plugin Name: CustomQuery
Plugin URI: http://meandmymac.net/plugins/
Description: Database queries for the rest of us...
Author: Arnan de Gans
Version: 0.1
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
	add_submenu_page('plugins.php', 'Database - CustomQuery', 'Database', 10, 'customquery2', 'cq_database');
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
function cq_database() {
	global $wpdb;
	
	$tablename = $_GET['cq_table'];
	$pluginurl = get_option('home')."/wp-admin/plugins.php?page";
?>
	<div class="wrap">
	  	<h2>CustomQuery - Database</h2>

    	<table class="form-table">
			<tr valign="top">
				<td colspan="4" bgcolor="#DDD">This page lists all available database tables and allows you to review them in an orderly manner.</td>
			</tr>
			<tr valign="top">
				<th scope="row">Tables:</th>
		        <td>
	<?php
	$tables = mysql_query("SHOW TABLES FROM ".DB_NAME);
	
	if (!$tables) {
		echo "<tr><td colspan=\"6\"><span style=\"color:#f00; font-weight:bold;\">Could not list tables:<br />" . mysql_error() . "</span></td></tr>";
	}
	
	while ($table = mysql_fetch_row($tables)) {
		if($tablename != $table[0]) {
	    	echo "<a href=\"$pluginurl=customquery2&cq_table=$table[0]\">$table[0]</a> ";
		} else {
			echo "$table[0] ";
		}
	}
	
	mysql_free_result($tables);
	?>
				</td>
			</tr>
		</table>
	<?php 
	if(!empty($tablename)) { 
	?>
	  	<table class="form-table">
			<tr valign="top">
				<td colspan="6" bgcolor="#DDD"><strong>You are currently viewing: <?php echo $tablename; ?></strong></td>
			</tr>
			<tr>
				<td bgcolor="#EEE">Field name</td>
				<td bgcolor="#EEE">Data type</td>
				<td width="7%" bgcolor="#EEE">Null</td>
				<td width="7%" bgcolor="#EEE">Key</td>
				<td width="15%" bgcolor="#EEE">Default</td>
				<td width="15%" bgcolor="#EEE">Extra</td>
			</tr>
	<?php
		$fields = mysql_query("SHOW COLUMNS FROM $tablename");
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
		        </td>
			</tr>
			<tr>
				<td bgcolor="#EEE">Field name</td>
				<td bgcolor="#EEE">Data type</td>
				<td bgcolor="#EEE">Null</td>
				<td bgcolor="#EEE">Key</td>
				<td bgcolor="#EEE">Default</td>
				<td bgcolor="#EEE">Extra</td>
			</tr>

<?php
		}
	}
	?>
		</table>
	</div>
<?php
}
?>