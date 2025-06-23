<?php
include("../config/database.php");

function getAllUsers($cnx) {
    try {
        $req = "SELECT id, nom, email FROM users";
        $res = $cnx->query($req);
        
        return $res->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur dans getAllUsers: " . $e->getMessage());
        return [];
    }
}

function searchUsers($cnx, $name) {
    $req = "SELECT id, nom, email FROM users WHERE nom LIKE :name";
    $stmt = $cnx->prepare($req);
    $stmt->execute(['name' => '%' . $name . '%']);
    $users = $stmt->fetchAll();
    return $users;
}

function deleteUser($user_id, $cnx) {
    $query = "DELETE FROM users WHERE id = :user_id";
    $stmt = $cnx->prepare($query);
    
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function getAllDocuments($cnx) {
    $stmt = $cnx->prepare("SELECT d.id, d.is_popular, d.title, c.nom AS category_name, d.date, u.nom AS uploaded_by
                        FROM document d
                        JOIN users u ON d.id_user = u.id
                        JOIN category c ON d.id_category = c.id");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



function deleteDocument($document_id, $cnx) {
    $query = "DELETE FROM document WHERE id = :document_id";
    $stmt = $cnx->prepare($query);
    
    $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function getAllReports($cnx) 
{
    $req = "SELECT r.id, r.content, r.created_at, u.nom 
            FROM users u 
            JOIN report r ON u.id = r.reported_by_user_id";
    
    $res = $cnx->query($req);
    return $res->fetchAll(PDO::FETCH_ASSOC);
}

function deleteReport(PDO $pdo, int $reportId): bool {
    $stmt = $pdo->prepare("SELECT message_id FROM report WHERE id = ?");
    $stmt->execute([$reportId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        return false;
    }

    $messageId = $message['message_id'];

    $stmt = $pdo->prepare("DELETE FROM report WHERE id = ?");
    if (!$stmt->execute([$reportId])) {
        return false;
    }

    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    return $stmt->execute([$messageId]);
}

function getAllComments($cnx) {
    $req = "SELECT r.id, r.content, r.created_at AS date, r.is_featured, u.nom 
            FROM users u 
            JOIN comments r ON u.id = r.user_id";
    $res = $cnx->query($req);
    return $res->fetchAll(PDO::FETCH_ASSOC);
}

function deleteComment(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    return $stmt->execute([$id]);
}

function getAllMessages($cnx) {
    $req = "SELECT m.id, m.content, m.created_at, m.attachment_path, u.nom AS sender, u2.nom AS reply_to 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            LEFT JOIN users u2 ON m.reply_to_user_id = u2.id";
    $res = $cnx->query($req);
    return $res->fetchAll(PDO::FETCH_ASSOC);
}

function deleteMessage(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    return $stmt->execute([$id]);
}
function addDocument($cnx, $file, $id_category, $documentTitle, $id_user = null) {
    $uploadDir = '../Uploads/doc_uploads/';
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $dbPath = 'Uploads/doc_uploads/' . $fileName;
        $stmt = $cnx->prepare("INSERT INTO document (path, id_user, id_category, title) 
                            VALUES (:path, :id_user, :id_category, :title)");
        $stmt->execute([
            ':path' => $dbPath,
            ':id_user' => $id_user,
            ':id_category' => $id_category,
            ':title' => $documentTitle
        ]);
    }
}

?>