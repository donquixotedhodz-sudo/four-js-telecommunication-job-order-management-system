<?php
session_start();
require_once '../config/database.php';

// Initialize variables
$ticket_info = null;
$error_message = '';
$search_performed = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ticket_number'])) {
    $ticket_number = trim($_POST['ticket_number']);
    $search_performed = true;
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Query to get ticket information with related data
        $sql = "
            SELECT 
                jo.*,
                CASE 
                    WHEN jo.service_type = 'repair' THEN COALESCE(ap.part_name, 'Not Specified')
                    ELSE COALESCE(am.model_name, 'Not Specified')
                END as model_name,
                CASE 
                    WHEN jo.service_type = 'repair' THEN COALESCE(ap.part_category, 'Not Specified')
                    ELSE COALESCE(am.brand, 'Not Specified')
                END as brand,
                CASE 
                    WHEN jo.service_type = 'repair' THEN COALESCE(ap.part_code, 'Not Specified')
                    ELSE COALESCE(am.hp, 'Not Specified')
                END as model_details,
                t.name as technician_name,
                t.profile_picture as technician_profile,
                t2.name as secondary_technician_name,
                t2.profile_picture as secondary_technician_profile,
                cs.service_name as cleaning_service_name,
                cs.service_description as cleaning_service_description
            FROM job_orders jo 
            LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
            LEFT JOIN ac_parts ap ON jo.part_id = ap.id
            LEFT JOIN technicians t ON jo.assigned_technician_id = t.id
            LEFT JOIN technicians t2 ON jo.secondary_technician_id = t2.id
            LEFT JOIN cleaning_services cs ON jo.cleaning_service_id = cs.id
            WHERE jo.job_order_number = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ticket_number]);
        $ticket_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket_info) {
            $error_message = "No ticket found with number: " . htmlspecialchars($ticket_number);
        }
        
    } catch (PDOException $e) {
        $error_message = "Database error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Lookup - Four J's Aircon Services</title>
    <link rel="icon" href="../images/logo-favicon.ico" type="image/x-icon">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        .top-nav {
            background: #ffffff;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e9ecef;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-title-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-title-container img {
            height: 50px;
            width: auto;
        }
        
        .maya-logo {
            color: #00d4aa;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .search-box {
            position: absolute;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .search-box input {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            background: #f8f9fa;
            font-size: 0.9rem;
            width: 200px;
        }
        
        .hero-section {
            background: #1a1a1a;
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                 radial-gradient(circle at 20% 50%, rgba(0, 212, 170, 0.3) 0%, transparent 50%),
                 radial-gradient(circle at 80% 20%, rgba(0, 212, 170, 0.2) 0%, transparent 50%),
                 radial-gradient(circle at 40% 80%, rgba(0, 212, 170, 0.1) 0%, transparent 50%);
            background-size: 100% 100%;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2300d4aa' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 1rem 2rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .breadcrumb a {
            color: #00d4aa;
            text-decoration: none;
        }
        
        .main-content {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .ticket-form-section {
            background: #ffffff;
            border-radius: 8px;
            padding: 3rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }
        
        .center-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .center-logo img {
            height: 80px;
            width: auto;
            opacity: 0.8;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 2rem;
            color: #333;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
            background: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .btn-search {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
            min-width: 120px;
        }
        
        .btn-search:hover {
            background: #0056b3;
        }
        
        .disclaimer {
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #6c757d;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .disclaimer input[type="checkbox"] {
            margin-top: 0.2rem;
        }
        
        .footer-section {
            background: #1a1a1a;
            color: white;
            padding: 3rem 2rem;
            margin-top: 4rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        
        .footer-logo {
            color: #00d4aa;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .footer-section h3 {
            color: white;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .footer-section p {
            color: #adb5bd;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .ticket-info-section {
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .info-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .info-card h5 {
            color: #333;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-card p {
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
            color: #555;
            line-height: 1.4;
        }
        
        .info-card p strong {
            color: #333;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .bg-success {
            background: #28a745;
            color: white;
        }
        
        .bg-info {
            background: #17a2b8;
            color: white;
        }
        
        .bg-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .bg-danger {
            background: #dc3545;
            color: white;
        }
        
        .technician-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .top-nav {
                padding: 1rem;
            }
            
            .search-box {
                position: static;
                transform: none;
                margin-top: 1rem;
            }
            
            .hero-section {
                padding: 2rem 1rem;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .main-content {
                padding: 0 1rem;
            }
            
            .ticket-form-section {
                padding: 2rem 1.5rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>
</head>
 <body>
    
     <!-- Main Content -->
     <div class="main-content">
         <div class="ticket-form-section">
             <!-- Center Logo -->
             <div class="center-logo">
                 <img src="../images/logo.png" alt="Company Logo">
             </div>
             <h2>Track your support ticket</h2>
             <p>Enter the ticket support ticket number provided by our support team.</p>
             
             <form method="POST" action="">
                 <div class="form-group">
                     <div class="input-group">
                         <input 
                             type="text" 
                             class="form-control" 
                             id="ticket_number" 
                             name="ticket_number" 
                             placeholder="Enter ticket number"
                             value="<?= htmlspecialchars($_POST['ticket_number'] ?? '') ?>"
                             required
                         >
                         <button type="submit" class="btn-search">Search</button>
                     </div>
                 </div>
             </form>
             
             <div class="disclaimer">
                
                 <label for="disclaimer">Tickets are searchable for 1 year from the date they are created.</label>
             </div>
         </div>
             
             <?php if ($search_performed): ?>
                  <div class="ticket-info-section">
                      <?php if ($error_message): ?>
                          <div class="alert alert-danger d-flex align-items-center" role="alert">
                              <i class="fas fa-exclamation-triangle me-2"></i>
                              <?= $error_message ?>
                          </div>
                      <?php elseif ($ticket_info): ?>
                          <div class="row">
                              <div class="col-12">
                                  <h4 class="mb-4 text-primary">
                                      <i class="fas fa-info-circle me-2"></i>Ticket Status
                                  </h4>
                              </div>
                          </div>
                          
                          <!-- Status Information Only -->
                          <div class="info-card text-center">
                              <h5 class="text-primary mb-3">
                                  <i class="fas fa-clipboard-check me-2"></i>Ticket #<?= htmlspecialchars($ticket_info['job_order_number']) ?>
                              </h5>
                              <div class="status-display">
                                  <p class="mb-2"><strong>Current Status:</strong></p>
                                  <span class="badge status-badge bg-<?= 
                                      $ticket_info['status'] === 'completed' ? 'success' : 
                                      ($ticket_info['status'] === 'in_progress' ? 'info' : 
                                      ($ticket_info['status'] === 'cancelled' ? 'danger' : 'warning')) 
                                  ?>" style="font-size: 1.2rem; padding: 0.5rem 1rem;">
                                      <?= ucfirst(htmlspecialchars($ticket_info['status'])) ?>
                                  </span>
                                  <?php if ($ticket_info['status'] === 'completed' && $ticket_info['completed_at']): ?>
                                      <p class="mt-3 text-muted">
                                          <i class="fas fa-calendar-check me-1"></i>
                                          Completed on <?= date('M d, Y', strtotime($ticket_info['completed_at'])) ?>
                                      </p>
                                  <?php elseif ($ticket_info['status'] === 'in_progress'): ?>
                                      <p class="mt-3 text-muted">
                                          <i class="fas fa-clock me-1"></i>
                                          Work in progress
                                      </p>
                                  <?php elseif ($ticket_info['status'] === 'pending'): ?>
                                      <p class="mt-3 text-muted">
                                          <i class="fas fa-hourglass-half me-1"></i>
                                          Waiting to be processed
                                      </p>
                                  <?php endif; ?>
                              </div>
                          </div>
                      <?php endif; ?>
                  </div>
              <?php endif; ?>
          </div>
      

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Reset search input on page reload
        window.addEventListener('load', function() {
            const ticketInput = document.getElementById('ticket_number');
            if (ticketInput && !ticketInput.value) {
                ticketInput.value = '';
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const ticketInput = document.getElementById('ticket_number');
            const searchForm = document.querySelector('form');
            const searchBtn = document.querySelector('.btn-search');
            
            // Clear input on page load if no search was performed
            <?php if (!$search_performed): ?>
                ticketInput.value = '';
            <?php endif; ?>
            
            // Auto-format ticket number input
            ticketInput.addEventListener('input', function(e) {
                let value = e.target.value.toUpperCase();
                
                // Remove any non-alphanumeric characters except hyphens
                value = value.replace(/[^A-Z0-9-]/g, '');
                
                // Auto-format to JO-YYYYMMDD-XXXX pattern if user starts typing numbers
                if (value.match(/^\d/)) {
                    value = 'JO-' + value;
                }
                
                e.target.value = value;
            });
            
            // Add loading state to search button
            searchForm.addEventListener('submit', function() {
                searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Searching...';
                searchBtn.disabled = true;
            });
            
            // Smooth scroll to results if they exist
            <?php if ($search_performed && ($ticket_info || $error_message)): ?>
                setTimeout(function() {
                    document.querySelector('.ticket-info-section').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            <?php endif; ?>
            
            // Add fade-in animation to info cards
            const infoCards = document.querySelectorAll('.info-card');
            infoCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Add hover effects to info cards
            infoCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
            
            // Add copy to clipboard functionality for ticket number
            const allParagraphs = document.querySelectorAll('p');
            allParagraphs.forEach(element => {
                if (element.textContent.includes('Ticket:')) {
                    element.style.cursor = 'pointer';
                    element.title = 'Click to copy ticket number';
                    
                    element.addEventListener('click', function() {
                        const ticketNumber = this.textContent.split(': ')[1];
                        navigator.clipboard.writeText(ticketNumber).then(() => {
                            // Show temporary success message
                            const originalText = this.innerHTML;
                            this.innerHTML = originalText + ' <small class="text-success"><i class="fas fa-check"></i> Copied!</small>';
                            
                            setTimeout(() => {
                                this.innerHTML = originalText;
                            }, 2000);
                        });
                    });
                }
            });
        });
        
        // Helper function to check if element contains text
        function containsText(element, text) {
            return element.textContent.includes(text);
        }
        
        // Add copy functionality to ticket details
        function addCopyFunctionality() {
            const detailItems = document.querySelectorAll('.ticket-detail-item p');
            detailItems.forEach(item => {
                if (item.textContent.includes('Ticket Number:') || 
                    item.textContent.includes('Customer Name:') || 
                    item.textContent.includes('Phone:') || 
                    item.textContent.includes('Email:')) {
                    item.style.cursor = 'pointer';
                    item.title = 'Click to copy';
                    item.addEventListener('click', function() {
                        const text = this.textContent.split(': ')[1];
                        if (text) {
                            navigator.clipboard.writeText(text).then(() => {
                                // Show temporary feedback
                                const originalText = this.textContent;
                                this.textContent = this.textContent.split(': ')[0] + ': Copied!';
                                this.style.color = '#28a745';
                                setTimeout(() => {
                                    this.textContent = originalText;
                                    this.style.color = '';
                                }, 1000);
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>