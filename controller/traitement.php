<?php
include("../config/database.php");

function AddUser($cnx, $data) {
    // Vérifier si l'email existe déjà
    $stmt = $cnx->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        error_log('Erreur : Cet email est déjà utilisé.');
        return false;
    }

    // Hacher le mot de passe
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Déterminer le rôle : admin si email est admin@knowshare.com, sinon user
    $role = ($data['email'] === 'admin@knowshare.com') ? 'admin' : 'user';

    // Insérer l'utilisateur avec le rôle
    $req = $cnx->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, ?)");
    $res = $req->execute([$data['user_name'], $data['email'], $hashedPassword, $role]);

    if ($res) {
        error_log('Utilisateur ajouté avec succès: ' . json_encode($data));
        return true;
    }
    return false;
}

function Login($cnx) {
    $error = "";
    $user_name = "";
    $user = null;
    $isTest = php_sapi_name() === 'cli';
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['sign_up'])) {
        return AddUser($cnx, $_POST); 
    }
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        try {
            $stmt = $cnx->prepare("SELECT id, nom, email, password, photo, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nom'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_photo'] = !empty($user['photo']) ? $user['photo'] : '../view/photo_uploads/p1.jpg';
                $_SESSION['role'] = $user['role'];
                if ($isTest) {
                    return ['redirect' => $user['role'] === 'admin' ? 'dashboard.php' : 'home2.php'];
                }

                if ($user['role'] === 'admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: home2.php");
                }
                exit();
            }
            $error = "Identifiants incorrects";
        } catch (PDOException $e) {
            $error = "Erreur de connexion";
        }
    }
    return ['error' => $error, 'user_name' => $user_name];
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

function getAllCategories($cnx) {
    $req = "SELECT * FROM category";
    $res = $cnx->query($req);
    return $res->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryName($cnx, $categoryId) {
    $query = "SELECT nom FROM category WHERE id = ?";
    $stmt = $cnx->prepare($query);
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nom'] : 'Catégorie inconnue';
}

function getDocumentsByCategory($cnx, $categoryId) {
    try {
        $req = "SELECT d.*, c.nom as category_name 
                FROM document d
                JOIN category c ON d.id_category = c.id
                WHERE d.id_category = :categoryId
                ORDER BY d.date DESC";
        $stmt = $cnx->prepare($req);
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getDocumentsByCategory: " . $e->getMessage());
        return false;
    }
}

function getAllDocuments($cnx) {
    try {
        $req = "SELECT d.*, c.nom as category_name 
                FROM document d
                JOIN category c ON d.id_category = c.id
                ORDER BY d.date DESC";
        $res = $cnx->query($req);
        return $res->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getAllDocuments: " . $e->getMessage());
        return false;
    }
}

function getGeneralComments($cnx, $userId) {
    try {
        $query = $cnx->prepare("
            SELECT c.*, u.nom as username 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.user_id = :userId
            ORDER BY c.created_at DESC
        ");
        $query->bindParam(':userId', $userId, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getGeneralComments: " . $e->getMessage());
        return [];
    }
}

function addComment(PDO $cnx, int $user_id, int $rating, string $content): bool {
    // Vérifier les paramètres
    if ($rating < 1 || $rating > 5 || empty(trim($content))) {
        error_log("Validation failed: rating=$rating, content='$content'");
        return false;
    }

    // Vérifier si l'utilisateur existe
    $stmt = $cnx->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        error_log("User does not exist: user_id=$user_id");
        return false;
    }

    try {
        $stmt = $cnx->prepare("INSERT INTO comments (user_id, rating, content, created_at) VALUES (:user_id, :rating, :content, NOW())");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Failed to insert comment: user_id=$user_id, rating=$rating, content='$content'");
        }
        
        return $success;
    } catch (PDOException $e) {
        error_log("Comment insertion failed: " . $e->getMessage());
        return false;
    }
}

function getDocumentCount($cnx, $userId) {
    try {
        $query = "SELECT COUNT(*) as count FROM document WHERE id_user = :userId";
        $stmt = $cnx->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    } catch (PDOException $e) {
        error_log("Erreur dans getDocumentCount: " . $e->getMessage());
        return 0;
    }
}

function getRateCount($cnx, $userId) {
    $sql = "SELECT COUNT(*) AS total FROM comments WHERE user_id = :user_id";
    $stmt = $cnx->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['total'] : 0;
}

function getMessageCount($cnx, $userId) {
    try {
        $query = "SELECT COUNT(*) as count FROM messages WHERE user_id = :userId";
        $stmt = $cnx->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    } catch (PDOException $e) {
        error_log("Erreur dans getMessageCount: " . $e->getMessage());
        return 0;
    }
}

function addMessage($cnx, $userId, $content, $attachment = null) {
    $attachmentPath = null;
    if ($attachment && $attachment['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (in_array($attachment['type'], $allowedTypes)) {
            $uploadDir = '../Uploads/message_attachments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($attachment['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($attachment['tmp_name'], $targetPath)) {
                $attachmentPath = 'Uploads/message_attachments/' . $fileName;
            }
        }
    }

    $stmt = $cnx->prepare("INSERT INTO messages (user_id, content, attachment_path, created_at) VALUES (?, ?, ?, NOW())");
    return $stmt->execute([$userId, $content, $attachmentPath]);
}

