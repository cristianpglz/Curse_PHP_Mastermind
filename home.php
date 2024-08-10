<?php

require "database.php";

session_start();

if (!isset($_SESSION["user"])) {
  header("Location: login.php");
  return;
}

$contacts = $conn->query("
    SELECT id, name, phone_number 
    FROM contacts 
    WHERE user_id = {$_SESSION['user']['id']}
");

?>

<?php require "partials/header.php" ?>

<div class="container pt-4 p-3">
  <div class="row">
    
    <?php if ($contacts->rowCount() == 0): ?>
      <div class="col-md-4 mx-auto">
        <div class="card card-body text-center">
          <p>No contacts saved yet</p>
          <a href="add.php">Add One!</a>
        </div>
      </div>
    <?php endif ?>
    
    <?php foreach ($contacts as $contact): ?>
    <?php
    $addressesStatement = $conn->prepare("
        SELECT address 
        FROM addresses 
        WHERE contact_id = :contact_id
    ");
    $addressesStatement->execute([':contact_id' => $contact["id"]]);
    $addresses = $addressesStatement->fetchAll(PDO::FETCH_ASSOC);
    ?>
      <div class="col-md-4 mb-3">
          <div class="card text-center">
              <div class="card-body">
                  <h3 class="card-title text-capitalize"><?= htmlspecialchars($contact["name"]) ?></h3>
                  <p class="m-2">Number: <?= htmlspecialchars($contact["phone_number"]) ?></p>
                  <p class="m-2">
                      <?php if (count($addresses) > 0): ?>
                          <?php 
                          $count = 1; // Initialize the counter
                          foreach ($addresses as $address): ?>
                              <div>Address<?= $count ?>: <?= htmlspecialchars($address["address"]) ?></div>
                              <?php $count++; // Increment the counter ?>
                          <?php endforeach; ?>
                      <?php else: ?>
                          <div>No addresses available</div>
                      <?php endif; ?>
                  </p>
                  <a href="edit.php?id=<?= $contact["id"] ?>" class="btn btn-secondary mb-2">Edit Contact</a>
                  <a href="delete.php?id=<?= $contact["id"] ?>" class="btn btn-danger mb-2">Delete Contact</a>
              </div>
          </div>
      </div>
    <?php endforeach; ?>

  </div>
</div>

<?php require "partials/footer.php" ?>
