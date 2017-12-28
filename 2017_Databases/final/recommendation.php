<!DOCTYPE html>
<html>
	<head>
		<title>Recommendation</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<script src="https://code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous">
        </script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	</head>
	<body>
		<div class="container text-center">
			<div class="col-sm-9 col-md-6 col-lg-8">
				<h2>Our recommendations</h2>
<?php
$host = 'mpcs53001.cs.uchicago.edu';
$username = '';
$password = '';
$database = $username.'DB';

// Connect to db
$conn = mysqli_connect($host, $username, $password, $database)
	or die('Could not connect ' . mysqli_connect_error());


$cat = $_REQUEST['cat'];
$counter = 1;
$recommendation = array();

if (!isset($_REQUEST['budget']) || trim($_REQUEST['budget']) === '')
	$budget = 100;
else
	$budget = $_REQUEST['budget'];

if (!isset($_REQUEST['ppl']) || trim($_REQUEST['ppl']) === '')
	$ppl = 2;
else
	$ppl = $_REQUEST['ppl'];

for ($x = count($cat); $x < 3; $x++) {
    $cat[$x] = '';
} 

// from recommendation table
$query = "SELECT DISTINCT recommendationID
			FROM Recommendation
			JOIN RecommendMenuItems
			USING(recommendationID)
			JOIN MenuItem
			USING(menuItemID)
			JOIN MenuCategory
			USING(menuCategoryID)
			WHERE (categoryName = '$cat[0]' OR '$cat[0]' = '')
			OR (categoryName = '$cat[1]' OR '$cat[1]' = '')
			OR (categoryName = '$cat[2]' OR '$cat[2]' = '')
			GROUP BY recommendationID
			HAVING ABS($ppl-AVG(people)) <= 1
			AND SUM(price) <= $budget
			ORDER BY counter DESC
			limit 2";

$result = mysqli_query($conn, $query)
	or die('Find recommendation failed ' . mysqli_error());


while ($tuple = mysqli_fetch_row($result)) {
	$query2 = "SELECT menuItemName
				FROM RecommendMenuItems
				JOIN MenuItem
				USING(menuItemID)
				WHERE recommendationID = $tuple[0]";
	
	$result2 = mysqli_query($conn, $query2)
		or die('Find recommended dishes failed ' . mysqli_error());

	print "<h4>Recommendation $counter</h4>";
	while ($res = mysqli_fetch_row($result2)) {
		print "$res[0]<br>";
	}

	array_push($recommendation, $tuple[0]);
	$counter++;

	if ($counter > 1)
		break;

	mysqli_free_result($result2);

}

$query3 = "SELECT A.menuItemID, A.menuItemName, 
					B.menuItemID, B.menuItemName, 
					C.menuItemID, C.menuItemName
			FROM (SELECT menuItemID, menuItemName, price, people, categoryName, COUNT(*)
					FROM OrderMenuItems
					JOIN MenuItem
					USING(menuItemID)
					JOIN MenuCategory
					USING(menuCategoryID)
					WHERE (categoryName = '$cat[0]' OR '$cat[0]' = '')
					OR (categoryName = '$cat[1]' OR '$cat[1]' = '')
					OR (categoryName = '$cat[2]' OR '$cat[2]' = '')
					GROUP BY menuItemID
					ORDER BY COUNT(*) DESC) A, 
			(SELECT menuItemID, menuItemName, price, people, categoryName, COUNT(*)
				FROM OrderMenuItems
				JOIN MenuItem
				USING(menuItemID)
				JOIN MenuCategory
				USING(menuCategoryID)
				WHERE (categoryName = '$cat[0]' OR '$cat[0]' = '')
				OR (categoryName = '$cat[1]' OR '$cat[1]' = '')
				OR (categoryName = '$cat[2]' OR '$cat[2]' = '')
				GROUP BY menuItemID
				ORDER BY COUNT(*) DESC) B, 
			(SELECT menuItemID, menuItemName, price, people, categoryName, COUNT(*)
				FROM OrderMenuItems
				JOIN MenuItem
				USING(menuItemID)
				JOIN MenuCategory
				USING(menuCategoryID)
				WHERE (categoryName = '$cat[0]' OR '$cat[0]' = '')
				OR (categoryName = '$cat[1]' OR '$cat[1]' = '')
				OR (categoryName = '$cat[2]' OR '$cat[2]' = '')
				GROUP BY menuItemID
				ORDER BY COUNT(*) DESC) C
			WHERE A.price + B.price + C.price <= $budget
			ANd A.menuItemID > B.menuItemID
			AND B.menuItemID > C.menuItemID
			AND ABS($ppl-(A.people+B.people+C.people)/3) <= 1";

$result3 = mysqli_query($conn, $query3)
	or die('Find ordered dishes failed: ' . mysqli_error());

while ($new_rec = mysqli_fetch_row($result3)) {
	$query4 = "INSERT INTO Recommendation(lastDate)
				VALUES
				(NOW())";
	$result4 = mysqli_query($conn, $query4)
		or die('Create recommendation failed ' . mysqli_error());

	$query5 = "SELECT LAST_INSERT_ID()";
	$result5 = mysqli_query($conn, $query5)
		or die('Select new recommendation failed ' . mysqli_error());
	$recID = mysqli_fetch_row($result5);

	print "<h4>Recommendation $counter</h4>";
	for ($x = 0; $x < 3; $x++) {
		$name = $new_rec[2*$x+1];
		print "$name<br>";
		$tmp = $new_rec[2*$x];
		$query6 = "INSERT INTO RecommendMenuItems(recommendationID, menuItemID)
					VALUES
					($recID[0], $tmp)";
		$result6 = mysqli_query($conn, $query6)
			or die('Create new recommendation dish failed ' . mysqli_error());

		mysqli_free_result($result6);
	}

	array_push($recommendation, $recID[0]);
	$counter++;

	if ($counter > 4)
		break;

	mysqli_free_result($result4);
	mysqli_free_result($result5);
}

if ($counter === 1)
	print "<h3>Oops! We cound not find a good recommendation!</h3>";
print "</div>
		<div class=\"col-sm-3 col-md-6 col-lg-4\">
			<h2>Order here</h2>
			<form id=\"rec_form\" action=\"rec_order.php\" method=\"post\">";
$y = 1;
foreach ($recommendation as $value) {
	print "<input type=\"checkbox\" name=\"my_recommendation[]\" value=\"$value\">Recommendation $y<br>";
    $y++;
}	

print "     <b>First Name</b><br>
			<input type=\"text\" name=\"firstName\" required><br>
			<b>Last Name</b><br>
			<input type=\"text\" name=\"lastName\" required><br>
			<b>e-signature</b><br>
			<input type=\"text\" name=\"esign\" required><br>
			<input type=\"submit\" value=\"Order!\">
		</form>";
// Free result
mysqli_free_result($result);
mysqli_free_result($result3);
// Close connection
mysqli_close($conn);
?>
			</div>
		</div>
		<script type="text/javascript">
			$('#rec_form').on('submit', function(event) {
				if ($('input[type="checkbox"]:checked').length === 0) {
					event.preventDefault();
					alert("You haven't selected any recommendations!");
				}
			})
		</script>
	</body>
</html>