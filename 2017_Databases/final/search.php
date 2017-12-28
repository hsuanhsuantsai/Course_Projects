<!DOCTYPE html>
<html>
	<head>
		<title>Search</title>
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
			<h3>Search</h3>
			<h4>Search dish</h4>
			<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
				<b>Keyword</b><br>
				<input type="text" name="keyword"><br>
				<b>Max price of the dish</b><br>
				<input type="number" name="price" min="0"><br>
				<b>Min # of people one dish serves</b><br>
				<input type="number" name="people" min="1"><br>
				<input type="submit" name="submit" value="Search">
			</form>
<br>
			<h4>Search combination</h4>
			<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
				<b>Category 1</b><br>
				<select name="cat1">
					<option value="Appetizer">Appetizer</option>
					<option value="Noodles">Noodles</option>
					<option value="Rice">Rice</option>
					<option value="Soup">Soup</option>
				</select><br>
				<b>Category 2</b><br>
				<select name="cat2">
					<option value="Appetizer">Appetizer</option>
					<option value="Noodles">Noodles</option>
					<option value="Rice">Rice</option>
					<option value="Soup">Soup</option>
				</select>
				<br>
				<b>Max total sum</b><br>
				<input type="number" name="total" min="0"><br>
				<input type="submit" name="submit2" value="Search">
			</form>
<br>
			<h4>Search ingredients</h4>
			<form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
				<b>Dish ID</b><br>
				<input type="number" name="dishid" min="1" required><br>
				<input type="submit" name="submit3" value="Search">
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

if(isset($_REQUEST['submit'])) {
	// Input parameters
	$keyword = input_sanitization($_REQUEST['keyword']);
	$price = $_REQUEST['price'];
	$people = $_REQUEST['people'];

	if(!isset($keyword) || trim($keyword) === '') 
		$keyword = '';
	if (!isset($price) || trim($price) === '')
		$price = 100;
	if (!isset($people) || trim($people) === '') 
		$people = 1;

	// List dishes which meets the criteria in the database
	$query = "SELECT menuItemID, menuItemName, price, people
				FROM MenuItem
				WHERE menuItemName LIKE '%$keyword%'
				AND price <= $price
				AND people >= $people";

	
	$result = mysqli_query($conn, $query)
		or die('Show dishes failed ' . mysqli_error());

	if (mysqli_num_rows($result) === 0)
		print '<h3>No result found, please try another criteria!</h3>';
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
else if (isset($_REQUEST['submit2'])) {
	// Input parameters
	$cat1 = $_REQUEST['cat1'];
	$cat2 = $_REQUEST['cat2'];
	$total = $_REQUEST['total'];

	if (!isset($total) || trim($total) === '') 
		$total = 100;

	// List combinations in the database
	$query = "SELECT A.categoryName, A.menuItemID, A.menuItemName, A.price, A.people, 
					B.categoryName, B.menuItemID, B.menuItemName, B.price, B.people, 
					ROUND(SUM(A.price+B.price),2)
				FROM (SELECT *
					FROM MenuItem
					JOIN MenuCategory
					USING(menuCategoryID)
					WHERE categoryName = '$cat1') AS A,
					(SELECT *
					 FROM MenuItem
					 JOIN MenuCategory
		 			 USING(menuCategoryID)
		 			 WHERE categoryName = '$cat2') AS B
				WHERE A.menuItemID < B.menuItemID
				AND A.price + B.price <= $total
				GROUP BY A.menuItemID, B.menuItemID";

	
	$result = mysqli_query($conn, $query)
		or die('Show combinations failed ' . mysqli_error());

	if (mysqli_num_rows($result) === 0)
		print '<h3>No result found, please try another criteria!</h3>';
	// Print table in html
	else {
		print "<table>
				<thread>
					<tr>
						<th>Category 1</th>
						<th>Dish ID</th>
						<th>Name</th>
						<th>Price</th>
						<th>People</th>
						<th>Category 2</th>
						<th>Dish ID</th>
						<th>Name</th>
						<th>Price</th>
						<th>People</th>
						<th>Total sum</th>
					</tr>
				</thread>
			<tbody>";
		while ($tuple = mysqli_fetch_row($result)) {
			print '<tr>';
			print "<td align=\"center\" valign=\"center\">$tuple[0]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[1]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[2]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[3]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[4]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[5]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[6]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[7]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[8]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[9]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[10]</td>";
			print '</tr>';
		}

		print "</tbody>
			</table>";
	}
	// Free result
	mysqli_free_result($result);
}
else if(isset($_REQUEST['submit3'])) {
	// Input parameters
	$dishid = $_REQUEST['dishid'];

	// List dishes which meets the criteria in the database
	$query = "SELECT ingredientName, CASE WHEN origin IS NULL THEN 'N/A'
										ELSE origin
										END
				FROM Contain
				JOIN Ingredient
				USING(ingredientID)
				WHERE menuItemID = $dishid";

	
	$result = mysqli_query($conn, $query)
		or die('Show ingredients failed ' . mysqli_error());


	if (mysqli_num_rows($result) === 0)
		print '<h3>No result found, please try another criteria!</h3>';
	// Print result in html
	else {
		print "<table>
				<thread>
					<tr>
						<th>Ingredient</th>
						<th>Origin</th>
					</tr>
				</thread>
			<tbody>";
		while ($tuple = mysqli_fetch_row($result)) {
			print '<tr>';
			print "<td align=\"center\" valign=\"center\">$tuple[0]</td>";
			print "<td align=\"center\" valign=\"center\">$tuple[1]</td>";
			print '</tr>';
		}

		print "</tbody>
			</table>";
	}

	// Free result
	mysqli_free_result($result);

}

// Close connection
mysqli_close($conn);
?>
				
		</div>
	</body>
</html>