function addReply($cnx, $userId, $content, $replyToUserId, $attachment = null) {
    $attachmentPath = null;
    if ($attachment && $attachment['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (in_array($attachment['type'], $allowedTypes)) {
            $uploadDir = '../Uploads/message_attachments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($attachment['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($attachment['tmp_name'], $targetPath)) {
                $attachmentPath = 'Uploads/message_attachments/' . $fileName;
            }
        }
    }

    $stmt = $cnx->prepare("INSERT INTO messages (user_id, content, reply_to_user_id, attachment_path, created_at) VALUES (?, ?, ?, ?, NOW())");
    return $stmt->execute([$userId, $content, $replyToUserId, $attachmentPath]);
}

function toggleReaction($cnx, $messageId, $userId, $type) {
    // Vérifier si l'utilisateur a déjà réagi
    $stmt = $cnx->prepare("SELECT type FROM likes_dislikes WHERE message_id = ? AND user_id = ?");
    $stmt->execute([$messageId, $userId]);
    $existingReaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingReaction) {
        // L'utilisateur a déjà réagi, ne rien faire
        return false;
    }

    // Ajouter la nouvelle réaction
    $stmt = $cnx->prepare("INSERT INTO likes_dislikes (message_id, user_id, type, created_at) VALUES (?, ?, ?, NOW())");
    return $stmt->execute([$messageId, $userId, $type]);
}

