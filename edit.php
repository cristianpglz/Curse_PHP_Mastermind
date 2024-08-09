<?php

require "database.php";

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$id = $_GET["id"];

// Obtener el contacto
$contactStatement = $conn->prepare("SELECT * FROM contacts WHERE id = :id LIMIT 1");
$contactStatement->execute([":id" => $id]);

if ($contactStatement->rowCount() == 0) {
    http_response_code(404);
    echo("HTTP 404 NOT FOUND");
    exit;
}

$contact = $contactStatement->fetch(PDO::FETCH_ASSOC);

// Verificar si el usuario tiene permiso para editar el contacto
if ((int)$contact["user_id"] !== (int)$_SESSION["user"]["id"]) {
    http_response_code(403);
    echo("HTTP 403 UNAUTHORIZED");
    exit;
}

// Obtener direcciones asociadas al contacto
$addressStatement = $conn->prepare("SELECT * FROM addresses WHERE contact_id = :contact_id");
$addressStatement->execute([":contact_id" => $id]);
$addresses = $addressStatement->fetchAll(PDO::FETCH_ASSOC);

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["name"]) || empty($_POST["phone_number"])) {
        $error = "Please fill all the fields.";
    } else if (strlen($_POST["phone_number"]) < 9) {
        $error = "Phone number must be at least 9 characters.";
    } else {
        $name = $_POST["name"];
        $phoneNumber = $_POST["phone_number"];

        // Actualizar contacto
        $contactStatement = $conn->prepare("UPDATE contacts SET name = :name, phone_number = :phone_number WHERE id = :id");
        $contactStatement->execute([
            ":id" => $id,
            ":name" => $name,
            ":phone_number" => $phoneNumber,
        ]);

        $_SESSION["flash"] = ["message" => "Contact {$name} updated."];

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
                <div class="card-header">Edit Contact</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <p class="text-danger"><?= htmlspecialchars($error) ?></p>
                    <?php endif ?>
                    <form method="POST" action="edit.php?id=<?= htmlspecialchars($contact['id']) ?>">
                        <div class="mb-3 row">
                            <label for="name" class="col-md-4 col-form-label text-md-end">Name</label>
                            <div class="col-md-6">
                                <input value="<?= htmlspecialchars($contact['name']) ?>" id="name" type="text" class="form-control" name="name" autocomplete="name" autofocus>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="phone_number" class="col-md-4 col-form-label text-md-end">Phone Number</label>
                            <div class="col-md-6">
                                <input value="<?= htmlspecialchars($contact['phone_number']) ?>" id="phone_number" type="tel" class="form-control" name="phone_number" autocomplete="phone_number">
                            </div>
                        </div>

                        <div id="addresses-container">
                            <?php foreach ($addresses as $address): ?>
                                <div class="mb-3 row">
                                    <label for="address" class="col-md-4 col-form-label text-md-end">Address</label>
                                    <div class="col-md-6">
                                        <input value="<?= htmlspecialchars($address['address']) ?>" type="text" class="form-control" name="addresses[]">
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                        
                        <div class="mb-3 row">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="editaddress.php?id=<?= htmlspecialchars($contact['id']) ?>" class="btn btn-warning ">Modify Addresses</a>
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
    var newAddress = document.createElement('div');
    newAddress.className = 'mb-3 row';
    newAddress.innerHTML = `
        <label for="address" class="col-md-4 col-form-label text-md-end">Address</label>
        <div class="col-md-6">
            <input type="text" class="form-control" name="addresses[]">
        </div>
    `;
    container.appendChild(newAddress);
});
</script>

<?php require "partials/footer.php" ?>
