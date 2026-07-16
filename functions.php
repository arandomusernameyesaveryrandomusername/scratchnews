<?php
require_once __DIR__ . '/config.php';

// Get all articles, newest first
function getAllArticles(): array {
    $db = getDB();
    $result = $db->query("SELECT * FROM articles ORDER BY created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get a single article by ID
function getArticleById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();
    $stmt->close();
    return $article ?: null;
}

// Allow only safe formatting tags in article content (bold, italic, headers, colors via span, etc.)
function sanitizeArticleHtml(string $html): string {
    $allowed = '<p><br><strong><b><em><i><s><strike><u><h1><h2><h3><span><ul><ol><li><blockquote><a><img>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*("javascript:[^"]*"|\'javascript:[^\']*\')/i', '$1="#"', $html);
    return $html;
}

// Create a new article, returns the new ID
function createArticle(string $title, string $summary, string $content, string $author, ?string $imageUrl = null): int {
    $db = getDB();
    $content = sanitizeArticleHtml($content);
    $id = getNextArticleId();
    $stmt = $db->prepare("INSERT INTO articles (id, title, summary, content, author, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssss', $id, $title, $summary, $content, $author, $imageUrl);
    $stmt->execute();
    $stmt->close();
    return $id;
}

// Update an existing article
function updateArticle(int $id, string $title, string $summary, string $content, string $author, ?string $imageUrl = null): bool {
    $db = getDB();
    $content = sanitizeArticleHtml($content);
    $stmt = $db->prepare("UPDATE articles SET title = ?, summary = ?, content = ?, author = ?, image_url = ? WHERE id = ?");
    $stmt->bind_param('sssssi', $title, $summary, $content, $author, $imageUrl, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// Delete an article
function deleteArticle(int $id): bool {
    $db = getDB();
    $article = getArticleById($id);
    if ($article && !empty($article['image_url'])) {
        deleteUploadedImage($article['image_url']);
    }
    $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// Small helper to safely print user content
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function logVisit(string $page): void {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
    $stmt = $db->prepare("INSERT INTO visits (ip_address, page, user_agent) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $ip, $page, $ua);
    $stmt->execute();
    $stmt->close();
}

function getRecentVisits(int $limit = 200): array {
    $db = getDB();
    $limit = max(1, min($limit, 1000));
    $result = $db->query("SELECT * FROM visits ORDER BY visited_at DESC LIMIT {$limit}");
    return $result->fetch_all(MYSQLI_ASSOC);
}
// ---- Reader accounts ----
function createUser(string $username, string $email, string $password) {
    $db = getDB();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $username, $email, $hash);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();
        return $id;
    } catch (mysqli_sql_exception $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) return 'duplicate';
        throw $e;
    }
}

function getUserByUsername(string $username): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function setDarkModePreference(int $userId, bool $enabled): void {
    $db = getDB();
    $val = $enabled ? 1 : 0;
    $stmt = $db->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->bind_param('ii', $val, $userId);
    $stmt->execute();
    $stmt->close();
}

// ---- Comments ----
function getCommentsForArticle(int $articleId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE article_id = ? ORDER BY comments.created_at ASC");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function addComment(int $articleId, int $userId, string $content, ?int $parentId = null): bool {
    $db = getDB();
    if ($parentId === null) {
        $stmt = $db->prepare("INSERT INTO comments (article_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $articleId, $userId, $content);
    } else {
        $stmt = $db->prepare("INSERT INTO comments (article_id, user_id, content, parent_comment_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iisi', $articleId, $userId, $content, $parentId);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function buildCommentTree(array $comments): array {
    $byId = [];
    foreach ($comments as $c) {
        $c['replies'] = [];
        $byId[$c['id']] = $c;
    }

    $tree = [];
    foreach ($byId as $id => $c) {
        if ($c['parent_comment_id'] !== null && isset($byId[$c['parent_comment_id']])) {
            $byId[$c['parent_comment_id']]['replies'][] = &$byId[$id];
        } else {
            $tree[] = &$byId[$id];
        }
    }

    // Only return top-level comments; replies are nested inside via reference
    $topLevel = [];
    foreach ($tree as $c) {
        $topLevel[] = $c;
    }
    return $topLevel;
}

function renderCommentThread(array $comment, bool $canReply, int $depth = 0, bool $canReport = false): string {
    $indent = min($depth * 24, 96); // cap indentation so deep threads don't run off-screen
    $html = '<div class="comment" style="margin-left: ' . $indent . 'px;">';
    $html .= '<strong><a href="/@' . e($comment['username']) . '">' . e($comment['username']) . '</a></strong>';
    $html .= ' <span class="meta">' . date('M j, Y g:i A', strtotime($comment['created_at'])) . '</span>';
    $html .= '<p>' . e($comment['content']) . '</p>';

    if ($canReply) {
        $formId = 'reply-form-' . (int)$comment['id'];
        $html .= '<button type="button" class="reply-toggle" title="Reply" onclick="document.getElementById(\'' . $formId . '\').classList.toggle(\'open\')"><img src="/assets/icons/reply.svg" class="icon-svg-sm" alt=""> Reply</button>';
        $html .= '<form method="post" class="reply-form" id="' . $formId . '">';
        $html .= '<input type="hidden" name="action" value="comment">';
        $html .= '<input type="hidden" name="parent_id" value="' . (int)$comment['id'] . '">';
        $html .= '<textarea name="content" placeholder="Write a reply..." required></textarea>';
        $html .= '<button class="btn" type="submit">Post Reply</button>';
        $html .= '</form>';
    }

    if ($canReport) {
        $html .= ' <form method="post" class="report-form" onsubmit="return confirm(\'Report this comment for review?\');">';
        $html .= '<input type="hidden" name="action" value="report">';
        $html .= '<input type="hidden" name="comment_id" value="' . (int)$comment['id'] . '">';
        $html .= '<button type="submit" class="reply-toggle" title="Report"><img src="/assets/icons/report.svg" class="icon-svg-sm" alt=""> Report</button>';
        $html .= '</form>';
    }

    foreach ($comment['replies'] as $reply) {
        $html .= renderCommentThread($reply, $canReply, $depth + 1, $canReport);
    }

    $html .= '</div>';
    return $html;
}

// ---- Likes ----
function getLikeCount(int $articleId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM likes WHERE article_id = ?");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['cnt'];
}

function hasUserLiked(int $articleId, int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM likes WHERE article_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $articleId, $userId);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)$found;
}

function toggleLike(int $articleId, int $userId): bool {
    $db = getDB();
    if (hasUserLiked($articleId, $userId)) {
        $stmt = $db->prepare("DELETE FROM likes WHERE article_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $articleId, $userId);
        $stmt->execute();
        $stmt->close();
        return false;
    } else {
        $stmt = $db->prepare("INSERT INTO likes (article_id, user_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $articleId, $userId);
        $stmt->execute();
        $stmt->close();
        $del = $db->prepare("DELETE FROM dislikes WHERE article_id = ? AND user_id = ?");
        $del->bind_param('ii', $articleId, $userId);
        $del->execute();
        $del->close();
        return true;
    }
}

// ---- Dislikes ----
function getDislikeCount(int $articleId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM dislikes WHERE article_id = ?");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['cnt'];
}

function hasUserDisliked(int $articleId, int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM dislikes WHERE article_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $articleId, $userId);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)$found;
}

function toggleDislike(int $articleId, int $userId): bool {
    $db = getDB();
    if (hasUserDisliked($articleId, $userId)) {
        $stmt = $db->prepare("DELETE FROM dislikes WHERE article_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $articleId, $userId);
        $stmt->execute();
        $stmt->close();
        return false;
    } else {
        $stmt = $db->prepare("INSERT INTO dislikes (article_id, user_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $articleId, $userId);
        $stmt->execute();
        $stmt->close();
        $del = $db->prepare("DELETE FROM likes WHERE article_id = ? AND user_id = ?");
        $del->bind_param('ii', $articleId, $userId);
        $del->execute();
        $del->close();
        return true;
    }
}

// ---- Profile / account deletion helpers ----
function getUserById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function getCommentsByUser(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT comments.*, articles.title AS article_title FROM comments JOIN articles ON comments.article_id = articles.id WHERE comments.user_id = ? ORDER BY comments.created_at DESC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function deleteUserAccount(int $userId): bool {
    $db = getDB();
    $db->begin_transaction();
    try {
        $stmt = $db->prepare("DELETE FROM comments WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare("DELETE FROM likes WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        return true;
    } catch (Throwable $e) {
        $db->rollback();
        return false;
    }
}

function issueVerificationToken($userId) {
    $db = getDB();
    $token = bin2hex(random_bytes(32));

    $stmt = $db->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $userId);
    $stmt->execute();
    $stmt->close();

    return $token;
}

function getUserByVerificationToken($token) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function markEmailVerified($userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function sendVerificationEmail($toEmail, $toUsername, $token) {
    $verifyLink = "https://scratchnews.freedev.app/verify?token=" . urlencode($token);

    $payload = json_encode([
        "sender" => [
            "name" => "ScratchNews",
            "email" => BREVO_SENDER_EMAIL
        ],
        "to" => [
            ["email" => $toEmail, "name" => $toUsername]
        ],
        "subject" => "Verify your ScratchNews account",
        "htmlContent" => "<p>Hi " . htmlspecialchars($toUsername) . ",</p>"
            . "<p>Click the link below to verify your email address and unlock likes and comments on ScratchNews:</p>"
            . "<p><a href=\"" . htmlspecialchars($verifyLink) . "\">" . htmlspecialchars($verifyLink) . "</a></p>"
            . "<p>If you didn't create this account, you can ignore this email.</p>"
    ]);

    $ch = curl_init("https://api.brevo.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "api-key: " . BREVO_API_KEY,
        "content-type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // TEMPORARY DEBUG LOGGING — remove once email sending is confirmed working
    file_put_contents(__DIR__ . '/brevo_debug.log',
        date('Y-m-d H:i:s') . " | HTTP $httpCode | curl_error: $curlError | response: $response\n",
        FILE_APPEND
    );

    return $httpCode >= 200 && $httpCode < 300;
}

function createSubmission($userId, $title, $summary, $content) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO submissions (user_id, title, summary, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $title, $summary, $content);
    $stmt->execute();
    $id = $db->insert_id;
    $stmt->close();
    return $id;
}

function getPendingSubmissions() {
    $db = getDB();
    $result = $db->query("
        SELECT submissions.*, users.username, users.email
        FROM submissions
        JOIN users ON submissions.user_id = users.id
        WHERE submissions.status = 'pending'
        ORDER BY submissions.created_at ASC
    ");
    $submissions = [];
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    return $submissions;
}

function getSubmissionById($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT submissions.*, users.username, users.email
        FROM submissions
        JOIN users ON submissions.user_id = users.id
        WHERE submissions.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();
    $stmt->close();
    return $submission ?: null;
}

function approveSubmission($id) {
    $db = getDB();
    $submission = getSubmissionById($id);
    if (!$submission || $submission['status'] !== 'pending') {
        return false;
    }

    $articleId = getNextArticleId();
    $stmt = $db->prepare("INSERT INTO articles (id, title, summary, content, author) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $articleId, $submission['title'], $submission['summary'], $submission['content'], $submission['username']);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("UPDATE submissions SET status = 'approved', reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    sendSubmissionDecisionEmail($submission['email'], $submission['username'], $submission['title'], true);
    return true;
}

function rejectSubmission($id) {
    $db = getDB();
    $submission = getSubmissionById($id);
    if (!$submission || $submission['status'] !== 'pending') {
        return false;
    }

    $stmt = $db->prepare("UPDATE submissions SET status = 'rejected', reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    sendSubmissionDecisionEmail($submission['email'], $submission['username'], $submission['title'], false);
    return true;
}

function sendSubmissionDecisionEmail($toEmail, $toUsername, $articleTitle, $approved) {
    if ($approved) {
        $subject = "Your ScratchNews submission was approved!";
        $body = "<p>Hi " . htmlspecialchars($toUsername) . ",</p>"
            . "<p>Great news — your submission \"" . htmlspecialchars($articleTitle) . "\" has been approved and is now live on ScratchNews.</p>"
            . "<p><a href=\"https://scratchnews.freedev.app/\">Check it out</a></p>";
    } else {
        $subject = "Update on your ScratchNews submission";
        $body = "<p>Hi " . htmlspecialchars($toUsername) . ",</p>"
            . "<p>Thanks for submitting \"" . htmlspecialchars($articleTitle) . "\" to ScratchNews. After review, we've decided not to publish this one.</p>"
            . "<p>Feel free to submit again in the future!</p>";
    }

    $payload = json_encode([
        "sender" => [
            "name" => "ScratchNews",
            "email" => BREVO_SENDER_EMAIL
        ],
        "to" => [
            ["email" => $toEmail, "name" => $toUsername]
        ],
        "subject" => $subject,
        "htmlContent" => $body
    ]);

    $ch = curl_init("https://api.brevo.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "api-key: " . BREVO_API_KEY,
        "content-type: application/json"
    ]);

    curl_exec($ch);
    curl_close($ch);
}

function submitFeedback($userId, $message) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $message);
    $stmt->execute();
    $stmt->close();
}

function getAllFeedback() {
    $db = getDB();
    $result = $db->query("
        SELECT feedback.*, users.username
        FROM feedback
        LEFT JOIN users ON feedback.user_id = users.id
        ORDER BY feedback.created_at DESC
    ");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function searchArticles(string $query): array {
    $db = getDB();
    $like = '%' . $query . '%';
    $stmt = $db->prepare("SELECT * FROM articles WHERE title LIKE ? OR summary LIKE ? OR content LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function reportComment($commentId, $reporterId) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO comment_reports (comment_id, reporter_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $commentId, $reporterId);
    $stmt->execute();
    $stmt->close();
}

function getPendingReports() {
    $db = getDB();
    $result = $db->query("
        SELECT comment_reports.id AS report_id, comment_reports.created_at AS reported_at,
               comments.id AS comment_id, comments.content, comments.article_id,
               commenter.username AS commenter_username,
               reporter.username AS reporter_username
        FROM comment_reports
        JOIN comments ON comment_reports.comment_id = comments.id
        JOIN users AS commenter ON comments.user_id = commenter.id
        JOIN users AS reporter ON comment_reports.reporter_id = reporter.id
        WHERE comment_reports.status = 'pending'
        ORDER BY comment_reports.created_at ASC
    ");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function resolveReport($reportId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE comment_reports SET status = 'resolved' WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $stmt->close();
}

function adminDeleteComment($commentId) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $stmt->close();
}

function banUser($userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function unbanUser($userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function isUserBanned($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result && (int)$result['is_banned'] === 1;
}

function getAllUsers() {
    $db = getDB();
    $result = $db->query("SELECT id, username, email, is_admin, is_banned, email_verified, created_at FROM users ORDER BY created_at DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function anonymizeUser($userId) {
    $db = getDB();
    $anonUsername = 'deleted_user_' . $userId;
    $anonEmail = 'deleted_' . $userId . '@deleted.local';
    $unusableHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, is_banned = 1, email_verified = 0, verification_token = NULL WHERE id = ?");
    $stmt->bind_param("sssi", $anonUsername, $anonEmail, $unusableHash, $userId);
    $stmt->execute();
    $stmt->close();
}

// Find the lowest unused article ID (fills gaps left by moved/deleted articles)
function getNextArticleId(): int {
    $db = getDB();
    $result = $db->query("
        SELECT MIN(t1.id + 1) AS next_id
        FROM articles t1
        LEFT JOIN articles t2 ON t1.id + 1 = t2.id
        WHERE t2.id IS NULL
    ");
    $row = $result->fetch_assoc();
    return $row['next_id'] ? (int)$row['next_id'] : 1;
}

function saveUploadedImage(array $file): ?string {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 3 * 1024 * 1024) throw new RuntimeException('Image must be under 3MB.');

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $dir = __DIR__ . '/assets/uploads/articles';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if ($ext === 'svg' || in_array($detectedType, ['image/svg+xml', 'text/xml', 'text/html', 'text/plain'], true)) {
        $content = file_get_contents($file['tmp_name']);
        if ($content === false || stripos($content, '<svg') === false) {
            throw new RuntimeException('File is not a valid SVG.');
        }
        $content = sanitizeSvg($content);
        $filename = bin2hex(random_bytes(8)) . '.svg';
        file_put_contents($dir . '/' . $filename, $content);
        return '/assets/uploads/articles/' . $filename;
    }

    $info = getimagesize($file['tmp_name']);
    if (!$info) throw new RuntimeException('File is not a valid image.');
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($allowed[$info['mime']])) throw new RuntimeException('Only JPG, PNG, GIF, WEBP, or SVG images are allowed.');
    $filename = bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
    move_uploaded_file($file['tmp_name'], $dir . '/' . $filename);
    resizeImageIfNeeded($dir . '/' . $filename, $info['mime']);
    return '/assets/uploads/articles/' . $filename;
}

function sanitizeSvg(string $svg): string {
    $svg = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $svg);
    $svg = preg_replace('#<foreignObject\b[^>]*>.*?</foreignObject>#is', '', $svg);
    $svg = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $svg);
    $svg = preg_replace('/(href|xlink:href)\s*=\s*("javascript:[^"]*"|\'javascript:[^\']*\')/i', '$1="#"', $svg);
    return $svg;
}

function deleteUploadedImage(?string $url): void {
    if (!$url) return;
    $path = __DIR__ . $url;
    $uploadsRoot = realpath(__DIR__ . '/assets/uploads');
    if (is_file($path) && $uploadsRoot && strpos(realpath($path), $uploadsRoot) === 0) {
        unlink($path);
    }
}

function getPopularArticles(int $limit = 12): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT a.*, COUNT(l.article_id) AS like_count
         FROM articles a
         LEFT JOIN likes l ON l.article_id = a.id
         GROUP BY a.id
         ORDER BY like_count DESC, a.created_at DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function resizeImageIfNeeded(string $path, string $mime, int $maxDim = 1600): void {
    if (!extension_loaded('gd') || $mime === 'image/gif') return;
    $info = getimagesize($path);
    if (!$info) return;
    [$width, $height] = $info;
    if ($width <= $maxDim && $height <= $maxDim) return;

    $ratio = min($maxDim / $width, $maxDim / $height);
    $newWidth = (int) round($width * $ratio);
    $newHeight = (int) round($height * $ratio);

    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($path); break;
        case 'image/png': $src = imagecreatefrompng($path); break;
        case 'image/webp': $src = imagecreatefromwebp($path); break;
        default: return;
    }
    if (!$src) return;

    $dst = imagecreatetruecolor($newWidth, $newHeight);
    if ($mime === 'image/png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    switch ($mime) {
        case 'image/jpeg': imagejpeg($dst, $path, 85); break;
        case 'image/png': imagepng($dst, $path, 6); break;
        case 'image/webp': imagewebp($dst, $path, 85); break;
    }
    imagedestroy($src);
    imagedestroy($dst);
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool {
    return !empty($_POST['csrf_token']) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function requireCsrf(): void {
    if (!verifyCsrf()) {
        http_response_code(403);
        die('Session expired or invalid request. Please refresh the page and try again.');
    }
}