<?php
header("Content-Type: application/json");
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email_config.php';

use Smalot\PdfParser\Parser;

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ==========================================
// CLOUDINARY UPLOAD HELPER FUNCTION
// ==========================================
function uploadToCloudinary($file_tmp_path, $public_id) {
    $cloud_url = getenv('CLOUDINARY_URL');
    if (!$cloud_url) {
        return ['error' => 'CLOUDINARY_URL not configured in Render Environment Variables'];
    }
    
    preg_match('/cloudinary:\/\/(.*):(.*)@(.*)/', $cloud_url, $matches);
    $api_key = $matches[1] ?? '';
    $api_secret = $matches[2] ?? '';
    $cloud_name = $matches[3] ?? '';

    if (!$api_key || !$api_secret || !$cloud_name) {
        return ['error' => 'Invalid CLOUDINARY_URL format'];
    }

    $timestamp = time();
    $params_to_sign = "public_id=$public_id&timestamp=$timestamp";
    $signature = sha1($params_to_sign . $api_secret);

    // PDF বা ডকুমেন্টের জন্য auto/upload
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/auto/upload";

    $post_data = [
        'file' => new CURLFile($file_tmp_path),
        'api_key' => $api_key,
        'timestamp' => $timestamp,
        'public_id' => $public_id,
        'signature' => $signature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode == 200 && isset($result['secure_url'])) {
        return ['success' => true, 'url' => $result['secure_url']];
    } else {
        return ['error' => $result['error']['message'] ?? 'Upload failed', 'raw' => $response];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // ==========================================
    // FORM DATA
    // ==========================================
    $name = trim($_POST['name'] ?? $_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $config_id = (int)($_POST['config_id'] ?? 1);

    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and Email are required.']);
        exit;
    }

    // ==========================================
    // RESUME FILE VALIDATION
    // ==========================================
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Resume file is required.']);
        exit;
    }

    $file_tmp_path = $_FILES['resume']['tmp_name'];
    $original_name = basename($_FILES['resume']['name']);
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Only PDF resumes are allowed for AI parsing.']);
        exit;
    }

    // ==========================================
    // PDF TEXT EXTRACTION (লোকাল টেম্পোরারি ফাইল থেকে)
    // ==========================================
    $resume_text = '';
    try {
        $parser = new Parser();
        $pdf = $parser->parseFile($file_tmp_path); // লোকাল টেম্পোরারি পাথ ব্যবহার করা হলো
        $resume_text = $pdf->getText();
        
        // টেক্সট পরিষ্কার (শুধু কন্ট্রোল ক্যারেক্টার রিমুভ, ইউনিকোড রাখা হলো)
        $resume_text = mb_convert_encoding($resume_text, 'UTF-8', 'UTF-8');
        $resume_text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $resume_text);
        $resume_text = trim($resume_text);
        
        error_log("PDF Text Length: " . strlen($resume_text));
    } catch (Throwable $e) {
        error_log("PDF Parsing Error: " . $e->getMessage());
    }

    // ==========================================
    // CLOUDINARY-তে ফাইল আপলোড
    // ==========================================
    $public_id = 'kormoshathi/resumes/resume_' . time() . '_' . uniqid();
    $upload_result = uploadToCloudinary($file_tmp_path, $public_id);

    if (!isset($upload_result['success'])) {
        error_log("Cloudinary Resume Upload Error: " . json_encode($upload_result));
        echo json_encode(['success' => false, 'message' => 'Failed to upload resume to cloud storage.']);
        exit;
    }
    
    $resume_path = $upload_result['url']; // ডাটাবেসে সেভ হবে Cloudinary URL

    // ==========================================
    // AI SCREENING CONFIGURATION
    // ==========================================
    $stmt = $db->prepare("SELECT required_skills, job_title FROM ai_screening_config WHERE id = ?");
    $stmt->execute([$config_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    $required_skills = $job ? $job['required_skills'] : 'PHP, MySQL, JavaScript';
    $job_title = $job ? $job['job_title'] : 'Software Engineer';

    // ==========================================
    // GROQ AI RESUME ANALYSIS
    // ==========================================
    $api_key = getEnvValue('GROQ_API_KEY');

    $score = 50;
    $recommendation = 'review';
    $skills_found = 'None detected';
    $education = 'Not specified';
    $experience = 'Not specified';

    if (!empty($api_key) && !empty($resume_text)) {
        $prompt = "You are an expert HR AI. Analyze the following resume text for the job role: '$job_title'.\n" .
                  "Required Skills: $required_skills.\n\n" .
                  "Output a raw JSON object (no markdown, no extra text) with exactly these 5 keys:\n" .
                  "1. 'score': Integer (0-100) evaluating how well the resume matches the job.\n" .
                  "2. 'recommendation': String ('shortlist' if score >= 80, 'review' if 40-79, 'reject' if < 40).\n" .
                  "3. 'skills_found': String of comma-separated matched skills.\n" .
                  "4. 'education': String (highest degree found).\n" .
                  "5. 'experience': String (total years found).\n\n" .
                  "Resume Text:\n" . substr($resume_text, 0, 4000);

        $data = [
            'model' => 'openai/gpt-oss-120b',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object']
        ];

        $json_payload = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

        if ($json_payload === false) {
            error_log("JSON Encode Error: " . json_last_error_msg());
            $skills_found = 'JSON Error in PHP';
        } else {
            $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json_payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key
                ],
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            
            error_log("Groq API HTTP Code: " . $http_code);

            if ($response !== false && $http_code >= 200 && $http_code < 300) {
                $ai_result = json_decode($response, true);
                $ai_content = $ai_result['choices'][0]['message']['content'] ?? '';
                
                $ai_content = str_replace(['```json', '```'], '', $ai_content);
                $parsed = json_decode(trim($ai_content), true);

                if (is_array($parsed)) {
                    $score = max(0, min(100, (int)($parsed['score'] ?? 50)));
                    $recommendation = $score >= 80 ? 'shortlist' : ($score < 40 ? 'reject' : 'review');
                    $skills_found = $parsed['skills_found'] ?? 'None detected';
                    $education = $parsed['education'] ?? 'Not specified';
                    $experience = $parsed['experience'] ?? 'Not specified';
                } else {
                    error_log("Failed to parse AI JSON: " . $ai_content);
                }
            } else {
                error_log("Groq API Error: HTTP $http_code | $curl_error");
            }
        }
    } else {
        error_log("Missing API Key or Empty Resume Text.");
    }

    // ==========================================
    // 🟢 SAVE SCREENING RESULT (resume_text সহ)
    // ==========================================
    $stmt = $db->prepare("
        INSERT INTO ai_screening_results
        (config_id, candidate_name, candidate_email, phone, resume_path, resume_text, match_score, recommendation, skills_found, education, experience, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $config_id, $name, $email, $phone, $resume_path, $resume_text, $score, $recommendation, $skills_found, $education, $experience
    ]);

    // ==========================================
    // EMAIL NOTIFICATIONS
    // ==========================================
    $safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safe_job_title = htmlspecialchars($job_title, ENT_QUOTES, 'UTF-8');

    $confirm_subject = "Application Received - $job_title";
    $confirm_body = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;'><h2 style='color:#356df3;'>Thank you for applying!</h2><p>Dear <b>$safe_name</b>,</p><p>We have received your resume for the position of <b>$safe_job_title</b>.</p><p>We will get back to you shortly.</p><br><p>Best Regards,<br>HR Team - KormoShathi</p></div>";
    sendEmail($email, $confirm_subject, $confirm_body);

    if ($score >= 80) {
        $subject = "Interview Invitation - $job_title";
        $body = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;'><h2 style='color:#10b981;'>Congratulations!</h2><p>Dear <b>$safe_name</b>,</p><p>Your profile is an excellent match for <b>$safe_job_title</b>.</p><p>We would like to invite you for an interview.</p><br><p>Best Regards,<br>HR Team - KormoShathi</p></div>";
        sendEmail($email, $subject, $body);
    } elseif ($score < 40) {
        $subject = "Application Update - $job_title";
        $body = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;'><h2 style='color:#ef4444;'>Application Update</h2><p>Dear <b>$safe_name</b>,</p><p>Thank you for applying for <b>$safe_job_title</b>.</p><p>After reviewing your resume, we found that your current profile does not sufficiently match the requirements for this specific role.</p><p>We wish you the best in your future endeavors.</p><br><p>Best Regards,<br>HR Team - KormoShathi</p></div>";
        sendEmail($email, $subject, $body);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Resume submitted and analyzed successfully.',
        'data' => ['score' => $score, 'recommendation' => $recommendation]
    ]);

} catch (Throwable $e) {
    error_log("Resume Submission Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong while processing your resume.']);
}
?>