function getReactionCounts($cnx, $messageId) {
    $stmt = $cnx->prepare("SELECT type, COUNT(*) as count FROM likes_dislikes WHERE message_id = ? GROUP BY type");
    $stmt->execute([$messageId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = ['like' => 0, 'dislike' => 0];
    foreach ($results as $result) {
        $counts[$result['type']] = (int)$result['count'];
    }
    return $counts;
}

function hasUserReacted($cnx, $messageId, $userId) {
    $stmt = $cnx->prepare("SELECT type FROM likes_dislikes WHERE message_id = ? AND user_id = ?");
    $stmt->execute([$messageId, $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['type'] : null;
}

function reportMessage($cnx, $messageId, $userId, $content) {
    try {
        $stmt = $cnx->prepare("INSERT INTO report (message_id, reported_by_user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$messageId, $userId, $content]);
    } catch (PDOException $e) {
        // Si l'erreur est due à une violation de contrainte unique, c'est que l'utilisateur a déjà signalé
        if ($e->getCode() == 23000) {
            return false;
        }
        throw $e;
    }
}

function hasUserReported($cnx, $messageId, $userId) {
    $stmt = $cnx->prepare("SELECT id FROM report WHERE message_id = ? AND reported_by_user_id = ?");
    $stmt->execute([$messageId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function getMessages($cnx) {
    $query = $cnx->prepare("
        SELECT m.*, u.nom, u.photo, ru.nom as reply_to_nom
        FROM messages m
        JOIN users u ON m.user_id = u.id
        LEFT JOIN users ru ON m.reply_to_user_id = ru.id
        ORDER BY m.created_at ASC
    ");
    $query->execute();
    $messages = $query->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter les comptes de réactions et l'état des réactions de l'utilisateur
    foreach ($messages as &$message) {
        $message['reactions'] = getReactionCounts($cnx, $message['id']);
        $message['user_reaction'] = hasUserReacted($cnx, $message['id'], $_SESSION['user_id']);
        $message['has_reported'] = hasUserReported($cnx, $message['id'], $_SESSION['user_id']);
    }
    return $messages;
}

function deleteMessage($cnx, $messageId, $userId) {
    try {
        // Vérifier que le message appartient à l'utilisateur
        $stmt = $cnx->prepare("SELECT user_id, attachment_path FROM messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($message && $message['user_id'] == $userId) {
            // Supprimer le fichier attaché s'il existe
            if ($message['attachment_path']) {
                $filePath = '../' . $message['attachment_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Supprimer les réactions associées
            $stmt = $cnx->prepare("DELETE FROM likes_dislikes WHERE message_id = ?");
            $stmt->execute([$messageId]);

            // Supprimer les signalements associés
            $stmt = $cnx->prepare("DELETE FROM report WHERE message_id = ?");
            $stmt->execute([$messageId]);

            // Supprimer le message
            $stmt = $cnx->prepare("DELETE FROM messages WHERE id = ?");
            return $stmt->execute([$messageId]);
        }
        return false;
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression du message: " . $e->getMessage());
        return false;
    }
}

function searchDocuments($cnx, $title) {
    $req = "SELECT * FROM document WHERE title LIKE :title";
    $stmt = $cnx->prepare($req);
    $stmt->bindValue(':title', '%' . $title . '%', PDO::PARAM_STR);
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $documents;
}

function getAllUsers($cnx) {
    try {
        // Requête pour récupérer seulement id, nom et email
        $req = "SELECT id, nom, email FROM users";
        $res = $cnx->query($req);
        
        // Retourne le tableau associatif complet
        return $res->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        // En cas d'erreur, retourne un tableau vide et affiche l'erreur (pour debug)
        error_log("Erreur dans getAllUsers: " . $e->getMessage());
        return [];
    }
}

function getAllDocument($cnx) {
    $stmt = $cnx->prepare("
        SELECT d.id, d.title, c.nom AS category_name, d.date AS uploaded_at, u.nom AS uploaded_by
        FROM document d
        JOIN users u ON d.id_user = u.id
        JOIN category c ON d.id_category = c.id
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecommendations($cnx, $userId) {
    // Define Python paths
    $preferredPath = 'C:\Users\sirin\AppData\Local\Programs\Python\Python312\python.exe';
    $fallbackPath = 'C:\Users\sirin\AppData\Local\Microsoft\WindowsApps\python.exe';
    $pythonPath = file_exists($preferredPath) ? $preferredPath : (file_exists($fallbackPath) ? $fallbackPath : null);
    // Corrected script path to match earlier context (scripts, not script)
    $scriptPath = realpath('C:\xampp\htdocs\knowshare\scripts\recommendations.py');

    // Verify script and Python executable existence
    if (!$scriptPath || !file_exists($scriptPath)) {
        error_log("Script Python introuvable : $scriptPath", 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");
        return [];
    }
    if (!$pythonPath) {
        error_log("Aucun exécutable Python trouvé : $preferredPath ou $fallbackPath", 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");
        return [];
    }

    // Escape paths and arguments
    $pythonPath = escapeshellarg($pythonPath);
    $scriptPath = escapeshellarg($scriptPath);
    $userIdArg = escapeshellarg($userId);

    // Build and log command
    $command = "$pythonPath $scriptPath $userIdArg 2>&1";
    error_log("Exécution de la commande Python : $command", 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");

    // Execute command
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);

    // Log raw output
    $rawOutput = implode("\n", $output);
    file_put_contents("C:/xampp/htdocs/knowshare/logs/python_raw_output.txt", $rawOutput . "\n", FILE_APPEND);
    error_log("Sortie Python pour l'utilisateur $userId : $rawOutput", 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");

    // Check execution status
    if ($returnVar !== 0) {
        error_log("Erreur d'exécution Python pour l'utilisateur $userId : Code de retour $returnVar", 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");
        return [];
    }

    // Validate and decode JSON output
    if (empty($rawOutput) || !json_decode($rawOutput, true)) {
        error_log("Sortie Python non-JSON ou vide pour l'utilisateur $userId : $rawOutput", 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");
        return [];
    }

    $result = json_decode($rawOutput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Erreur de décodage JSON pour l'utilisateur $userId : " . json_last_error_msg(), 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");
        return [];
    }

    // Extract recommendations
    $recommendations = $result['recommendations'] ?? [];
    if (empty($recommendations)) {
        error_log("Aucune recommandation pour l'utilisateur $userId : " . json_encode($result), 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");
    } else {
        error_log("Recommandations générées pour l'utilisateur $userId : " . json_encode($recommendations), 3, "C:/xampp/htdocs/knowshare/logs/php_errors.log");
    }

    return $recommendations;
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

function getPopularDocuments($cnx) {
    try {
        $stmt = $cnx->prepare("
            SELECT d.id, d.title, d.path, d.date, d.is_popular, u.nom AS uploaded_by, c.nom AS category_name
            FROM document d
            LEFT JOIN users u ON d.id_user = u.id
            LEFT JOIN category c ON d.id_category = c.id
            WHERE d.is_popular = 1
            ORDER BY d.date DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Debug: Output the number of results
        error_log("Number of popular documents found: " . count($results));
        return $results;
    } catch (PDOException $e) {
        // Output the error for debugging
        die("Database error in getPopularDocuments: " . $e->getMessage());
    }
}

function getFeaturedComments($cnx) {
    try {
        $stmt = $cnx->prepare("
            SELECT c.id, c.content, c.created_at, c.is_featured, u.nom
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.is_featured = 1
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Debug: Output the number of results
        error_log("Number of featured comments found: " . count($results));
        return $results;
    } catch (PDOException $e) {
        // Output the error for debugging
        die("Database error in getFeaturedComments: " . $e->getMessage());
    }
}
?>