<?php
// URL থেকে config_id নেওয়া হচ্ছে (যদি না থাকে তবে ১ ডিফল্ট)
$config_id = $_GET['config_id'] ?? $_GET['id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Resume - KormoShathi</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body { background: #f5f7fb; min-height: 100vh; display: flex; align-items: center; font-family: 'DM Sans', sans-serif; }
        .container { max-width: 550px; margin: auto; padding: 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(26,43,70,0.08); border: none; overflow: hidden; }
        .card-header { background: linear-gradient(115deg, #356df3, #1d3d70); color: white; padding: 30px 20px; text-align: center; border: none; }
        .card-header h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; margin: 0; font-size: 24px; }
        .card-header p { opacity: 0.9; margin: 5px 0 0; font-size: 14px; }
        .card-body { padding: 30px; }
        
        .form-label { font-weight: 600; color: #435168; font-size: 14px; margin-bottom: 8px; }
        .form-control { border-radius: 8px; border: 1px solid #e9edf3; padding: 12px 15px; background-color: #fbfcfe; font-size: 14px; transition: 0.2s; }
        .form-control:focus { border-color: #356df3; background-color: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        
        .btn-primary { background: #356df3; border: none; padding: 12px; font-weight: 600; border-radius: 8px; transition: 0.3s; font-size: 15px; }
        .btn-primary:hover { background: #2859d9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(53,109,243,0.2); }
        
        .file-upload { border: 2px dashed #cbd5e1; padding: 25px 20px; text-align: center; border-radius: 10px; cursor: pointer; transition: 0.3s; background: #f8fafc; }
        .file-upload:hover { border-color: #356df3; background: #f4f8ff; }
        .file-upload i { font-size: 40px; color: #356df3; margin-bottom: 10px; }
        
        #fileInfo { display: none; }
        .alert { border-radius: 8px; font-size: 14px; font-weight: 500; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-paper-plane me-2"></i>Submit Your Resume</h4>
            <p>Apply for current job openings</p>
        </div>
        <div class="card-body">
            <form id="resumeForm" enctype="multipart/form-data">
                
                <!-- 🟢 এই হিডেন ইনপুটটি জব আইডি ব্যাকএন্ডে পাঠাবে -->
                <input type="hidden" name="config_id" value="<?php echo htmlspecialchars($config_id); ?>">

                <div class="mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone" placeholder="Enter your phone number">
                </div>
                <div class="mb-4">
                    <label class="form-label">Upload Resume <span class="text-danger">*</span></label>
                    <div class="file-upload" id="uploadBox" onclick="document.getElementById('resumeFile').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="mt-2 mb-0 fw-bold">Click to browse or drag & drop</p>
                        <small class="text-muted">PDF, DOC, DOCX (Max 10MB)</small>
                        <input type="file" class="d-none" name="resume" id="resumeFile" accept=".pdf,.doc,.docx" required>
                    </div>
                    <div id="fileInfo" class="mt-2">
                        <div class="alert alert-info d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-file-pdf me-2"></i><span id="fileName">file.pdf</span></span>
                            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeFile(event)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane me-2"></i>Submit Resume
                </button>
            </form>
            <div id="message" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('resumeFile').addEventListener('change', function() {
    if (this.files.length) {
        document.getElementById('fileInfo').style.display = 'block';
        document.getElementById('fileName').textContent = this.files[0].name;
        document.getElementById('uploadBox').style.display = 'none';
    }
});

function removeFile(event) {
    event.stopPropagation();
    document.getElementById('resumeFile').value = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('uploadBox').style.display = 'block';
}

document.getElementById('resumeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
    btn.disabled = true;
    document.getElementById('message').innerHTML = '';
    
    try {
        const response = await fetch('../api/submit-resume.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        const alertClass = result.success ? 'alert-success' : 'alert-danger';
        document.getElementById('message').innerHTML = `<div class="alert ${alertClass}">${result.message}</div>`;
        
        if (result.success) {
            this.reset();
            document.getElementById('fileInfo').style.display = 'none';
            document.getElementById('uploadBox').style.display = 'block';
        }
    } catch(e) {
        document.getElementById('message').innerHTML = `<div class="alert alert-danger">Connection error. Please try again.</div>`;
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});
</script>
</body>
</html>
