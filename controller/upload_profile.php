<?php
session_start();
include("../config/database.php");

if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $tmpName = $_FILES['photo']['tmp_name'];
    $photoName = uniqid() . "_" . $_FILES['photo']['name'];
    $destination = "../uploads/profile_photos/" . $photoName;

    // Déplacer l'image
    if (move_uploaded_file($tmpName, $destination)) {
        $userId = $_SESSION['user_id'];
        $sql = "UPDATE users SET photo = ? WHERE id = ?";
        $stmt = $cnx->prepare($sql);
        
        // Stockez seulement le nom du fichier, pas le chemin complet
        $stmt->execute([$photoName, $userId]);
        
        header("Location: ../view/home2.php");
        exit();
    } else {
        echo "Erreur lors du téléchargement.";
    }
}
?>