require_once 'config.php';
startSession();

$db = getMySQLDB();

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing image ID');
}

$id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT mime_type, image_data FROM images WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$image = $stmt->fetch(PDO::FETCH_ASSOC);

if ($image) {
    header("Content-Type: " . $image['mime_type']);
    echo $image['image_data'];
    exit;
}

http_response_code(404);
echo "Image not found";
?>