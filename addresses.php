<?php

require "database.php";

session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION["user"])) {
  header("Location: login.php");
  exit();
}

$userId = $_SESSION['user']['id'];

// Obtén todas las direcciones del usuario
$addressesStatement = $conn->prepare("
    SELECT id, address 
    FROM addresses 
    WHERE user_id = :user_id
");
$addressesStatement->execute([':user_id' => $userId]);
$addresses = $addressesStatement->fetchAll(PDO::FETCH_ASSOC);

// Maneja la eliminación de una dirección
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    
    $deleteStatement = $conn->prepare("
        DELETE FROM addresses 
        WHERE id = :id AND user_id = :user_id
    ");
    $deleteStatement->execute([':id' => $deleteId, ':user_id' => $userId]);
    
    // Redirige para evitar el reenvío del formulario
    header("Location: addresses.php");
    exit();
}

?>

<?php require "partials/header.php" ?>

<div class="container pt-4 p-3">
  <h2>My Addresses</h2>
  
  <?php if (count($addresses) === 0): ?>
    <div class="alert alert-info">No addresses found.</div>
  <?php else: ?>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Address</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($addresses as $address): ?>
          <tr>
            <td><?= htmlspecialchars($address["address"]) ?></td>
            <td>
              <a href="?delete_id=<?= $address["id"] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this address?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  
  <a href="add_address.php" class="btn btn-primary">Add New Address</a>
</div>

<?php require "partials/footer.php" ?>
