<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    /**
     * Configure SMTP settings
     */
    private function configureSMTP() {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = SMTP_AUTH;
            $this->mail->Username = SMTP_USERNAME;
            $this->mail->Password = SMTP_PASSWORD;
            $this->mail->SMTPSecure = SMTP_SECURE;
            $this->mail->Port = SMTP_PORT;
            $this->mail->CharSet = EMAIL_CHARSET;
            
            // Debug settings
            $this->mail->SMTPDebug = EMAIL_DEBUG;
            
            // Default sender
            $this->mail->setFrom(FROM_EMAIL, FROM_NAME);
            $this->mail->addReplyTo(REPLY_TO_EMAIL, REPLY_TO_NAME);
            
        } catch (Exception $e) {
            error_log("SMTP Configuration Error: " . $e->getMessage());
        }
    }
    
    /**
     * Send ticket status update email to customer
     */
    public function sendTicketStatusUpdate($customerEmail, $customerName, $ticketId, $status, $message = '', $description = '') {
        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            
            // Recipients
            $this->mail->addAddress($customerEmail, $customerName);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = "Ticket #{$ticketId} Status Update - " . ucfirst($status);
            
            $htmlBody = $this->getTicketStatusEmailTemplate($customerName, $ticketId, $status, $message, $description);
            $this->mail->Body = $htmlBody;
            $this->mail->AltBody = strip_tags($htmlBody);
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send new ticket confirmation email
     */
    public function sendTicketConfirmation($customerEmail, $customerName, $ticketId, $serviceType, $description) {
        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            
            // Recipients
            $this->mail->addAddress($customerEmail, $customerName);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = "Ticket Confirmation - #{$ticketId}";
            
            $htmlBody = $this->getTicketConfirmationTemplate($customerName, $ticketId, $serviceType, $description);
            $this->mail->Body = $htmlBody;
            $this->mail->AltBody = strip_tags($htmlBody);
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Confirmation email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send completion notification with invoice/receipt
     */
    public function sendCompletionNotification($customerEmail, $customerName, $ticketId, $totalAmount, $services) {
        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            
            // Recipients
            $this->mail->addAddress($customerEmail, $customerName);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = "Service Completed - Ticket #{$ticketId}";
            
            $htmlBody = $this->getCompletionEmailTemplate($customerName, $ticketId, $totalAmount, $services);
            $this->mail->Body = $htmlBody;
            $this->mail->AltBody = strip_tags($htmlBody);
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Completion notification sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get ticket status update email template
     */
    private function getTicketStatusEmailTemplate($customerName, $ticketId, $status, $message, $description = '') {
        $statusColors = [
            'pending' => ['bg' => '#ffc107', 'icon' => '⏳', 'text' => 'PENDING'],
            'in_progress' => ['bg' => '#17a2b8', 'icon' => '🔧', 'text' => 'IN PROGRESS'],
            'completed' => ['bg' => '#28a745', 'icon' => '✅', 'text' => 'COMPLETED'],
            'cancelled' => ['bg' => '#dc3545', 'icon' => '❌', 'text' => 'CANCELLED']
        ];
        
        $statusInfo = $statusColors[$status] ?? ['bg' => '#6c757d', 'icon' => '📋', 'text' => strtoupper($status)];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Ticket Status Update</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; line-height: 1.6; color: #333333; max-width: 650px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
            
            <!-- Header Section -->
            <div style='background: #2c3e50; padding: 30px; border-radius: 8px; margin-bottom: 25px; text-align: center; border-top: 4px solid #3498db;'>
                <h1 style='color: white; margin: 0; font-size: 26px; font-weight: 600;'>FourJS Air Conditioning</h1>
                <p style='margin: 8px 0 0 0; color: #bdc3c7; font-size: 16px;'>Service Update Notification</p>
            </div>
            
            <!-- Main Content -->
            <div style='background: white; padding: 35px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;'>
                
                <!-- Greeting -->
                <div style='margin-bottom: 30px;'>
                    <h2 style='color: #2c3e50; margin: 0 0 15px 0; font-size: 22px; font-weight: 600;'>Hello {$customerName},</h2>
                    <p style='margin: 0; font-size: 16px; color: #555555; line-height: 1.6;'>We wanted to update you on the status of your air conditioning service ticket. Here's the latest information:</p>
                </div>
                
                <!-- Status Update Card -->
                <div style='background: #f8f9fa; padding: 25px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #3498db;'>
                    <h3 style='margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;'>
                        📋 Ticket Details
                    </h3>
                    
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: 15px; width: 40%;'>Ticket ID:</td>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; text-align: right; color: #2c3e50; font-weight: 600; font-size: 15px;'>
                                #{$ticketId}
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; font-weight: 600; color: #495057; font-size: 15px;'>Current Status:</td>
                            <td style='padding: 12px 0; text-align: right;'>
                                <span style='background: {$statusInfo['bg']}; color: white; padding: 8px 16px; border-radius: 4px; font-size: 13px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;'>
                                    {$statusInfo['text']}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                " . ($description ? "
                <!-- Job Order Description -->
                <div style='background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #6c757d;'>
                    <h3 style='margin: 0 0 15px 0; color: #495057; font-size: 16px; font-weight: 600;'>
                        📋 Job Order Description
                    </h3>
                    <div style='color: #495057; font-size: 15px; line-height: 1.6; background: white; padding: 15px; border-radius: 4px; border: 1px solid #dee2e6;'>
                        {$description}
                    </div>
                </div>
                " : "") . "
                
                " . ($message ? "
                <!-- Additional Information -->
                <div style='background: #e8f4fd; padding: 20px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #2196f3;'>
                    <h3 style='margin: 0 0 15px 0; color: #1976d2; font-size: 16px; font-weight: 600;'>
                        💬 Additional Information
                    </h3>
                    <div style='color: #1565c0; font-size: 15px; line-height: 1.6; background: white; padding: 15px; border-radius: 4px; border: 1px solid #bbdefb;'>
                        {$message}
                    </div>
                </div>
                " : "") . "
                
                <!-- Status-specific Information -->
                " . ($status === 'completed' ? "
                <div style='background: #e8f5e8; padding: 20px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #28a745;'>
                    <h3 style='margin: 0 0 15px 0; color: #155724; font-size: 16px; font-weight: 600;'>
                        🎉 Service Completed!
                    </h3>
                    <div style='color: #155724; font-size: 15px; line-height: 1.6;'>
                        <p style='margin: 0 0 10px 0;'>Your air conditioning service has been completed successfully</p>
                        <p style='margin: 0 0 10px 0;'>You should receive a detailed completion report shortly</p>
                        <p style='margin: 0;'>We'd love to hear about your experience with our service</p>
                    </div>
                </div>
                " : ($status === 'in_progress' ? "
                <div style='background: #fff3cd; padding: 20px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #ffc107;'>
                    <h3 style='margin: 0 0 15px 0; color: #856404; font-size: 16px; font-weight: 600;'>
                        🔧 Work in Progress
                    </h3>
                    <div style='color: #856404; font-size: 15px; line-height: 1.6;'>
                        <p style='margin: 0 0 10px 0;'>Our technician is currently working on your air conditioning system</p>
                        <p style='margin: 0 0 10px 0;'>You'll receive updates as the work progresses</p>
                        <p style='margin: 0;'>Estimated completion will be communicated shortly</p>
                    </div>
                </div>
                " : "")) . "
                
                <!-- Contact Information -->
                <div style='text-align: center; margin: 30px 0 0 0; padding: 20px; background: #f8f9fa; border-radius: 6px;'>
                    <h3 style='margin: 0 0 12px 0; color: #2c3e50; font-size: 16px; font-weight: 600;'>Questions or Concerns?</h3>
                    <p style='margin: 0; color: #555555; font-size: 14px; line-height: 1.5;'>
                        If you have any questions about your service or need to make changes,<br>
                        please don't hesitate to contact our support team.
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='text-align: center; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 1px 5px rgba(0,0,0,0.1);'>
                <div style='margin-bottom: 12px;'>
                    <h4 style='margin: 0; color: #3498db; font-size: 16px; font-weight: 600;'>Thank you for choosing FourJS!</h4>
                </div>
                <div style='color: #6c757d; font-size: 12px; line-height: 1.4;'>
                    <p style='margin: 4px 0;'>This is an automated message. Please do not reply to this email.</p>
                    <p style='margin: 4px 0;'>© 2024 FourJS Air Conditioning Services. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get ticket confirmation email template
     */
    private function getTicketConfirmationTemplate($customerName, $ticketId, $serviceType, $description) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Ticket Confirmation</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; line-height: 1.6; color: #333333; max-width: 650px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
            
            <!-- Header Section -->
            <div style='background: #2c3e50; padding: 30px; border-radius: 8px; margin-bottom: 25px; text-align: center; border-top: 4px solid #3498db;'>
                <h1 style='color: white; margin: 0; font-size: 26px; font-weight: 600;'>FourJS Air Conditioning</h1>
                <p style='margin: 8px 0 0 0; color: #bdc3c7; font-size: 16px;'>Service Request Confirmation</p>
            </div>
            
            <!-- Main Content -->
            <div style='background: white; padding: 35px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;'>
                
                <!-- Greeting -->
                <div style='margin-bottom: 30px;'>
                    <h2 style='color: #2c3e50; margin: 0 0 15px 0; font-size: 22px; font-weight: 600;'>Hello {$customerName},</h2>
                    <p style='margin: 0; font-size: 16px; color: #555555; line-height: 1.6;'>Thank you for choosing FourJS Air Conditioning Services! We have successfully received your service request and created a ticket for you.</p>
                </div>
                
                <!-- Ticket Information Card -->
                <div style='background: #f8f9fa; padding: 25px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #3498db;'>
                    <h3 style='margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;'>
                        📋 Ticket Information
                    </h3>
                    
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: 15px; width: 40%;'>Ticket ID:</td>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; text-align: right; color: #2c3e50; font-weight: 600; font-size: 15px;'>
                                #{$ticketId}
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: 15px;'>Service Type:</td>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; text-align: right; color: #2c3e50; font-weight: 500; font-size: 15px;'>{$serviceType}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: 15px; vertical-align: top;'>Description:</td>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; text-align: right;'>
                                <div style='color: #2c3e50; font-size: 15px; line-height: 1.6; background: white; padding: 12px; border-radius: 4px; border: 1px solid #dee2e6; text-align: left;'>{$description}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; font-weight: 600; color: #495057; font-size: 15px;'>Current Status:</td>
                            <td style='padding: 12px 0; text-align: right;'>
                                <span style='background: #ffc107; color: white; padding: 8px 16px; border-radius: 4px; font-size: 13px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;'>PENDING</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- What's Next Section -->
                <div style='background: #f8f9fa; padding: 25px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #28a745;'>
                    <h3 style='margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;'>
                     What's Next?
                    </h3>
                    
                    <div style='color: #555555; font-size: 15px; line-height: 1.6;'>
                        <div style='margin-bottom: 15px; padding: 12px 0; border-bottom: 1px solid #dee2e6;'>
                            <strong style='color: #2c3e50;'>1. Confirmation:</strong> You will receive updates via email as your ticket progresses.
                        </div>
                        <div style='margin-bottom: 15px; padding: 12px 0; border-bottom: 1px solid #dee2e6;'>
                            <strong style='color: #2c3e50;'>2. Assignment:</strong> Our technical team will review and assign a technician to your case.
                        </div>
                        <div style='margin-bottom: 15px; padding: 12px 0; border-bottom: 1px solid #dee2e6;'>
                            <strong style='color: #2c3e50;'>3. Contact:</strong> The assigned technician will contact you to schedule the service.
                        </div>
                        <div style='padding: 12px 0;'>
                            <strong style='color: #2c3e50;'>4. Service:</strong> Professional service will be provided at your scheduled time.
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div style='background: #e8f4fd; padding: 20px; border-radius: 6px; margin: 25px 0; border: 1px solid #bee5eb;'>
                    <h4 style='margin: 0 0 15px 0; color: #2c3e50; font-size: 16px; font-weight: 600;'>Track Your Service</h4>
                    <p style='margin: 0; color: #555555; font-size: 14px; line-height: 1.6;'>
                        You can track your ticket status anytime by visiting our <strong style='color: #2c3e50;'>ticket status page</strong> and entering your ticket ID: <strong style='color: #2c3e50;'>#{$ticketId}</strong>
                    </p>
                </div>
                
                <!-- Contact Information -->
                <div style='background: #e8f4fd; padding: 20px; border-radius: 6px; margin: 25px 0; border: 1px solid #bee5eb;'>
                    <h4 style='margin: 0 0 15px 0; color: #2c3e50; font-size: 16px; font-weight: 600;'>Need Help?</h4>
                    <p style='margin: 0; color: #555555; font-size: 14px; line-height: 1.6;'>
                        If you have any questions about your service request, please don't hesitate to contact us at 
                        <strong style='color: #2c3e50;'>support@fourjs.com</strong> or call us at 
                        <strong style='color: #2c3e50;'>(555) 123-4567</strong>.
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 13px; border-top: 1px solid #dee2e6; margin-top: 30px;'>
                <p style='margin: 0 0 8px 0;'>© 2024 FourJS Air Conditioning Services. All rights reserved.</p>
                <p style='margin: 0; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get completion notification email template
     */
    private function getCompletionEmailTemplate($customerName, $ticketId, $serviceType, $totalAmount) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Service Completion Notification</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; line-height: 1.6; color: #333333; max-width: 650px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
            
            <!-- Header Section -->
            <div style='background: #2c3e50; padding: 30px; border-radius: 8px; margin-bottom: 25px; text-align: center; border-top: 4px solid #28a745;'>
                <h1 style='color: white; margin: 0; font-size: 26px; font-weight: 600;'>FourJS Air Conditioning</h1>
                <p style='margin: 8px 0 0 0; color: #bdc3c7; font-size: 16px;'>Service Completion Notification</p>
            </div>
            
            <!-- Main Content -->
            <div style='background: white; padding: 35px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;'>
                
                <!-- Greeting -->
                <div style='margin-bottom: 30px;'>
                    <h2 style='color: #2c3e50; margin: 0 0 15px 0; font-size: 22px; font-weight: 600;'>Hello {$customerName},</h2>
                    <p style='margin: 0; font-size: 16px; color: #555555; line-height: 1.6;'>Great news! Your air conditioning service has been successfully completed. We're pleased to inform you that your system is now running optimally.</p>
                </div>
                
                <!-- Completion Status Card -->
                <div style='background: #d4edda; padding: 25px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #28a745; text-align: center;'>
                    <div style='margin-bottom: 15px;'>
                        <span style='font-size: 48px; color: #28a745;'>✅</span>
                    </div>
                    <h3 style='margin: 0 0 10px 0; color: #155724; font-size: 20px; font-weight: 600;'>Service Completed Successfully!</h3>
                    <p style='margin: 0; color: #155724; font-size: 16px;'>Your air conditioning system is now fully operational</p>
                </div>
                
                <!-- Service Summary -->
                <div style='background: #f8f9fa; padding: 25px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #3498db;'>
                    <h3 style='margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;'>
                        📋 Service Summary
                    </h3>
                    
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: 15px; width: 40%;'>Ticket ID:</td>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; text-align: right; color: #2c3e50; font-weight: 600; font-size: 15px;'>
                                #{$ticketId}
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: 15px;'>Service Type:</td>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; text-align: right; color: #2c3e50; font-weight: 500; font-size: 15px;'>{$serviceType}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: 15px;'>Status:</td>
                            <td style='padding: 12px 0; border-bottom: 1px solid #dee2e6; text-align: right;'>
                                <span style='background: #28a745; color: white; padding: 8px 16px; border-radius: 4px; font-size: 13px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;'>COMPLETED</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; font-weight: 600; color: #495057; font-size: 15px;'>Total Amount:</td>
                            <td style='padding: 12px 0; text-align: right;'>
                                <span style='background: #2c3e50; color: white; padding: 10px 16px; border-radius: 4px; font-size: 18px; font-weight: 700;'>{${totalAmount}}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Important Information -->
                <div style='background: #fff3cd; padding: 25px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #ffc107;'>
                    <h3 style='margin: 0 0 20px 0; color: #856404; font-size: 18px; font-weight: 600;'>
                        💡 Important Information
                    </h3>
                    <div style='color: #856404; font-size: 15px; line-height: 1.6;'>
                        <div style='margin-bottom: 15px; padding: 12px 0; border-bottom: 1px solid #ffeaa7;'>
                            <strong>🔧 Maintenance Tips:</strong> Regular cleaning and filter replacement will keep your AC running efficiently
                        </div>
                        <div style='margin-bottom: 15px; padding: 12px 0; border-bottom: 1px solid #ffeaa7;'>
                            <strong>📞 Support:</strong> Contact us anytime if you experience any issues with your air conditioning system
                        </div>
                        <div style='padding: 12px 0;'>
                            <strong>📅 Next Service:</strong> We recommend scheduling maintenance every 6 months for optimal performance
                        </div>
                    </div>
                </div>
                
                <!-- Feedback Section -->
                <div style='background: #e8f4fd; padding: 20px; border-radius: 6px; margin: 25px 0; border: 1px solid #bee5eb;'>
                    <h4 style='margin: 0 0 15px 0; color: #2c3e50; font-size: 16px; font-weight: 600;'>⭐ We Value Your Feedback</h4>
                    <p style='margin: 0; color: #555555; font-size: 14px; line-height: 1.6;'>
                        Your satisfaction is our priority! We'd love to hear about your experience with our service. 
                        Your feedback helps us continue to provide excellent air conditioning services.
                    </p>
                </div>
                
                <!-- Contact Information -->
                <div style='background: #e8f4fd; padding: 20px; border-radius: 6px; margin: 25px 0; border: 1px solid #bee5eb;'>
                    <h4 style='margin: 0 0 15px 0; color: #2c3e50; font-size: 16px; font-weight: 600;'>Need Future Service?</h4>
                    <p style='margin: 0; color: #555555; font-size: 14px; line-height: 1.6;'>
                        For any future air conditioning needs, maintenance, or questions, don't hesitate to contact us at 
                        <strong style='color: #2c3e50;'>support@fourjs.com</strong> or call us at 
                        <strong style='color: #2c3e50;'>(555) 123-4567</strong>.
                    </p>
                </div>
                
            </div>
            
            <!-- Footer -->
            <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 13px; border-top: 1px solid #dee2e6; margin-top: 30px;'>
                <p style='margin: 0 0 8px 0;'>© 2024 FourJS Air Conditioning Services. All rights reserved.</p>
                <p style='margin: 0; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
            </div>
            
        </body>
        </html>
        ";
    }
}
?>