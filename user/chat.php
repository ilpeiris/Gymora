<?php
// /Gymora/user/chat.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);

$user_id = $_SESSION['user_id'];

// Fetch ONLY staff who have interacted with this user (Appointments, Plans, or Assessments)
$contactsStmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name, u.role 
    FROM users u 
    LEFT JOIN appointments a ON u.id = a.staff_id AND a.user_id = ?
    LEFT JOIN workout_plans w ON u.id = w.trainer_id AND w.user_id = ?
    LEFT JOIN medical_assessments m ON u.id = m.doctor_id AND m.user_id = ?
    WHERE u.role IN ('doctor', 'trainer') 
    AND u.is_active = 1 
    AND (a.id IS NOT NULL OR w.id IS NOT NULL OR m.id IS NOT NULL)
    ORDER BY u.role, u.name
");
$contactsStmt->execute([$user_id, $user_id, $user_id]);
$contacts = $contactsStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-chat-dots"></i> Gymora Secure Messenger</h2>
        <p class="text-muted">Chat securely with your medical and fitness support team.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-dark h-100">
            <div class="card-header bg-dark text-white fw-bold">
                My Care Team
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($contacts as $contact): ?>
                    <button class="list-group-item list-group-item-action contact-item" 
                            id="contact-<?= $contact['id'] ?>" 
                            onclick="loadChat(<?= $contact['id'] ?>, '<?= addslashes(htmlspecialchars($contact['name'])) ?>')">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?= htmlspecialchars($contact['name']) ?></strong>
                            <span class="badge <?= $contact['role'] == 'doctor' ? 'bg-danger' : 'bg-primary' ?>">
                                <?= ucfirst($contact['role']) ?>
                            </span>
                        </div>
                    </button>
                <?php endforeach; ?>
                <?php if (count($contacts) == 0): ?>
                    <div class="p-3 text-muted">No staff available to chat.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-secondary h-100">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                <span id="chat-with-name">Select a contact</span>
                <i class="bi bi-shield-lock" title="Messages are secure"></i>
            </div>
            
            <div class="card-body bg-white" id="chat-box" style="height: 400px; overflow-y: auto;">
                <div id="empty-chat-state" class="text-center text-muted mt-5">
                    <i class="bi bi-chat-left-text display-4 text-light"></i>
                    <p class="mt-2">Click a contact on the left to start chatting.</p>
                </div>
            </div>
            
            <div class="card-footer bg-light" id="message-form" style="display: none;">
                <form id="message-input-form" class="d-flex">
                    <input type="text" id="message-input" class="form-control me-2" placeholder="Type your message securely..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-send"></i> Send</button>
                </form>
                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                    * Never share banking details here. If experiencing a medical emergency, call 999.
                </small>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/chat.js?v=<?= time() ?>"></script>

<?php require_once '../includes/footer.php'; ?>