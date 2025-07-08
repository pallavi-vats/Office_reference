<?php
//  Attachment Download 
if (isset($_GET['download']) && isset($_GET['email_id'])) {
    require_once '../Database.php';
    $db = new Database();
    $conn = $db->getConnection();

    $attachment_id = intval($_GET['download']);
    $email_id = intval($_GET['email_id']);

    $stmt = $conn->prepare("SELECT filename, filedata FROM email_attachments WHERE id = ? AND email_id = ?");
    $stmt->bind_param("ii", $attachment_id, $email_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($file = $result->fetch_assoc()) {
        $filename = $file['filename'];
        $filedata = base64_decode($file['filedata']);

        if ($filedata === false || strlen($filedata) === 0) {
            die("Attachment corrupted or not found.");
        }

        if (ob_get_length()) ob_end_clean();

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: public");
        header("Content-Length: " . strlen($filedata));

        echo $filedata;
        exit();
    } else {
        die("Attachment not found.");
    }
}
?>
<!--  Starts the session and connects to the MySQL database  --> -->
<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);
 require_once '../Database.php';

$db = new Database();
$conn = $db->getConnection();

function decode_subject($subject) {
    $decoded = '';
    $elements = imap_mime_header_decode($subject ?? '(No Subject)');
    foreach ($elements as $element) {
        $decoded .= $element->text;
    }
    return $decoded;
}

function get_body_and_attachments($imap, $email_number, $structure, $part_number = '') {
    $body = '';
    $attachments = [];
    $inline_cid_map = [];
// checks if the current part has subparts
    if (isset($structure->parts)) {
        foreach ($structure->parts as $index => $sub_structure) {
            $new_part_number = $part_number ? "$part_number." . ($index + 1) : (string)($index + 1);
            $result = get_body_and_attachments($imap, $email_number, $sub_structure, $new_part_number);
            $body .= $result['body'];
            $attachments = array_merge($attachments, $result['attachments']);
            $inline_cid_map = array_merge($inline_cid_map, $result['inline_cid_map'] ?? []);
        }
    } else {
        $current_part_number = $part_number ?: 1;
        $part = imap_fetchbody($imap, $email_number, $current_part_number);

        // Decode the part
        switch ($structure->encoding) {
            case 3: $part = base64_decode($part); break;
            case 4: $part = quoted_printable_decode($part); break;
        }

        // Check if it's text
        if ($structure->type == 0) {
            if (strtolower($structure->subtype) === 'plain') {
                $body .= htmlspecialchars($part);
            } elseif (strtolower($structure->subtype) === 'html') {
                $body .= $part;
            }
        }

        // Check if it's an attachment
        $is_attachment = false;
        $filename = '';
        $cid = '';

        if (!empty($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (strtolower($param->attribute) == 'name') {
                    $filename = $param->value;
                    $is_attachment = true;
                }
                if (strtolower($param->attribute) == 'cid') {
                    $cid = trim($param->value, "<>");
                }
            }
        }

        if (!empty($structure->dparameters)) {
            foreach ($structure->dparameters as $dparam) {
                if (strtolower($dparam->attribute) == 'filename') {
                    $filename = $dparam->value;
                    $is_attachment = true;
                }
            }
        }

        if ($is_attachment) {
            if (empty($filename)) {
                $filename = 'attachment_' . uniqid();
            }

            $attachments[] = ['filename' => $filename, 'data' => $part, 'cid' => $cid];

            if ($cid) {
                $inline_cid_map[$cid] = "data:image/png;base64," . base64_encode($part);
            }
        }
    }

    return ['body' => $body, 'attachments' => $attachments, 'inline_cid_map' => $inline_cid_map];
}

