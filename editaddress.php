<?php

require "database.php";

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// Obtener el ID del contacto desde la URL
$id = $_GET["id"];

if (!is_numeric($id)) {
    http_response_code(400);
    echo "Invalid contact ID";
    exit;
}

// Obtener el contacto para verificar la existencia y el permiso
$contactStatement = $conn->prepare("SELECT * FROM contacts WHERE id = :id LIMIT 1");
$contactStatement->execute([":id" => $id]);

if ($contactStatement->rowCount() == 0) {
    http_response_code(404);
    echo "HTTP 404 NOT FOUND";
    exit;
}

$contact = $contactStatement->fetch(PDO::FETCH_ASSOC);

// Verificar si el usuario tiene permiso para editar el contacto
if ((int)$contact["user_id"] !== (int)$_SESSION["user"]["id"]) {
    http_response_code(403);
    echo "HTTP 403 UNAUTHORIZED";
    exit;
}

// Obtener las direcciones asociadas al contacto
$addressStatement = $conn->prepare("SELECT * FROM addresses WHERE contact_id = :contact_id");
$addressStatement->execute([":contact_id" => $id]);
$addresses = $addressStatement->fetchAll(PDO::FETCH_ASSOC);

// Debugging: Verificar el contenido de $addresses
var_dump($addresses);

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $addresses = $_POST["addresses"] ?? [];

    if (empty($addresses)) {
        $error = "Please add at least one address.";
    } else {
        // Eliminar direcciones existentes
        $deleteAddressesStatement = $conn->prepare("DELETE FROM addresses WHERE contact_id = :contact_id");
        $deleteAddressesStatement->execute([":contact_id" => $id]);

        // Insertar nuevas direcciones
        $addressStatement = $conn->prepare("INSERT INTO addresses (contact_id, address) VALUES (:contact_id, :address)");
        foreach ($addresses as $address) {
            $address = trim($address); // Eliminar espacios en blanco
            if (!empty($address)) {
                $addressStatement->execute([
                    ":contact_id" => $id,
                    ":address" => $address,
                ]);
            }
        }

        $_SESSION["flash"] = ["message" => "Addresses updated."];

        header("Location: home.php");
        exit;
    }
}
?>

<?php require "partials/header.php" ?>

<div class="container pt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit Addresses</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <p class="text-danger"><?= htmlspecialchars($error) ?></p>
                    <?php endif ?>
                    <form method="POST" action="editaddress.php?id=<?= htmlspecialchars($contact['id']) ?>">
                        <div id="addresses-container">
                            <?php if (count($addresses) > 0): ?>
                                <?php foreach ($addresses as $index => $address): ?>
                                    <div class="mb-3 row">
                                        <label for="address<?= $index ?>" class="col-md-4 col-form-label text-md-end">Address</label>
                                        <div class="col-md-6">
                                            <input id="address<?= $index ?>" value="<?= htmlspecialchars($address['address']) ?>" type="text" class="form-control" name="addresses[]">
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            <?php else: ?>
                                <div class="mb-3 row">
                                    <label for="address0" class="col-md-4 col-form-label text-md-end">Address</label>
                                    <div class="col-md-6">
                                        <input id="address0" type="text" class="form-control" name="addresses[]">
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>
                        
                        <button type="button" id="add-address" class="btn btn-secondary">Add Another Address</button>

                        <div class="mb-3 row">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('add-address').addEventListener('click', function() {
    var container = document.getElementById('addresses-container');
    var index = container.getElementsByTagName('input').length; // Get the current number of inputs
    var newAddress = document.createElement('div');
    newAddress.className = 'mb-3 row';
    newAddress.innerHTML = `
        <label for="address${index}" class="col-md-4 col-form-label text-md-end">Address</label>
        <div class="col-md-6">
            <input id="address${index}" type="text" class="form-control" name="addresses[]">
        </div>
    `;
    container.appendChild(newAddress);
});
</script>

<?php require "partials/footer.php" ?>
