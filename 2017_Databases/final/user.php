<!DOCTYPE html>
<html>
	<head>
		<title>Customer</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<style>
			table, td, th {
				border: 1px solid black;
			}
			th, td {
				padding: 5px;
				text-align: center;
			}
		</style>
	</head>
	<body>
		<div class="col-sm-3 col-md-6 col-lg-4">
			<h3>Customer actions</h3>
				<h4>New customer sign up</h4>
				<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
					<b>First name</b><br>
					<input type="text" name="firstName" required><br>
					<b>Last name</b><br>
					<input type="text" name="lastName" required><br>
					<b>Customer e-signature 
						<br>(used when confirming actions)</b><br>
					<input type="text" name="esign" required><br>
					<input type="submit" name="submit" value="Sign up">
				</form>
<br>
				<h4>Show my order history</h4>
				<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
					<b>First name</b><br>
					<input type="text" name="firstName" required><br>
					<b>Last name</b><br>
					<input type="text" name="lastName" required><br>
					<b>Customer e-signature</b><br>
					<input type="text" name="esign" required><br>
					<input type="submit" name="submit2" value="Show">
				</form>
<br>
				<h4>What I have ordered so far</h4>
				<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
					<b>First name</b><br>
					<input type="text" name="firstName" required><br>
					<b>Last name</b><br>
					<input type="text" name="lastName" required><br>
					<b>Customer e-signature</b><br>
					<input type="text" name="esign" required><br>
					<input type="submit" name="submit3" value="Submit">
				</form>
<br>
				<h4>Modify prohibited foods</h4>
				<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
					<b>First name</b><br>
					<input type="text" name="firstName" required><br>
					<b>Last name</b><br>
					<input type="text" name="lastName" required><br>
					<b>Customer e-signature</b><br>
					<input type="text" name="esign" required><br>
					<b>Action</b><br>
					<select name="action">
						<option value="add">Add</option>
						<option value="delete">Delete</option>
					</select><br>
					<b>Ingredient (origin)</b><br>
					<select name="ingredient">
<?php
$host = 'mpcs53001.cs.uchicago.edu';
$username = '';
$password = '';
$database = $username.'DB';

// Connect to db
$conn = mysqli_connect($host, $username, $password, $database)
	or die('Could not connect ' . mysqli_connect_error());

$query = "SELECT ingredientID, ingredientName, CASE WHEN origin IS NULL THEN 'N/A'
                								ELSE origin
                								END AS origin
			FROM Ingredient";

$result = mysqli_query($conn, $query)
	or die('Find ingredient failed ' . mysqli_error());

while ($tuple = mysqli_fetch_row($result)) {
	print "<option value=\"$tuple[0]\">$tuple[1] ($tuple[2])</option>";
}

// Free result
mysqli_free_result($result);

// Close connection
mysqli_close($conn);
?>
					</select><br>
					<input type="submit" name="submit4" value="Modify">
				</form>
			</div>
			<div class="col-sm-9 col-md-6 col-lg-8">
				<h3>Result</h3>
					

<?php
function input_sanitization($data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}

$host = 'mpcs53001.cs.uchicago.edu';
$username = '';
$password = '';
$database = $username.'DB';

// Connect to db
$conn = mysqli_connect($host, $username, $password, $database)
	or die('Could not connect ' . mysqli_connect_error());

// Input parameters
$firstname = input_sanitization($_REQUEST['firstName']);
$lastname = input_sanitization($_REQUEST['lastName']);
$esign = input_sanitization($_REQUEST['esign']);

