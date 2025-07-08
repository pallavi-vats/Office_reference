<?php
session_start();
require_once '../Database.php';
set_time_limit(300);
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
    $inline_images = [];

    if (!isset($structure->parts)) {
        $content = imap_fetchbody($imap, $email_number, $part_number ?: 1);
        if ($structure->encoding == 3) $content = base64_decode($content);
        elseif ($structure->encoding == 4) $content = quoted_printable_decode($content);

        if ($structure->type == 0 && strtolower($structure->subtype) === 'html') {
            $body = $content;
        }

        if (isset($structure->disposition) && strtolower($structure->disposition) === 'attachment') {
            $filename = 'attachment';
            foreach (array_merge($structure->parameters ?? [], $structure->dparameters ?? []) as $param) {
                if (strtolower($param->attribute) == 'filename') {
                    $filename = $param->value;
                }
            }
            $attachments[] = ['filename' => $filename, 'data' => $content];
        }

        if (isset($structure->id)) {
            $cid = trim($structure->id, '<>');
            $inline_images[$cid] = 'data:image/png;base64,' . base64_encode($content);
        }

    } else {
        foreach ($structure->parts as $i => $part) {
            $part_result = get_body_and_attachments($imap, $email_number, $part, ($i + 1));
            $body .= $part_result['body'];
            $attachments = array_merge($attachments, $part_result['attachments']);
            $inline_images = array_merge($inline_images, $part_result['inline_images']);
        }
    }

    return ['body' => $body, 'attachments' => $attachments, 'inline_images' => $inline_images];
}

if (isset($_GET['fetch'])) {
    $imap = imap_open('{imap.gmail.com:993/imap/ssl}INBOX', 'pallavikumari2623@gmail.com', 'pufmykzrurenbgcg');
    if (!$imap) die('IMAP Connection Failed');

    $emails = imap_search($imap, 'UNSEEN');
    if ($emails) {
        foreach (array_slice($emails, 0, 50) as $num) {
            $overview = imap_fetch_overview($imap, $num, 0)[0] ?? null;
            if (!$overview) continue;

            $subject = decode_subject($overview->subject ?? '(No Subject)');
            $from = $overview->from ?? '(Unknown)';
            $date = date("Y-m-d H:i:s", strtotime($overview->date ?? 'now'));
            $structure = imap_fetchstructure($imap, $num);

            $result = get_body_and_attachments($imap, $num, $structure);
            $body = $result['body'];
            foreach ($result['inline_images'] as $cid => $uri) {
                $body = str_replace("cid:$cid", $uri, $body);
            }

            $subject = mysqli_real_escape_string($conn, $subject);
            $from = mysqli_real_escape_string($conn, $from);
            $body = mysqli_real_escape_string($conn, $body);
            $date = mysqli_real_escape_string($conn, $date);

            $exist = mysqli_query($conn, "SELECT id FROM emails WHERE subject='$subject' AND email_from='$from' AND email_date='$date'");
            if (mysqli_num_rows($exist) == 0) {
                mysqli_query($conn, "INSERT INTO emails (email_from, subject, message, email_date, status) VALUES ('$from', '$subject', '$body', '$date', 'unread')");
            }
        }
    }

    imap_close($imap);
    header("Location: admin_email.php?success=1");
    exit();
}

if (isset($_GET['mark'])) {
    $id = intval($_GET['mark']);
    mysqli_query($conn, "UPDATE emails SET status='read' WHERE id=$id");
    header("Location: admin_email.php?marked=$id");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM emails WHERE id=$id");
    header("Location: admin_email.php?deleted=$id");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM emails ORDER BY email_date DESC");
?>

<!DOCTYPE html>
<html>
<head><title>Email Inbox</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
<h2>Email Inbox</h2>
<a href="?fetch=1" class="btn btn-warning mb-3">Fetch New Emails</a>
<?php if (isset($_GET['success'])): ?><div class="alert alert-success">Emails fetched.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-danger">Email deleted.</div><?php endif; ?>
<?php if (isset($_GET['marked'])): ?><div class="alert alert-info">Email marked as read.</div><?php endif; ?>

<?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <div class="card mb-4 <?= $row['status'] === 'unread' ? 'border-primary' : '' ?>">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($row['subject']) ?></h5>
            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($row['email_from']) ?> | <?= $row['email_date'] ?></h6>
            <div class="border p-3 bg-white" style="white-space: normal;">
                <?= $row['message'] ?>
            </div>
            <div class="mt-3">
                <?php if ($row['status'] === 'unread'): ?>
                    <a href="?mark=<?= $row['id'] ?>" class="btn btn-success btn-sm">Mark as Read</a>
                <?php else: ?>
                    <span class="badge bg-secondary">Read</span>
                <?php endif; ?>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this email?')" class="btn btn-danger btn-sm">Delete</a>
            </div>
        </div>
    </div>
<?php } ?>
</div>
</body>
</html>