//  Fetch Emails
if (isset($_GET['fetch'])) {
    $imap_username = 'pallavikumari2623@gmail.com';
    $imap_password = 'pufmykzrurenbgcg';

    $imap = imap_open('{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX', $imap_username, $imap_password);

    if (!$imap) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'IMAP connection failed.'];
        header("Location: admin_email.php");
        exit();
    }

    $emails = imap_search($imap, 'SINCE "1 June 2025"');

    if ($emails) {
        rsort($emails);
        $emails = array_slice($emails, 0, 50);

        foreach ($emails as $num) {
            $overview = imap_fetch_overview($imap, $num, 0)[0] ?? null;
            if (!$overview) continue;

            $subject = decode_subject($overview->subject ?? '(No Subject)');
            $from = $overview->from ?? '(Unknown Sender)';
            $date = date("Y-m-d H:i:s", strtotime($overview->date ?? 'now'));

            $structure = imap_fetchstructure($imap, $num);
            if (!$structure) continue;

            $result = get_body_and_attachments($imap, $num, $structure);
            $body = $result['body'] ?: '(No message)';
            $attachments = $result['attachments'];
            $cid_map = $result['inline_cid_map'] ?? [];

            foreach ($cid_map as $cid => $data_uri) {
                $body = str_replace(["cid:$cid", "cid:'$cid'", 'cid:"' . $cid . '"'], $data_uri, $body);
            }
            //Checks for duplicates using subject , from , date
            $check_stmt = $conn->prepare("SELECT id FROM emails WHERE subject=? AND email_from=? AND email_date=?");
            $check_stmt->bind_param("sss", $subject, $from, $date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                $insert_email_stmt = $conn->prepare("INSERT INTO emails (email_from, subject, message, email_date, status) VALUES (?, ?, ?, ?, 'unread')");
                $insert_email_stmt->bind_param("ssss", $from, $subject, $body, $date);

                if ($insert_email_stmt->execute()) {
                    $email_id = $conn->insert_id;

                    foreach ($attachments as $file) {
                        if ($file['cid']) continue;

                        $filedata_encoded = base64_encode($file['data']);
                        if (empty($filedata_encoded)) continue;

                        $insert_attachment_stmt = $conn->prepare("INSERT INTO email_attachments (email_id, filename, filedata) VALUES (?, ?, ?)");
                        $insert_attachment_stmt->send_long_data(2, $filedata_encoded); // Important for large data
                        $insert_attachment_stmt->bind_param("iss", $email_id, $file['filename'], $filedata_encoded);
                        $insert_attachment_stmt->execute();
                        $insert_attachment_stmt->close();

                    }
                }
                $insert_email_stmt->close();
            }
            $check_stmt->close();
        }
    }

    imap_close($imap);
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Emails fetched successfully.'];
    header("Location: admin_email.php");
    exit();
}

// 🔻 Mark as Read
if (isset($_GET['mark'])) {
    $email_id_to_mark = intval($_GET['mark']);
    $stmt = $conn->prepare("UPDATE emails SET status='read' WHERE id=?");
    $stmt->bind_param("i", $email_id_to_mark);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Email marked as read.'];
    header("Location: admin_email.php");
    exit();
}

// 🔻 Delete Email
if (isset($_GET['delete'])) {
    $email_id_to_delete = intval($_GET['delete']);
    $conn->begin_transaction();
    try {
        $stmt_attachments = $conn->prepare("DELETE FROM email_attachments WHERE email_id = ?");
        $stmt_attachments->bind_param("i", $email_id_to_delete);
        $stmt_attachments->execute();
        $stmt_attachments->close();

        $stmt_email = $conn->prepare("DELETE FROM emails WHERE id = ?");
        $stmt_email->bind_param("i", $email_id_to_delete);
        $stmt_email->execute();
        $stmt_email->close();

        $conn->commit();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Email deleted successfully.'];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to delete email.'];
    }
    header("Location: admin_email.php");
    exit();
}

//  Display emails
$current_date_filter = '2025-04-04';
$stmt_display = $conn->prepare("SELECT * FROM emails WHERE email_date > ? AND status = 'unread' ORDER BY email_date DESC");
$stmt_display->bind_param("s", $current_date_filter);
$stmt_display->execute();
$result = $stmt_display->get_result();
$stmt_display->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Unread Inbox</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card.border-primary { border-left: 5px solidrgb(96, 52, 198) !important; }
        .email-body-content {
            white-space: normal;
            height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            padding: 15px;
            background-color: #fff;
        }
        .email-body-content img {
            max-width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Admin Email</a>
        <div class="ms-auto">
            <a href="list.php" class="btn btn-outline-light">Back to Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Email Inbox</h2>
    <a href="admin_email.php?fetch=1" class="btn btn-warning mb-3">Fetch New Emails</a>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['message']['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="card mb-4 <?= $row['status'] === 'unread' ? 'border-primary' : '' ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($row['subject']) ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">From: <?= htmlspecialchars($row['email_from']) ?></h6>
                    <p class="card-text"><small class="text-muted"><?= $row['email_date'] ?></small></p>

                    <div class="email-body-content">
                        <?= strpos($row['message'], '<') !== false ? $row['message'] : nl2br(htmlspecialchars($row['message'])) ?>
                    </div>

                    <?php
                    $attachments_stmt = $conn->prepare("SELECT id, filename FROM email_attachments WHERE email_id = ?");
                    $attachments_stmt->bind_param("i", $row['id']);
                    $attachments_stmt->execute();
                    $attachments_res = $attachments_stmt->get_result();

                    if (mysqli_num_rows($attachments_res) > 0) {
                        echo '<div class="mt-2"><strong>Attachments:</strong><ul>';
                        while ($attach = mysqli_fetch_assoc($attachments_res)) {
                            echo '<ul><a href="admin_email.php?download=' . intval($attach['id']) . '&email_id=' . intval($row['id']) . '"class="btn btn-primary btn-sm" >' . htmlspecialchars($attach['filename']) . '</a></ul>';
                        }
                        echo '</ul></div>';
                    }
                    $attachments_stmt->close();
                    ?>

                    <div class="mt-3">
                        <a href="?mark=<?= intval($row['id']) ?>" class="btn btn-success btn-sm">Mark as Read</a>
                        <a href="?delete=<?= intval($row['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this email?')">Delete</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">No unread emails found.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
