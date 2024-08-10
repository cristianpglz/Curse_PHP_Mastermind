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

// Obtener direcciones asociadas al contacto
$addressStatement = $conn->prepare("SELECT * FROM addresses WHERE contact_id = :contact_id");
$addressStatement->execute([":contact_id" => $id]);
$addresses = $addressStatement->fetchAll(PDO::FETCH_ASSOC);
$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["delete_selected"])) {
        $deleteIds = $_POST["delete_ids"] ?? [];
        if (!empty($deleteIds)) {
            $deleteIds = array_map('intval', $deleteIds); // Sanitiza los IDs
            $placeholders = rtrim(str_repeat('?,', count($deleteIds)), ',');
            $deleteAddressesStatement = $conn->prepare("DELETE FROM addresses WHERE id IN ($placeholders) AND contact_id = ?");
            $deleteIds[] = $id; // Añadir el ID del contacto como último parámetro
            $deleteAddressesStatement->execute($deleteIds);
            
            $_SESSION["flash"] = ["message" => "Selected addresses deleted."];
            header("Location: editaddress.php?id=" . $id);
            exit;
        }
    }

    // Manejo de actualización de direcciones
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
                <div class="card-header text-center">Edit Addresses</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <p class="text-danger text-center"><?= htmlspecialchars($error) ?></p>
                    <?php endif ?>

                    <form method="POST" action="editaddress.php?id=<?= htmlspecialchars($contact['id']) ?>">
                        <div id="addresses-container">
                            <?php if (count($addresses) > 0): ?>
                                <?php foreach ($addresses as $index => $address): ?>
                                    <div class="mb-3 row align-items-center">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control small-address" name="addresses[]" value="<?= htmlspecialchars($address['address']) ?>">
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($address['id']) ?>">Delete</button>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            <?php else: ?>
                                <div class="mb-3 row">
                                    <div class="col-md-12">
                                        <input type="text" class="form-control small-address" name="addresses[]" placeholder="Add new address">
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>

                        <div class="text-center mb-3">
                            <button type="button" id="add-address" class="btn btn-secondary">Add Another Address</button>
                            <button type="submit" class="btn btn-primary">Update Addresses</button>
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
    newAddress.className = 'mb-3 row align-items-center';
    newAddress.innerHTML = `
        <div class="col-md-8">
            <input type="text" class="form-control small-address" name="addresses[]" placeholder="New address">
        </div>
        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-danger btn-sm delete-btn">Delete</button>
        </div>
    `;
    container.appendChild(newAddress);
});

// Handle delete button click
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('delete-btn')) {
        if (confirm('Are you sure you want to delete this address?')) {
            var button = event.target;
            var addressId = button.getAttribute('data-id');
            if (addressId) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'editaddress.php?id=<?= htmlspecialchars($contact['id']) ?>';
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_ids[]';
                input.value = addressId;
                form.appendChild(input);
                
                var deleteSelectedInput = document.createElement('input');
                deleteSelectedInput.type = 'hidden';
                deleteSelectedInput.name = 'delete_selected';
                deleteSelectedInput.value = '1';
                form.appendChild(deleteSelectedInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    }
});
</script>

<style>
#addresses-container{
    display: grid;
    justify-content: center;
}
/* Estilo para hacer el cuadro de dirección más pequeño */
.small-address {
    width: 100%;
    max-width: 200px; /* Tamaño máximo del cuadro de dirección */
}
.text-center 

</style>

<?php require "partials/footer.php" ?>
