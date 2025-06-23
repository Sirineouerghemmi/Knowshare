<?php
session_start();

include("../config/database.php");
include("../controller/traitement.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$query = $cnx->prepare("SELECT * FROM users WHERE id = :id");
$query->bindParam(':id', $userId, PDO::PARAM_INT);
$query->execute();
$user = $query->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit();
}

$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Nom_utilisateur';

if (!empty($user['photo'])) {
    $photoPath = "../Uploads/profile_photos/" . $user['photo'];
} else {
    $photoPath = "../assets/p1.jpg";
}

/* Ajout de document */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'], $_POST['category'], $_POST['documentTitle'])) {
    $file = $_FILES['file'];
    $id_category = $_POST['category'];
    $id_user = $_SESSION['user_id'];
    $documentTitle = mb_convert_encoding($_POST['documentTitle'], 'UTF-8', 'auto');

    addDocument($cnx, $file, $id_category, $documentTitle, $id_user);
    header("Location: home2.php");
    exit();
}

/* Traitement des messages */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_message' && isset($_POST['message'])) {
        $content = trim($_POST['message']);
        $attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;
        if (!empty($content) || $attachment) {
            addMessage($cnx, $userId, $content, $attachment);
            header("Location: home2.php?section=community");
            exit();
        }
    } elseif ($_POST['action'] === 'reply_message' && isset($_POST['message'], $_POST['reply_to_user_id'])) {
        $content = trim($_POST['message']);
        $reply_to_user_id = (int)$_POST['reply_to_user_id'];
        $attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;
        if (!empty($content) || $attachment) {
            addReply($cnx, $userId, $content, $reply_to_user_id, $attachment);
            header("Location: home2.php?section=community");
            exit();
        }
    } elseif ($_POST['action'] === 'like' && isset($_POST['message_id'])) {
        toggleReaction($cnx, (int)$_POST['message_id'], $userId, 'like');
        header("Location: home2.php?section=community");
        exit();
    } elseif ($_POST['action'] === 'dislike' && isset($_POST['message_id'])) {
        toggleReaction($cnx, (int)$_POST['message_id'], $userId, 'dislike');
        header("Location: home2.php?section=community");
        exit();
    } elseif ($_POST['action'] === 'report' && isset($_POST['message_id'], $_POST['content'])) {
        reportMessage($cnx, (int)$_POST['message_id'], $userId, $_POST['content']);
        header("Location: home2.php?section=community");
        exit();
    } elseif ($_POST['action'] === 'delete_message' && isset($_POST['message_id'])) {
        deleteMessage($cnx, (int)$_POST['message_id'], $userId);
        header("Location: home2.php?section=community");
        exit();
    }
}

/* Traitement des commentaires */
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'], $_POST['comment'])) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if (addComment($cnx, $user['id'], $rating, $comment)) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Une erreur s'est produite lors de l'envoi de votre commentaire.";
    }
}

/* Traitement de la recherche */
$searchResults = [];
$activeSection = 'homeContent'; 
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $searchResults = searchDocuments($cnx, $searchTerm);
    $activeSection = 'searchContent';
} elseif (isset($_GET['section']) && $_GET['section'] === 'community') {
    $activeSection = 'communityContent';
    $messages = getMessages($cnx);
} elseif (isset($_GET['categoryId']) && is_numeric($_GET['categoryId'])) {
    $categoryId = (int)$_GET['categoryId'];
    $documents = getDocumentsByCategory($cnx, $categoryId);
    $activeSection = 'documentsContent';
} else {
    $documents = getAllDocuments($cnx);
}

$comments = getGeneralComments($cnx, $user['id']);

/*traitement de la recommendation*/
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $stmt = $cnx->prepare("INSERT INTO search_history (user_id, search_term, created_at) VALUES (:user_id, :search_term, NOW())");
    $stmt->execute([':user_id' => $userId, ':search_term' => $searchTerm]);
    $searchResults = searchDocuments($cnx, $searchTerm);
    $activeSection = 'searchContent';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowShare</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="home2.css">
    <link rel="stylesheet" href="footer.css">
    <script src="home2.js"></script>
