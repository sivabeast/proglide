require "includes/db.php";
$res = $conn->query("SELECT id,name FROM brands");
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
