<?php
header("Content-Type: application/json");
session_start();
require_once '../config/database.php';
require_once '../config/email_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['result_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$id = $input['result_id'];
$action = $input['action'];

try {
    $stmt = $db->prepare("SELECT * FROM ai_screening_results WHERE id = ?");
    $stmt->execute([$id]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
        exit;
    }

    $email = $candidate['candidate_email'];
    $name = $candidate['candidate_name'];

    if ($action === 'accept') {
        $stmt = $db->prepare("UPDATE ai_screening_results SET status = 'manual_shortlisted', recommendation = 'shortlist' WHERE id = ?");
        $stmt->execute([$id]);
        
        $subject = "🎉 Interview Invitation - KormoShathi";
        $interview_date = $input['interview_date'] ?? 'To Be Announced';
        $meeting_link = !empty($input['meeting_link']) ? "<p><b>Meeting Link:</b> <a href='{$input['meeting_link']}'>{$input['meeting_link']}</a></p>" : "";

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #356df3; padding: 20px; text-align: center; color: white;'>
                    <h2>Interview Invitation</h2>
                </div>
                <div style='padding: 30px;'>
                    <p>Dear <b>{$name}</b>,</p>
                    <p>Congratulations! We were impressed by your resume and would like to invite you for an interview.</p>
                    <div style='background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p><b>Date & Time:</b> {$interview_date}</p>
                        {$meeting_link}
                    </div>
                    <p>Please confirm your availability by replying to this email.</p>
                    <br>
                    <p>Best Regards,</p>
                    <p><b>HR Team</b><br>KormoShathi HR Management</p>
                </div>
            </div>
        ";
        
        $mailSent = sendEmail($email, $subject, $body);
        $msg = $mailSent ? "Candidate Accepted. Interview Email sent successfully!" : "Candidate Accepted, but Email failed to send (Check logs).";

        echo json_encode(['success' => true, 'message' => $msg]);

    } elseif ($action === 'reject') {
        $reason = $input['rejection_reason'] ?? 'No specific reason provided.';
        
        $stmt = $db->prepare("UPDATE ai_screening_results SET status = 'manual_rejected', recommendation = 'reject', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $id]);
        
        $subject = "Update on your application - KormoShathi";
        $safe_reason = nl2br(htmlspecialchars($reason)); // Protects against HTML injection
        
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #ef4444; padding: 20px; text-align: center; color: white;'>
                    <h2>Application Update</h2>
                </div>
                <div style='padding: 30px;'>
                    <p>Dear <b>{$name}</b>,</p>
                    <p>Thank you for taking the time to apply to KormoShathi and for your interest in joining our team.</p>
                    <p>After careful consideration of your resume, we regret to inform you that we will not be moving forward with your application at this time.</p>
                    <div style='background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;'>
                        <p style='margin:0;'><b>HR Feedback:</b></p>
                        <p style='margin: 5px 0 0 0; color: #475569;'>{$safe_reason}</p>
                    </div>
                    <p>We appreciate your effort and wish you the best of luck in your future endeavors.</p>
                    <br>
                    <p>Best Regards,</p>
                    <p><b>HR Team</b><br>KormoShathi HR Management</p>
                </div>
            </div>
        ";
        
        $mailSent = sendEmail($email, $subject, $body);
        $msg = $mailSent ? "Candidate Rejected. Feedback Email sent successfully!" : "Candidate Rejected, but Email failed to send (Check logs).";

        echo json_encode(['success' => true, 'message' => $msg]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>