if(isset($_REQUEST['submit'])) {
	// Check if there's an existing account in the database
	$query = "SELECT *
				FROM Users
				WHERE firstName = '$firstname'
				AND lastName = '$lastname'
				AND esign = '$esign'";

	$result = mysqli_query($conn, $query)
		or die('Check user account failed ' . mysqli_error());

	if (mysqli_num_rows($result) !== 0)
		print '<h3>You\'ve already registered!</h3>';
	// create a new user account
	else {
		$query2 = "INSERT INTO Users
					VALUES
					(null, '$firstname', '$lastname', '$esign')";
		$result2 = mysqli_query($conn, $query2)
			or die('Create user account failed ' . mysqli_error());
		print '<h3>You\'re all set!</h3>';

		mysqli_free_result($result2);
	}

	// Free result
	mysqli_free_result($result);
	

}
else if (isset($_REQUEST['submit2'])) {
	// List past orders in the database
	$query = "SELECT orderID, orderDate, ROUND(total, 2), status 
				FROM Orders 
				JOIN Users 
				USING(userID) 
				WHERE firstName = '$firstname' 
				AND lastName = '$lastname'
				AND esign = '$esign'";
	
	$result = mysqli_query($conn, $query)
		or die('Show orders failed ' . mysqli_error());

	if (mysqli_num_rows($result) === 0)
		print '<h3>No result found!</h3>';
	// Print result in html
	else {
		print "<table>
				<thread>
					<tr>
						<th>Order ID</th>
						<th>Order Date</th>
						<th>Total Amount</th>
						<th>Order Status</th>
					</tr>
				</thread>
			<tbody>";
		while ($tuple = mysqli_fetch_row($result)) {
			print '<tr>';
			print "<td align=\"center\" valign=\"center\">$tuple[0]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[1]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[2]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[3]</td>";
			print '</tr>';
		}

		print "</tbody>
			</table>";
	}

	// Free result
	mysqli_free_result($result);
}
else if(isset($_REQUEST['submit3'])) {
	// List past menuItems in the database
	$query = "SELECT DISTINCT menuItemID, menuItemName, price, people
				FROM Users
				JOIN Orders 
				USING(userID)
				JOIN OrderMenuItems
				USING(orderID)
				JOIN MenuItem
				USING(menuItemID)
				WHERE firstName = '$firstname' 
				AND lastName = '$lastname'
				AND esign = '$esign'";

	
	$result = mysqli_query($conn, $query)
		or die('Show dishes failed ' . mysqli_error());

	if (mysqli_num_rows($result) === 0)
		print '<h3>No result found!</h3>';
	// Print result in html
	else {
		print "<table>
					<thread>
						<tr>
							<th>Dish ID</th>
							<th>Name</th>
							<th>Price</th>
							<th>People</th>
						</tr>
				</thread>
			<tbody>";
		while ($tuple = mysqli_fetch_row($result)) {
			print '<tr>';
			print "<td align=\"center\" valign=\"center\">$tuple[0]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[1]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[2]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[3]</td>";
			print '</tr>';
		}

		print "</tbody>
			</table>";
	}

	// Free result
	mysqli_free_result($result);
}
else if (isset($_REQUEST['submit4'])) {
	$action = $_REQUEST['action'];
	$ingredientID = $_REQUEST['ingredient'];

	$query = "SELECT userID
				FROM Users
				WHERE firstName = '$firstname'
				AND lastName = '$lastname'
				AND esign = '$esign'";
	
	$result = mysqli_query($conn, $query)
		or die('Find user failed ' . mysqli_error());
	$tuple = mysqli_fetch_row($result);
	if ($action === 'add') {
		// check if the tuple has already existed
		$query2 = "SELECT *
					FROM Prohibits
					WHERE userID = $tuple[0]
					AND ingredientID = $ingredientID";
		
		$result2 = mysqli_query($conn, $query2)
			or die('Find prohibited ingredient failed ' . mysqli_error());
		if (mysqli_num_rows($result2) === 0) {
			$query3 = "INSERT INTO Prohibits (userID, ingredientID)
						VALUES
						($tuple[0], $ingredientID)";
			
			$result3 = mysqli_query($conn, $query3)
				or die('Insertion failed ' . mysqli_error());

			mysqli_free_result($result3);
		}
		mysqli_free_result($result2);
	}
	else if ($action === 'delete') {
		$query2 = "DELETE FROM Prohibits
					WHERE userID = $tuple[0]
					AND ingredientID = $ingredientID";
		
		$result2 = mysqli_query($conn, $query2)
			or die('Deletion failed ' . mysqli_error());
		mysqli_free_result($result2);
	}
	print "<h3>Your record has been modified.</h3>";
	// Free result
	mysqli_free_result($result);
}

// Close connection
mysqli_close($conn);
?>
				
		</div>
	</body>
</html>
