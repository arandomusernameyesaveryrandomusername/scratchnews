<?php
require_once __DIR__ . '/config.php';

function getAllArticles(bool $includeDrafts = false): array {
    $db = getDB();
    $sql = "SELECT * FROM articles";
    if (!$includeDrafts) $sql .= " WHERE status = 'published'";
    $sql .= " ORDER BY created_at DESC";
    $result = $db->query($sql);
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

function createArticle(string $title, string $summary, string $content, string $author, ?string $imageUrl = null, string $status = 'published'): int {
    $db = getDB();
    $content = sanitizeArticleHtml($content);
    $id = getNextArticleId();
    $stmt = $db->prepare("INSERT INTO articles (id, title, summary, content, author, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('issssss', $id, $title, $summary, $content, $author, $imageUrl, $status);
    $stmt->execute();
    $stmt->close();
    return $id;
}

// Update an existing article
function updateArticle(int $id, string $title, string $summary, string $content, string $author, ?string $imageUrl = null, string $status = 'published'): bool {
    $db = getDB();
    $content = sanitizeArticleHtml($content);
    $stmt = $db->prepare("UPDATE articles SET title = ?, summary = ?, content = ?, author = ?, image_url = ?, status = ? WHERE id = ?");
    $stmt->bind_param('ssssssi', $title, $summary, $content, $author, $imageUrl, $status, $id);
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
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $db->prepare("INSERT INTO visits (ip_address, page, user_agent) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $ip, $page, $ua);
    $stmt->execute();
    $stmt->close();
    $db->query("DELETE FROM visits WHERE id NOT IN (SELECT id FROM (SELECT id FROM visits ORDER BY id DESC LIMIT 200) AS keep)");

    $stmt = $db->prepare("INSERT IGNORE INTO daily_unique_visitors (visit_date, ip_address) VALUES (CURDATE(), ?)");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->close();
    $db->query("DELETE FROM daily_unique_visitors WHERE visit_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
}

function getRecentVisits(int $limit = 200, ?string $includeIp = null, ?string $excludeIp = null): array {
    $db = getDB();
    $sql = "SELECT * FROM visits WHERE 1=1";
    $params = [];
    $types = '';

    if ($includeIp !== null && $includeIp !== '') {
        $sql .= " AND ip_address = ?";
        $params[] = $includeIp;
        $types .= 's';
    }
    if ($excludeIp !== null && $excludeIp !== '') {
        $sql .= " AND ip_address != ?";
        $params[] = $excludeIp;
        $types .= 's';
    }

    $sql .= " ORDER BY id DESC LIMIT ?";
    $params[] = $limit;
    $types .= 'i';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
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

// ---- Remember Me ----
function setRememberToken(int $userId): string {
    $db = getDB();
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
    $stmt = $db->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?");
    $stmt->bind_param('ssi', $hash, $expires, $userId);
    $stmt->execute();
    $stmt->close();
    return $token;
}

function clearRememberToken(int $userId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

function getUserByValidRememberToken(int $userId, string $token): ?array {
    $db = getDB();
    $hash = hash('sha256', $token);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND remember_token = ? AND remember_token_expires > NOW()");
    $stmt->bind_param('is', $userId, $hash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function startSession(): void {
    session_start();

    if (empty($_SESSION['reader_id']) && !empty($_COOKIE['remember_me'])) {
        [$uid, $token] = array_pad(explode(':', $_COOKIE['remember_me'], 2), 2, '');
        $uid = (int)$uid;
        if ($uid > 0 && $token !== '') {
            $user = getUserByValidRememberToken($uid, $token);
            if ($user) {
                $_SESSION['reader_id'] = $user['id'];
                $_SESSION['reader_username'] = $user['username'];
                $_SESSION['is_admin'] = !empty($user['is_admin']);
                $_SESSION['dark_mode'] = $user['dark_mode'];
                $newToken = setRememberToken($user['id']);
                setcookie('remember_me', $user['id'] . ':' . $newToken, time() + 60 * 60 * 24 * 30, '/; SameSite=Lax', '', true, true);
            } else {
                setcookie('remember_me', '', time() - 3600, '/');
            }
        }
    }
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
    $html .= ' <span class="meta">' . utcTimeTag($comment['created_at'], 'datetime') . '</span>';
    $html .= '<p>' . e($comment['content']) . '</p>';

    if ($canReply) {
        $formId = 'reply-form-' . (int)$comment['id'];
        $html .= '<button type="button" class="reply-toggle" title="Reply" onclick="document.getElementById(\'' . $formId . '\').classList.toggle(\'open\')"><img src="/assets/icons/reply.svg" class="icon-svg-sm" alt=""> Reply</button>';
        $html .= '<form method="post" class="reply-form" id="' . $formId . '">';
        $html .= csrfField();
        $html .= '<input type="hidden" name="action" value="comment">';
        $html .= '<input type="hidden" name="parent_id" value="' . (int)$comment['id'] . '">';
        $html .= '<textarea name="content" placeholder="Write a reply..." required></textarea>';
        $html .= '<button class="btn" type="submit">Post Reply</button>';
        $html .= '</form>';
    }

    if ($canReport) {
        $html .= ' <form method="post" class="report-form" onsubmit="return confirm(\'Report this comment for review?\');">';
        $html .= csrfField();
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
function getCommentCount(int $articleId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM comments WHERE article_id = ?");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['cnt'];
}

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

function getApprovedArticleCountByUser(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM submissions WHERE user_id = ? AND status = 'approved'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
    return (int)$count;
}

function getArticleCountByUser(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM articles WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
    return (int)$count;
}

function getArticlesByUser(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM articles WHERE user_id = ? ORDER BY created_at DESC");
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
    $stmt = $db->prepare("INSERT INTO articles (id, title, summary, content, author, user_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $articleId, $submission['title'], $submission['summary'], $submission['content'], $submission['username'], $submission['user_id']);
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
    $stmt = $db->prepare("SELECT * FROM articles WHERE status = 'published' AND (title LIKE ? OR summary LIKE ? OR content LIKE ?) ORDER BY created_at DESC");
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

    $stmt = $db->prepare("DELETE FROM likes WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM dislikes WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
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
         WHERE a.status = 'published'
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

function sendNoCacheHeaders(): void {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function impersonateUser(int $adminId, int $targetUserId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $targetUserId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target) return false;

    $stmt = $db->prepare("INSERT INTO impersonation_log (admin_id, target_user_id, started_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('ii', $adminId, $targetUserId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['impersonator_admin_id'] = $adminId;
    $_SESSION['impersonator_admin_username'] = $_SESSION['reader_username'];
    $_SESSION['reader_id'] = $target['id'];
    $_SESSION['reader_username'] = $target['username'];
    $_SESSION['is_admin'] = (bool)$target['is_admin'];
    $_SESSION['dark_mode'] = (bool)$target['dark_mode'];
    return true;
}

function stopImpersonation(): bool {
    if (empty($_SESSION['impersonator_admin_id'])) return false;
    $db = getDB();
    $adminId = $_SESSION['impersonator_admin_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$admin) return false;

    $_SESSION['reader_id'] = $admin['id'];
    $_SESSION['reader_username'] = $admin['username'];
    $_SESSION['is_admin'] = (bool)$admin['is_admin'];
    $_SESSION['dark_mode'] = (bool)$admin['dark_mode'];
    unset($_SESSION['impersonator_admin_id'], $_SESSION['impersonator_admin_username']);
    return true;
}

function isDisposableEmail(string $email): bool {
    $blocked = ['gicont.com', 'ezimb.com', 'mailinator.com', 'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'yopmail.com', 'trashmail.com', 'discard.email', 'getnada.com'];
    $domain = strtolower(substr(strrchr($email, '@'), 1));
    return in_array($domain, $blocked, true);
}

function tooManySignupAttempts(string $ip, int $maxAttempts = 5, int $windowMinutes = 10): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM signup_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL ? MINUTE)");
    $stmt->bind_param('si', $ip, $windowMinutes);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $count >= $maxAttempts;
}

function logSignupAttempt(string $ip, bool $successful = false): void {
    $db = getDB();
    $successInt = $successful ? 1 : 0;
    $stmt = $db->prepare("INSERT INTO signup_attempts (ip, created_at, successful) VALUES (?, NOW(), ?)");
    $stmt->bind_param('si', $ip, $successInt);
    $stmt->execute();
    $stmt->close();
}

function utcTimeTag(string $datetimeUtc, string $style = 'date'): string {
    try {
        $dt = new DateTime($datetimeUtc, new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return e($datetimeUtc);
    }
    $iso = $dt->format('c');
    $class = $style === 'datetime' ? 'local-datetime' : 'local-date';
    return '<time class="' . $class . '" datetime="' . $iso . '">' . e($datetimeUtc) . '</time>';
}

// ── Categories ──────────────────────────────────────────

function getAllCategories(): array {
    $db = getDB();
    return $db->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
}

function getCategoryBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function getArticleCategoryIds(int $articleId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT category_id FROM article_categories WHERE article_id = ?");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return array_map('intval', array_column($rows, 'category_id'));
}

function getArticleCategories(int $articleId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT c.* FROM categories c
        JOIN article_categories ac ON ac.category_id = c.id
        WHERE ac.article_id = ?");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Enforces max 3 categories per article regardless of what's passed in
function setArticleCategories(int $articleId, array $categoryIds): void {
    $categoryIds = array_slice(array_unique(array_map('intval', $categoryIds)), 0, 3);
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM article_categories WHERE article_id = ?");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $stmt->close();
    if (empty($categoryIds)) return;
    $stmt = $db->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
    foreach ($categoryIds as $catId) {
        $stmt->bind_param('ii', $articleId, $catId);
        $stmt->execute();
    }
    $stmt->close();
}

function getArticlesByCategorySlug(string $slug): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.* FROM articles a
        JOIN article_categories ac ON ac.article_id = a.id
        JOIN categories c ON c.id = ac.category_id
        WHERE c.slug = ? AND a.status = 'published'
        ORDER BY a.created_at DESC");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ── Views & Trending ────────────────────────────────────

function incrementArticleView(int $articleId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $stmt->close();
}

// Score = (views + likes*3 + comments*4) / (age_in_hours + 2)^1.5
// Recency decay means a hot new article can outrank an old high-total one.
function getTrendingArticles(int $limit = 10): array {
    $db = getDB();
    $sql = "SELECT a.*,
        (a.views
            + (SELECT COUNT(*) FROM likes l WHERE l.article_id = a.id) * 3
            + (SELECT COUNT(*) FROM comments c WHERE c.article_id = a.id) * 4
        ) / POWER(TIMESTAMPDIFF(HOUR, a.created_at, NOW()) + 2, 1.5) AS trend_score
        FROM articles a
        WHERE a.status = 'published'
        ORDER BY trend_score DESC
        LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ── Subscribers ─────────────────────────────────────────

function getSubscriberIdByEmail(string $email): ?int {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['id'] : null;
}

// Returns the confirm token to email out. Re-subscribing resets preferences to whatever was just picked.
function createSubscriber(string $email, array $categoryIds): string {
    $db = getDB();
    $confirmToken = bin2hex(random_bytes(24));
    $unsubToken = bin2hex(random_bytes(24));

    $stmt = $db->prepare("INSERT INTO subscribers (email, confirm_token, unsubscribe_token) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE confirm_token = VALUES(confirm_token)");
    $stmt->bind_param('sss', $email, $confirmToken, $unsubToken);
    $stmt->execute();
    $stmt->close();

    $subscriberId = getSubscriberIdByEmail($email);

    $stmt = $db->prepare("DELETE FROM subscriber_categories WHERE subscriber_id = ?");
    $stmt->bind_param('i', $subscriberId);
    $stmt->execute();
    $stmt->close();

    if (!empty($categoryIds)) {
        $stmt = $db->prepare("INSERT INTO subscriber_categories (subscriber_id, category_id) VALUES (?, ?)");
        foreach (array_map('intval', $categoryIds) as $catId) {
            $stmt->bind_param('ii', $subscriberId, $catId);
            $stmt->execute();
        }
        $stmt->close();
    }

    return $confirmToken;
}

function confirmSubscriber(string $token): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE subscribers SET confirmed = 1, confirm_token = NULL, confirmed_at = NOW() WHERE confirm_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

function unsubscribeByToken(string $token): bool {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM subscribers WHERE unsubscribe_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

function getSubscribersForCategories(array $categoryIds): array {
    if (empty($categoryIds)) return [];
    $db = getDB();
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $types = str_repeat('i', count($categoryIds));
    $stmt = $db->prepare("SELECT DISTINCT s.* FROM subscribers s
        JOIN subscriber_categories sc ON sc.subscriber_id = s.id
        WHERE s.confirmed = 1 AND sc.category_id IN ($placeholders)");
    $stmt->bind_param($types, ...array_map('intval', $categoryIds));
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ── Subscriber emails (shares the Brevo pattern from sendVerificationEmail) ──

function sendBrevoEmail(string $payload): bool {
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
    curl_close($ch);
    return $httpCode >= 200 && $httpCode < 300;
}

function sendSubscriptionConfirmEmail(string $toEmail, string $token): bool {
    $confirmLink = "https://scratchnews.freedev.app/confirm-subscription.php?token=" . urlencode($token);
    $payload = json_encode([
        "sender" => ["name" => "ScratchNews", "email" => BREVO_SENDER_EMAIL],
        "to" => [["email" => $toEmail]],
        "subject" => "Confirm your ScratchNews subscription",
        "htmlContent" => "<p>Thanks for subscribing to ScratchNews!</p>"
            . "<p>Click below to confirm your email and start getting Scratch news in your inbox:</p>"
            . "<p><a href=\"" . htmlspecialchars($confirmLink) . "\">" . htmlspecialchars($confirmLink) . "</a></p>"
            . "<p>If you didn't request this, you can ignore this email.</p>"
    ]);
    return sendBrevoEmail($payload);
}

function sendNewArticleNotification(string $toEmail, string $unsubToken, string $articleTitle, int $articleId): bool {
    $articleLink = "https://scratchnews.freedev.app/article/" . $articleId;
    $unsubLink = "https://scratchnews.freedev.app/unsubscribe.php?token=" . urlencode($unsubToken);
    $payload = json_encode([
        "sender" => ["name" => "ScratchNews", "email" => BREVO_SENDER_EMAIL],
        "to" => [["email" => $toEmail]],
        "subject" => "New article on ScratchNews: " . $articleTitle,
        "htmlContent" => "<p>A new article just went up that matches your interests:</p>"
            . "<p><a href=\"" . htmlspecialchars($articleLink) . "\"><strong>" . htmlspecialchars($articleTitle) . "</strong></a></p>"
            . "<p style=\"margin-top:2rem;font-size:0.85em;color:#888;\"><a href=\"" . htmlspecialchars($unsubLink) . "\">Unsubscribe</a> from these emails.</p>"
    ]);
    return sendBrevoEmail($payload);
}

// Call this right after setArticleCategories(), only when status is 'published'
function notifySubscribersOfNewArticle(int $articleId, string $articleTitle): void {
    $categoryIds = getArticleCategoryIds($articleId);
    if (empty($categoryIds)) return;
    $subscribers = getSubscribersForCategories($categoryIds);
    foreach ($subscribers as $sub) {
        sendNewArticleNotification($sub['email'], $sub['unsubscribe_token'], $articleTitle, $articleId);
    }
}

function getExploreArticles(string $categorySlug, string $sort, string $authorFilter = '', string $dateFrom = '', string $dateTo = ''): array {
    $db = getDB();
    $joins = '';
    $where = "a.status = 'published'";
    $params = [];
    $types = '';

    if ($categorySlug !== 'all') {
        $joins = "JOIN article_categories ac ON ac.article_id = a.id JOIN categories c ON c.id = ac.category_id";
        $where .= " AND c.slug = ?";
        $params[] = $categorySlug;
        $types .= 's';
    }

    if ($authorFilter !== '') {
        $where .= " AND a.author LIKE ?";
        $params[] = '%' . $authorFilter . '%';
        $types .= 's';
    }

    if ($dateFrom !== '') {
        $where .= " AND a.created_at >= ?";
        $params[] = $dateFrom . ' 00:00:00';
        $types .= 's';
    }

    if ($dateTo !== '') {
        $where .= " AND a.created_at <= ?";
        $params[] = $dateTo . ' 23:59:59';
        $types .= 's';
    }

    $likeExpr = "(SELECT COUNT(*) FROM likes l WHERE l.article_id = a.id)";
    $dislikeExpr = "(SELECT COUNT(*) FROM dislikes d WHERE d.article_id = a.id)";
    $commentExpr = "(SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id)";
    $trendExpr = "(a.views + $likeExpr*3 + $commentExpr*4) / POWER(TIMESTAMPDIFF(HOUR, a.created_at, NOW()) + 2, 1.5)";

    switch ($sort) {
        case 'recent': $orderBy = "a.created_at DESC"; break;
        case 'popular': $orderBy = "a.views DESC, a.created_at DESC"; break;
        case 'most_liked': $orderBy = "$likeExpr DESC, a.created_at DESC"; break;
        case 'most_disliked': $orderBy = "$dislikeExpr DESC, a.created_at DESC"; break;
        case 'oldest': $orderBy = "a.created_at ASC"; break;
        default: $orderBy = "$trendExpr DESC"; break; // 'metrics'
    }

    $sql = "SELECT a.* FROM articles a $joins WHERE $where ORDER BY $orderBy";
    $stmt = $db->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}