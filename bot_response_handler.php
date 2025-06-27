<?php
// bot_response_handler.php
// This file handles automatic bot responses

require_once __DIR__ . '/../config/db.php';


class ChatBot {
    private $conn;
    private $config;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->loadConfig();
    }
    
    // Load bot configuration from database
    private function loadConfig() {
        $config_stmt = $this->conn->query("SELECT config_key, config_value FROM bot_config WHERE is_active = 1");
        $this->config = [];
        
        if ($config_stmt) {
            while ($row = $config_stmt->fetch_assoc()) {
                $this->config[$row['config_key']] = $row['config_value'];
            }
        }
        
        // Set default values if not found
        $defaults = [
            'bot_name' => 'ChatBot Assistant',
            'auto_reply_enabled' => '1',
            'response_delay' => '2',
            'fallback_response' => 'I\'m sorry, I didn\'t understand that. Could you please rephrase your question?'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $value;
            }
        }
    }
    
    // Check if auto-reply is enabled
    public function isAutoReplyEnabled() {
        return $this->config['auto_reply_enabled'] == '1';
    }
    
    // Get bot response for a message
    public function getBotResponse($user_message, $user_id) {
        if (!$this->isAutoReplyEnabled()) {
            return null;
        }
        
        // Clean and normalize the message
        $message = strtolower(trim($user_message));
        
        // Check for bot responses in database
        $response = $this->findBestResponse($message);
        
        if ($response) {
            // Update usage count
            $this->updateResponseUsage($response['id']);
            return $response['response_text'];
        }
        
        // Return fallback response
        return $this->config['fallback_response'];
    }
    
    // Find the best matching response from database
    private function findBestResponse($message) {
        $stmt = $this->conn->prepare("
            SELECT id, trigger_keyword, response_text, response_type, priority 
            FROM bot_responses 
            WHERE is_active = 1 
            ORDER BY priority DESC, id ASC
        ");
        
        if (!$stmt->execute()) {
            return null;
        }
        
        $responses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $best_match = null;
        $highest_priority = -1;
        
        foreach ($responses as $response) {
            $trigger = strtolower($response['trigger_keyword']);
            $match = false;
            
            switch ($response['response_type']) {
                case 'exact':
                    $match = ($message === $trigger);
                    break;
                    
                case 'contains':
                    $match = (strpos($message, $trigger) !== false);
                    break;
                    
                case 'regex':
                    $match = @preg_match('/' . $trigger . '/i', $message);
                    break;
                    
                case 'fallback':
                    // Fallback responses have lowest priority
                    if ($trigger === '*' && $response['priority'] > $highest_priority && !$best_match) {
                        $best_match = $response;
                        $highest_priority = $response['priority'];
                    }
                    continue 2;
            }
            
            if ($match && $response['priority'] > $highest_priority) {
                $best_match = $response;
                $highest_priority = $response['priority'];
            }
        }
        
        return $best_match;
    }
    
    // Update response usage statistics
    private function updateResponseUsage($response_id) {
        $stmt = $this->conn->prepare("
            UPDATE bot_responses 
            SET usage_count = usage_count + 1 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $response_id);
        $stmt->execute();
    }
    
    // Send bot response to user
    public function sendBotResponse($user_id, $user_message) {
        $bot_response = $this->getBotResponse($user_message, $user_id);
        
        if ($bot_response) {
            // Add response delay if configured
            $delay = intval($this->config['response_delay'] ?? 0);
            if ($delay > 0) {
                sleep($delay);
            }
            
            // Insert bot response to database
            $stmt = $this->conn->prepare("
                INSERT INTO chat_messages (user_id, sender, message, created_at) 
                VALUES (?, 'bot', ?, NOW())
            ");
            $stmt->bind_param("is", $user_id, $bot_response);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => $bot_response,
                    'response_id' => $this->conn->insert_id
                ];
            }
        }
        
        return ['success' => false, 'message' => null];
    }
    
    // Check if it's within office hours
    public function isWithinOfficeHours() {
        $start_time = $this->config['office_hours_start'] ?? '09:00';
        $end_time = $this->config['office_hours_end'] ?? '17:00';
        
        $current_time = date('H:i');
        
        return ($current_time >= $start_time && $current_time <= $end_time);
    }
    
    // Get offline message
    public function getOfflineMessage() {
        return $this->config['offline_message'] ?? 'Thanks for your message! Our team will get back to you soon.';
    }
    
    // Process incoming user message and send auto-response
    public function processUserMessage($user_id, $user_message) {
        // Check if message contains keywords that require human intervention
        $transfer_keywords = json_decode($this->config['transfer_to_human_keywords'] ?? '[]', true);
        
        if (is_array($transfer_keywords)) {
            foreach ($transfer_keywords as $keyword) {
                if (stripos($user_message, $keyword) !== false) {
                    // Mark conversation for human transfer
                    $this->markForHumanTransfer($user_id);
                    return $this->sendBotResponse($user_id, "I'll connect you with a human agent right away! Please hold on for a moment.");
                }
            }
        }
        
        // Send auto-response
        return $this->sendBotResponse($user_id, $user_message);
    }
    
    // Mark conversation for human transfer
    private function markForHumanTransfer($user_id) {
        $stmt = $this->conn->prepare("
            UPDATE chat_sessions 
            SET status = 'transferred' 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    // Get bot statistics
    public function getBotStats() {
        $stats = [];
        
        // Total bot messages sent
        $stmt = $this->conn->query("
            SELECT COUNT(*) as total_bot_messages 
            FROM chat_messages 
            WHERE sender = 'bot'
        ");
        $stats['total_messages'] = $stmt->fetch_assoc()['total_bot_messages'];
        
        // Messages sent today
        $stmt = $this->conn->query("
            SELECT COUNT(*) as today_messages 
            FROM chat_messages 
            WHERE sender = 'bot' AND DATE(created_at) = CURDATE()
        ");
        $stats['today_messages'] = $stmt->fetch_assoc()['today_messages'];
        
        // Active conversations
        $stmt = $this->conn->query("
            SELECT COUNT(DISTINCT user_id) as active_conversations 
            FROM chat_messages 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stats['active_conversations'] = $stmt->fetch_assoc()['active_conversations'];
        
        // Most used responses
        $stmt = $this->conn->query("
            SELECT trigger_keyword, usage_count 
            FROM bot_responses 
            WHERE usage_count > 0 
            ORDER BY usage_count DESC 
            LIMIT 5
        ");
        $stats['popular_responses'] = $stmt->fetch_all(MYSQLI_ASSOC);
        
        return $stats;
    }
}

// Usage example:
/*
// Initialize the bot
$chatbot = new ChatBot($conn);

// Process a user message (this would typically be called from your chat handler)
if ($_POST['action'] === 'user_message') {
    $user_id = $_POST['user_id'];
    $message = $_POST['message'];
    
    // Store user message first
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender, message) VALUES (?, 'user', ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    
    // Send bot auto-response
    $response = $chatbot->processUserMessage($user_id, $message);
    
    if ($response['success']) {
        echo json_encode(['status' => 'success', 'bot_response' => $response['message']]);
    } else {
        echo json_encode(['status' => 'no_response']);
    }
}
*/
?>