</head>
<body>
    <div class="wrapper">
        
        <nav class="navbar">
            <img src="../assets/edulogo.jpg" alt="logo">
            <div class="search-bar">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search documents..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" style="display: none;"></button>
                </form>
            </div>
            <button onclick="window.location.href='../controller/logout.php';">Logout</button>      
        </nav>

        <div class="container">
            <aside class="sidebar">
                <div class="profile">
                    <form id="photoForm" action="../controller/upload_profile.php" method="post" enctype="multipart/form-data">
                        <input type="file" id="profile-upload" name="photo" accept="image/*" style="display: none;" onchange="document.getElementById('photoForm').submit();">
                        <img src="<?php echo htmlspecialchars($photoPath); ?>"
                            alt="Profile Picture"
                            id="profile-preview"
                            style="width:100px; height:100px; border-radius:50%; object-fit:cover; cursor:pointer;"
                            onclick="document.getElementById('profile-upload').click();">
                    </form>
                    <h3><?php echo htmlspecialchars($userName); ?></h3>
                    <p>✨ "Every upload helps the community" </p>
                    <button id="addBtn">+ Add</button>
                </div>

                <div id="uploadModal" class="modal">
                    <div class="modal-content">
                        <span class="close">×</span>
                        <h2>Pick and Click– It's That Simple! 😌</h2>
                        <form id="uploadForm" action="" method="POST" enctype="multipart/form-data">
                            <div class="upload-box">
                                <p>Select a file to get started!</p>
                                <input type="file" name="file" id="fileInput" required>
                            </div>
                            <div class="form-group">
                                <label for="documentTitle">Document Title:</label>
                                <input type="text" name="documentTitle" id="documentTitle" placeholder="Enter the document title" required>
                            </div>
                            <p>Choose a category:</p>
                            <select name="category" id="categorySelect" required>
                                <?php 
                                    $categories = getAllCategories($cnx);
                                    if ($categories) {
                                        foreach ($categories as $cat) {
                                            echo '<option value="' . htmlspecialchars($cat['id']) . '">' . htmlspecialchars($cat['nom']) . '</option>';
                                        }
                                    } else {
                                        echo '<option disabled>Aucune catégorie disponible</option>';
                                    }
                                ?>
                            </select><br><br>
                            <button type="submit" id="uploadBtn">Upload</button>
                        </form>
                    </div>
                </div>  

                <div class="nav_menu">
                    <div class="menu-item">
                        <a href="#" onclick="showSection('homeContent')"><i class="fas fa-home"></i> Home</a>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">
                            <i class="fas fa-book"></i> Courses <button class="toggle-btn" onclick="toggleSection('courses-items')">▼</button> 
                        </div>
                        <div id="courses-items" class="section-content">
                            <?php 
                            $categories = getAllCategories($cnx);
                            if ($categories) {
                                foreach($categories as $category) {
                                    echo '<div class="recent-item"><span class="doc-icon">📄</span> 
                                    <a href="?categoryId=' . $category['id'] . '">' . htmlspecialchars($category['nom']) . '</a></div>';
                                }
                            } else {
                                echo '<div class="recent-item">Aucune catégorie trouvée</div>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="menu-item">
                        <a href="#" onclick="showSection('communityContent')"><i class="fas fa-users"></i> Community</a>
                    </div>
                </div>
            </aside>

            <!-- Home Content -->
            <main id="homeContent" class="content main-section <?php echo $activeSection === 'homeContent' ? 'active' : ''; ?>">
                <div class="user-home-content">
                    <div class="profile-summary">
                        <div class="user-card">
                            <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Profile" class="user-photo">
                            <div class="user-info">
                                <h2>👋 Welcome <?php echo htmlspecialchars($user['nom']); ?>!</h2>
                                <h2 class="welcome-message"> Your learning journey starts here 🚀</h2>
                            </div>
                        </div>
                        <p class="stats-intro"><i class="fas fa-chart-pie"></i> Activities overview:</p>

                        <div class="stats-table">
                            <div class="stat-item">
                                <i class="fas fa-file-alt"></i>
                                <div class="stat-value"><?php echo getDocumentCount($cnx, $user['id']); ?></div>
                                <div class="stat-label">Documents</div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-comments"></i>
                                <div class="stat-value"><?php echo getMessageCount($cnx, $user['id']); ?></div>
                                <div class="stat-label">Comments</div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <div class="stat-value"><?php echo getRateCount($cnx, $user['id']); ?></div>
                                <div class="stat-label">Rates</div>
                            </div>
                        </div>
                        <div class="recommendation-carousel">
                        <h3>Recommended for You</h3>
                        <div class="carousel-container">
                            <div class="carousel-track" id="carousel-track">
                                <?php
                                    $recommendations = getRecommendations($cnx, $userId);
                                    if (empty($recommendations)) {
                                        echo '<div class="carousel-item"><i class="fas fa-file-pdf"></i> No recommendations yet</div>';
                                    } else {
                                        foreach ($recommendations as $rec) {
                                            $title = $rec['title'] ?? 'Untitled';
                                            $path = $rec['path'] ?? '#';
                                            $category = $rec['category'] ?? 'Uncategorized';
                                            echo '<div class="carousel-item">';
                                            echo '<a href="../' . htmlspecialchars($path) . '" target="_blank" class="document-link">';
                                            echo '<i class="fas fa-file-pdf"></i> ' . htmlspecialchars($title);
                                            echo '</a>';
                                            echo '<p class="category">' . htmlspecialchars($category) . '</p>';
                                            echo '</div>';
                                        }
                                    }
                                ?>
                            </div>
                        </div>
                    </div>

                        <div class="feedback-section">
                            <h2>Rate Us</h2>
                            <form id="feedbackForm" method="POST">
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>">★</label>
                                    <?php endfor; ?>
                                </div>
                                <textarea name="comment" placeholder="Your comment..." required></textarea>
                                <?php if (isset($error)): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <button type="submit">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Documents Content -->
            <div id="documentsContent" class="content main-section <?php echo $activeSection === 'documentsContent' ? 'active' : ''; ?>">
                <div class="documents-list">
                    <?php if (empty($documents)): ?>
                        <div class="no-documents">
                            <i class="fas fa-folder-open" style="font-size: 50px; color: #aaa;"></i>
                            <h3 style="color: #555;">No documents available in this category</h3>
                            <p style="color: #777;">Please check back later or choose another category.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-item">
                                <div class="document-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="document-info">
                                    <h4><?php echo htmlspecialchars($doc['title']); ?></h4>
                                    <p class="category"><?php echo htmlspecialchars($doc['category_name'] ?? 'Uncategorized'); ?></p>
                                    <p class="date"><?php echo date('d/m/Y', strtotime($doc['date'])); ?></p>
                                    <a href="../<?php echo htmlspecialchars($doc['path']); ?>" target="_blank" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Search Results Content -->
            <div id="searchContent" class="content main-section <?php echo $activeSection === 'searchContent' ? 'active' : ''; ?>">
                <div class="documents-list">
                    <h2>Résultats de la recherche pour "<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"</h2>
                    <?php if (empty($searchResults)): ?>
                        <div class="no-documents">
                            <i class="fas fa-folder-open" style="font-size: 50px; color: #aaa;"></i>
                            <h3 style="color: #555;">Aucun document trouvé</h3>
                            <p style="color: #777;">Essayez avec un autre terme de recherche.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($searchResults as $doc): ?>
                            <div class="document-item">
                                <div class="document-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="document-info">
                                    <h4><?php echo htmlspecialchars($doc['title']); ?></h4>
                                    <p class="category"><?php echo htmlspecialchars($doc['category_name'] ?? 'Uncategorized'); ?></p>
                                    <p class="date"><?php echo date('d/m/Y', strtotime($doc['date'])); ?></p>
                                    <a href="../<?php echo htmlspecialchars($doc['path']); ?>" target="_blank" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Community Content -->
            <main id="communityContent" class="content main-section <?php echo $activeSection === 'communityContent' ? 'active' : ''; ?>">
                <div class="community-container">
                    <div class="messages-container" id="messagesContainer">
                        <?php if (empty($messages)): ?>
                            <div class="no-messages">
                                <h3 style="color: #555;">Aucun message pour le moment</h3>
                                <p style="color: #777;">Soyez le premier à démarrer la conversation !</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?php echo $message['user_id'] == $_SESSION['user_id'] ? 'current-user' : 'other-user'; ?>">
                                    <img src="../Uploads/profile_photos/<?php echo htmlspecialchars($message['photo'] ?: 'default.jpg'); ?>" class="user-photo">
                                    <div class="message-content">
                                        <div class="message-header">
                                            <span class="username"><?php echo htmlspecialchars($message['nom']); ?></span>
                                            <span class="timestamp"><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                                        </div>
                                        <div class="message-text"><?php echo htmlspecialchars($message['content']); ?></div>
                                        <?php if ($message['attachment_path']): ?>
                                            <div class="attachments">
                                                <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $message['attachment_path'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($message['attachment_path']); ?>" class="attachment-image">
                                                <?php elseif (preg_match('/\.pdf$/i', $message['attachment_path'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($message['attachment_path']); ?>" class="pdf-link" target="_blank">
                                                        <i class="fas fa-file-pdf"></i> Voir le PDF
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($message['reply_to_user_id']): ?>
                                            <div class="reply-info">
                                                <small>En réponse à <?php echo htmlspecialchars($message['reply_to_nom']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-actions">
                                            <?php if ($message['user_id'] == $_SESSION['user_id']): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_message">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                    <button type="submit" class="delete-btn" title="Supprimer">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if(!$message['user_reaction']): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="like">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                    <button type="submit" class="like-btn" title="J'aime">
                                                        <i class="far fa-thumbs-up"></i> <span><?php echo $message['reactions']['like']; ?></span>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="dislike">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                    <button type="submit" class="dislike-btn" title="Je n'aime pas">
                                                        <i class="far fa-thumbs-down"></i> <span><?php echo $message['reactions']['dislike']; ?></span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="reaction-count">
                                                    <i class="far fa-thumbs-up"></i> <?php echo $message['reactions']['like']; ?>
                                                </span>
                                                <span class="reaction-count">
                                                    <i class="far fa-thumbs-down"></i> <?php echo $message['reactions']['dislike']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <button class="reply-btn" onclick="showReplyForm(<?php echo $message['id']; ?>, <?php echo $message['user_id']; ?>)" title="Répondre">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                            <?php if (!$message['has_reported']): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="report">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                    <input type="hidden" name="content" value="<?php echo htmlspecialchars($message['content']); ?>">
                                                    <button type="submit" class="report-btn" title="Signaler">
                                                        <i class="fas fa-flag"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="reported">Signalé</span>
                                            <?php endif; ?>
                                        </div>
                                        <div id="reply-form-<?php echo $message['id']; ?>" class="reply-form" style="display:none;">
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="reply_message">
                                                <input type="hidden" name="reply_to_user_id" value="<?php echo $message['user_id']; ?>">
                                                <textarea name="message" placeholder="Votre réponse..." required></textarea>
                                                <input type="file" name="attachment" accept="image/*,.pdf">
                                                <button type="submit">Envoyer</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="message-input">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="send_message">
                            <div class="input-group">
                                <input type="text" name="message" placeholder="Écrivez un message..." class="message-input-field">
                                <label class="attach-btn">
                                    <i class="fas fa-paperclip"></i>
                                    <input type="file" name="attachment" accept="image/*,.pdf" style="display: none;">
                                </label>
                                <button type="submit" class="send-btn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>

        <footer class="footer" id="footer">
            <div class="footer-contact">
                <div class="footer-logo">
                    <img src="../assets/edulogo.jpg" alt="KnowShare Logo" class="footer-logo-img">
                </div>
                <div class="footer-info">
                    <h3>Contact Us</h3>
                    <p><i class="fa fa-phone"></i> +216 96 657 248</p>
                    <p><i class="fa fa-envelope"></i> Knowsharecontact@gmail.com</p>
                </div>
            </div>
            <div class="footer-social">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 KnowShare. All rights reserved.</p>
            </div>
        </footer>
    </div>


</body>
</html>